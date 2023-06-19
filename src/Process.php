<?php

declare(strict_types=1);

namespace Mrluke\Bus;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonSerializable;
use Mrluke\Bus\Contracts\HandlerResult;
use Mrluke\Bus\Contracts\InteractsWithRepository;
use Mrluke\Bus\Contracts\Process as ProcessContract;
use Mrluke\Bus\Exceptions\InvalidAction;
use Mrluke\Bus\Exceptions\MissingHandler;
use stdClass;

/**
 * Class Process
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@gmail.com>
 * @author  Krzysztof Ustowski <krzysztof.ustowski@movecloser.pl>
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus
 */
class Process implements Arrayable, InteractsWithRepository, JsonSerializable, ProcessContract
{
    private string $bus;

    private CarbonImmutable $committedAt;

    private string|int|null $committedBy;

    private ?array $data;

    private ?CarbonImmutable $finishedAt;

    private array $handlers;

    private bool $hasBeenPersisted;

    private string $id;

    private ?int $pid;

    private string $process;

    private ?array $related;

    private array $results;

    private ?CarbonImmutable $startedAt;

    private string $status;

    /**
     * @param string                       $id
     * @param string                       $bus
     * @param string                       $process
     * @param string                       $status
     * @param array                        $handlers
     * @param int|null                     $pid
     * @param int|string|null              $committedBy
     * @param \Carbon\CarbonImmutable      $committedAt
     * @param \Carbon\CarbonImmutable|null $startedAt
     * @param \Carbon\CarbonImmutable|null $finishedAt
     * @param bool                         $hasBeenPersisted
     * @param array                        $results
     * @param array|null                   $related
     * @param array|null                   $data
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function __construct(
        string           $id,
        string           $bus,
        string           $process,
        string           $status,
        array            $handlers,
        ?int             $pid,
        int|string|null  $committedBy,
        CarbonImmutable  $committedAt,
        ?CarbonImmutable $startedAt = null,
        ?CarbonImmutable $finishedAt = null,
        array            $results = [],
        ?array           $related = null,
        ?array           $data = null,
        bool             $hasBeenPersisted = false
    ) {
        $this->hasBeenPersisted = $hasBeenPersisted;

        $this->id = $id;
        $this->bus = $bus;
        $this->process = $process;
        $this->status = self::verifyStatus($status);
        $this->handlers = array_values($handlers);
        $this->results = $results;
        $this->related = $related;
        $this->data = $data;
        $this->pid = $pid;
        $this->committedBy = $committedBy;
        $this->committedAt = $committedAt;
        $this->startedAt = $startedAt;
        $this->finishedAt = $finishedAt;
    }

    /**
     * @inheritDoc
     */
    public function applyData(mixed $data): void
    {
        if (is_null($data)) {
            return;
        }

        $this->data = array_merge($this->data ?? [], $this->arrayOpaque($data));
    }

    /**
     * @inheritDoc
     */
    public function applyHandlerResult(string $handler, string $status, HandlerResult $result): void
    {
        $this->applyResult($handler, $status, $result->getFeedback());
        $this->applyRelated($result->getRelated());
        $this->applyData($result->getData());
    }

    /**
     * @inheritDoc
     */
    public function applyRelated(array|string|null $related): void
    {
        if (is_null($related)) {
            return;
        }

        $this->related = array_merge($this->related ?? [], $this->arrayOpaque($related));
    }

    /**
     * @inheritDoc
     */
    public function applyResult(
        string  $handler,
        string  $status,
        ?string $feedback = null
    ): void {
        $status = self::verifySubStatus($status);

        if (!in_array($handler, $this->handlers)) {
            throw new MissingHandler(
                sprintf('Trying to apply results of unknown handler [%s]', $handler)
            );
        }

        $index = array_search($handler, $this->handlers);

        $this->results[$index] = array_merge(
            ['status' => $status],
            $feedback ? ['feedback' => $feedback] : []
        );
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function beenPersisted(): bool
    {
        return $this->hasBeenPersisted;
    }

    /**
     * @inheritDoc
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function cancel(): void
    {
        if ($this->isPending() || $this->isFinished()) {
            throw new InvalidAction(
                sprintf('Cannot cancel touched process [%s]', $this->id())
            );
        }

        $this->status = ProcessContract::CANCELED;
        $this->finishedAt = CarbonImmutable::now();
    }

    /**
     * Create new process.
     *
     * @param string   $busName
     * @param string   $process
     * @param array    $handlers
     * @param int|null $auth
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public static function create(
        string $busName,
        string $process,
        array  $handlers,
        ?int   $auth = null
    ): ProcessContract {
        if (empty($handlers)) {
            throw new InvalidAction('Cannot create process with no handlers');
        }

        if (array_keys($handlers) !== range(0, count($handlers) - 1) || !is_string($handlers[0])) {
            throw new InvalidArgumentException(
                'Unsupported format of handlers given. An array of Handlers classname required.'
            );
        }

        $process = new self(
            Str::orderedUuid()->toString(),
            $busName,
            $process,
            ProcessContract::NEW,
            $handlers,
            getmypid() ?: null,
            $auth,
            CarbonImmutable::now()
        );

        $process->registerHandlers();

        return $process;
    }

    /**
     * @inheritDoc
     */
    public function data(): ?array
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function finish(): void
    {
        if ($this->isFinished()) {
            throw new InvalidAction(
                sprintf('Trying to finish already finished process [%s].', $this->id())
            );
        }

        if (!$this->qualifyAsFinished()) {
            throw new InvalidAction(
                sprintf('Process [%s] cannot be finished. It\'s still pending.', $this->id)
            );
        }

        $this->status = ProcessContract::FINISHED;
        $this->finishedAt = CarbonImmutable::now();
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function isFinished(): bool
    {
        return $this->status === ProcessContract::FINISHED;
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function isPending(): bool
    {
        return $this->status === ProcessContract::PENDING;
    }

    /**
     * @inheritDoc
     */
    public function isSuccessful(): bool
    {
        if (!$this->isFinished()) {
            return false;
        }

        $aggregated = 0;
        foreach ($this->results as $r) {
            if ($r['status'] === ProcessContract::SUCCEED) {
                ++$aggregated;
            }
        }

        return $aggregated === count($this->handlers);
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @inheritDoc
     */
    public function markAsPersisted(): void
    {
        $this->hasBeenPersisted = true;
    }

    /**
     * @inheritDoc
     */
    public function qualifyAsFinished(): bool
    {
        $aggregated = 0;
        foreach ($this->results as $r) {
            if (in_array($r['status'], [ProcessContract::SUCCEED, ProcessContract::FAILED])) {
                ++$aggregated;
            }
        }

        return $aggregated === count($this->results);
    }

    /**
     * @inheritDoc
     */
    public function qualifyToStart(): bool
    {
        if (!in_array($this->status, [ProcessContract::NEW, ProcessContract::CANCELED])) {
            return false;
        }

        $aggregated = 0;
        foreach ($this->results as $r) {
            if ($r['status'] === ProcessContract::NEW) {
                ++$aggregated;
            }
        }

        return $aggregated === count($this->results);
    }

    /**
     * @inheritDoc
     */
    public function related(): ?array
    {
        return $this->related;
    }

    /**
     * @inheritDoc
     */
    public function resultOf(string $handler): array
    {
        if (!in_array($handler, $this->handlers)) {
            throw new MissingHandler(
                sprintf('This process doesn\'t contain handler [%s]', $handler)
            );
        }

        return $this->results[
            array_search($handler, $this->handlers)
        ];
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function results(): array
    {
        if (count($this->handlers) === 1) {
            return $this->results[0];
        }

        $toReturn = [];
        foreach ($this->results as $i => $r) {
            $toReturn[$this->handlers[$i]] = $r;
        }

        return  $toReturn;
    }

    /**
     * Mark process as started.
     *
     * @return void
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function start(): void
    {
        if ($this->isPending()) {
            throw new InvalidAction(
                sprintf('Process [%s] already started.', $this->id())
            );
        }

        if (!$this->qualifyToStart()) {
            throw new InvalidAction(
                sprintf('Process [%s] cannot be started.', $this->id)
            );
        }

        $this->status = ProcessContract::PENDING;
        $this->startedAt = CarbonImmutable::now();
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function status(): string
    {
        return $this->status;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'bus' => $this->bus,
            'process' => $this->process,
            'status' => $this->status,
            'handlers' => $this->handlers,
            'results' => $this->results,
            'related' => $this->related,
            'data' => $this->data,
            'pid' => $this->pid,
            'committedBy' => $this->committedBy,
            'committedAt' => $this->committedAt->getPreciseTimestamp(3),
            'startedAt' => $this->startedAt?->getPreciseTimestamp(3),
            'finishedAt' => $this->finishedAt?->getPreciseTimestamp(3)
        ];
    }

    /**
     * @inheritDoc
     */
    public function toDatabase(): array
    {
        $payload = [];
        foreach ($this->toArray() as $k => $v) {
            $key = Str::snake($k);

            if ($key === 'data') {
                $v = array_map(
                    fn($item) => is_array($item) ? $item : serialize($item),
                    $v ?? []
                );
            }

            $payload[$key] = $v;
            if (
                in_array($k, ['handlers', 'results', 'data', 'related']) &&
                !is_null($payload[$key])
            ) {
                $payload[$key] = json_encode($payload[$key]);
            }
        }

        return $payload;
    }

    /**
     * Create instance from database model.
     *
     * @param \stdClass $model
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public static function fromDatabase(stdClass $model): ProcessContract
    {
        $toCheck = ['committed_at', 'started_at', 'finished_at'];

        foreach ($toCheck as $f) {
            if ($model->{$f} !== null && ($model->{$f} >> 40) < 1) {
                throw new InvalidArgumentException(
                    sprintf('Model property [%s] requires micro-time precision.', $f)
                );
            }
        }

        if (!is_null($model->related)) {
            $model->related = json_decode($model->related, true);
        }

        if (!is_null($model->data)) {
            $data = json_decode($model->data, true);

            $model->data = array_map(
                fn($item) => is_array($item) ? $item : unserialize($item),
                $data ?? []
            );
        }

        return new self(
            $model->id,
            $model->bus,
            $model->process,
            $model->status,
            json_decode($model->handlers),
            $model->pid,
            $model->committed_by,
            CarbonImmutable::createFromTimestampMs($model->committed_at),
            $model->started_at ? CarbonImmutable::createFromTimestampMs($model->started_at) : null,
            $model->finished_at ? CarbonImmutable::createFromTimestampMs($model->finished_at) :
                null,
            json_decode($model->results, true),
            $model->related,
            $model->data,
            true
        );
    }

    /**
     * Filter unknown statuses.
     *
     * @param string $candidate
     * @return string
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public static function verifyStatus(string $candidate): string
    {
        if (!in_array(
            $candidate,
            [
                ProcessContract::NEW, ProcessContract::PENDING,
                ProcessContract::FINISHED, ProcessContract::CANCELED
            ]
        )) {
            throw new InvalidAction(
                sprintf('Trying to set unknown status [%s]', $candidate)
            );
        }

        return $candidate;
    }

    /**
     * Filter unknown statuses.
     *
     * @param string $candidate
     * @return string
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public static function verifySubStatus(string $candidate): string
    {
        if (!in_array(
            $candidate,
            [
                ProcessContract::NEW, ProcessContract::PENDING,
                ProcessContract::SUCCEED, ProcessContract::FAILED
            ]
        )) {
            throw new InvalidAction(
                sprintf('Trying to set unknown status [%s]', $candidate)
            );
        }

        return $candidate;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function arrayOpaque(mixed $toOpaque): array
    {
        return is_array($toOpaque) ? $toOpaque : [$toOpaque];
    }

    /**
     * @codeCoverageIgnore
     */
    protected function registerHandlers(): void
    {
        for ($i = 0; $i < count($this->handlers); $i++) {
            $this->results[$i] = ['status' => ProcessContract::NEW];
        }
    }
}
