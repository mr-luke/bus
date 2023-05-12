<?php

declare(strict_types=1);

namespace Mrluke\Bus;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonSerializable;
use Mrluke\Bus\Contracts\Process as ProcessContract;
use Mrluke\Bus\Exceptions\InvalidAction;
use Mrluke\Bus\Exceptions\MissingHandler;
use stdClass;

/**
 * Class Process
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@gmail.com>
 * @author  Krzysztof Ustowski <krzysztof.ustowski@movecloser.pl>
 * @version 1.1.0
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus
 */
class Process implements Arrayable, JsonSerializable, ProcessContract
{
    /**
     * @var string
     */
    private string $bus;

    /**
     * @var \Carbon\CarbonImmutable
     */
    private CarbonImmutable $committedAt;

    /**
     * @var string|int|null
     */
    private $committedBy;

    /**
     * @var \Carbon\CarbonImmutable|null
     */
    private ?CarbonImmutable $finishedAt;

    /**
     * @var int
     */
    private int $handlers;

    /**
     * @var string
     */
    private string $id;

    /**
     * @var int|null
     */
    private ?int $pid;

    /**
     * @var string
     */
    private string $process;

    /**
     * @var array
     */
    private array $results;

    /**
     * @var \Carbon\CarbonImmutable|null
     */
    private ?CarbonImmutable $startedAt;

    /**
     * @var string
     */
    private string $status;

    /**
     * List of related processes
     *
     * @var array|null
     */
    private ?array $related;

    /**
     * List of serialized HanderResult data
     *
     * @var array|null
     */
    private ?array $data;

    /**
     * @param string                       $id
     * @param string                       $bus
     * @param string                       $process
     * @param string                       $status
     * @param int                          $handlers
     * @param array                        $results
     * @param array|null                   $related
     * @param array|null                   $data
     * @param int|null                     $pid
     * @param string|int|null              $committedBy
     * @param \Carbon\CarbonImmutable      $committedAt
     * @param \Carbon\CarbonImmutable|null $startedAt
     * @param \Carbon\CarbonImmutable|null $finishedAt
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function __construct(
        string $id,
        string $bus,
        string $process,
        string $status,
        int $handlers,
        array $results,
        ?array $related,
        ?array $data,
        ?int $pid,
        $committedBy,
        CarbonImmutable $committedAt,
        ?CarbonImmutable $startedAt = null,
        ?CarbonImmutable $finishedAt = null
    ) {
        $this->id          = $id;
        $this->bus         = $bus;
        $this->process     = $process;
        $this->status      = self::verifyStatus($status);
        $this->handlers    = $handlers;
        $this->results     = $results;
        $this->related     = $related;
        $this->data        = $data;
        $this->pid         = $pid;
        $this->committedBy = $committedBy;
        $this->committedAt = $committedAt;
        $this->startedAt   = $startedAt;
        $this->finishedAt  = $finishedAt;
    }

    /**
     * Apply handler data object to process.
     *
     * @param mixed|null $data
     * @return array|null
     */
    public function applyData($data): ?array
    {
        if (is_null($data)) {
            return $this->data;
        }

        if (is_null($this->data)) {
            $this->data = [$data];
        } else {
            $this->data = array_merge($this->data, [$data]);
        }
        return $this->data;
    }

    /**
     * Apply result for given handler.
     *
     * @param array|null $related
     * @return array|null
     */
    public function applyRelated(?array $related): ?array
    {
        if (is_null($related)) {
            return $this->related;
        }

        if (is_null($this->related)) {
            $this->related = $related;
        } else {
            $this->related = array_merge($this->related, $related);
        }

        return $this->related;
    }

    /**
     * Apply result for given handler.
     *
     * @param string      $handler
     * @param string      $status
     * @param string|null $feedback
     * @return array
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     */
    public function applyResult(
        string $handler,
        string $status,
        ?string $feedback = null
    ): array {
        $status = self::verifySubStatus($status);

        if (!array_key_exists($handler, $this->results)) {
            throw new MissingHandler(
                sprintf('Trying to apply results of unknown handler [%s]', $handler)
            );
        }

        $this->results[$handler] = array_merge(
            ['status' => $status],
            $feedback ? ['feedback' => $feedback] : []
        );

        return $this->results;
    }

    /**
     * Mark process as canceled.
     *
     * @return int
     */
    public function cancel(): int
    {
        $this->status     = ProcessContract::Canceled;
        $this->finishedAt = CarbonImmutable::now();

        return (int)$this->finishedAt->valueOf();
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
        array $handlers,
        ?int $auth
    ): ProcessContract {
        if (array_keys($handlers) !== range(0, count($handlers) - 1) || !is_string($handlers[0])) {
            throw new InvalidArgumentException(
                'Unsupported format of handlers given. An array of Handlers class required.'
            );
        }

        $id  = Str::uuid()->toString();
        $pid = getmypid() ? getmypid() : null;

        $results = [];
        foreach ($handlers as $h) {
            $results[$h] = ['status' => ProcessContract::New];
        }

        return new self(
            $id,
            $busName,
            $process,
            ProcessContract::New,
            count($handlers),
            $results,
            null,
            null,
            $pid,
            $auth,
            CarbonImmutable::now()
        );
    }

    /**
     * @return array|null
     */
    public function data(): ?array
    {
        return $this->data;
    }

    /**
     * Mark process as finished.
     *
     * @return int
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function finish(): int
    {
        if (!$this->qualifyAsFinished()) {
            throw new InvalidAction(
                sprintf('Process [%s] cannot be finished. It\'s still pending.', $this->id)
            );
        }

        $this->status     = ProcessContract::Finished;
        $this->finishedAt = CarbonImmutable::now();

        return (int)$this->finishedAt->valueOf();
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
                    sprintf('Model property [%s] requires microtime precision.', $f)
                );
            }
        }

        $model->related = !is_null($model->related) ?
            json_decode($model->related, true) : $model->related;

        $model->data = !is_null($model->data) ?
            json_decode($model->data, true) : $model->data;

        $model->data = is_array($model->data) ?
            array_filter(
                $model->data,
                function($item) {
                    return unserialize($item);
                }
            ) : $model->data;

        return new self(
            $model->id,
            $model->bus,
            $model->process,
            $model->status,
            (int)$model->handlers,
            json_decode($model->results, true),
            $model->related,
            $model->data,
            $model->pid,
            $model->committed_by,
            CarbonImmutable::createFromTimestampMs($model->committed_at),
            $model->started_at ? CarbonImmutable::createFromTimestampMs($model->started_at) : null,
            $model->finished_at ? CarbonImmutable::createFromTimestampMs($model->finished_at) : null
        );
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
     * Determine if process is already finished.
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isFinished(): bool
    {
        return $this->status === ProcessContract::Finished;
    }

    /**
     * Determine if process is pending.
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isPending(): bool
    {
        return $this->status === ProcessContract::Pending;
    }

    /**
     * Determine if the result of process is success.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        if (!$this->isFinished()) {
            return false;
        }

        $aggregated = 0;
        foreach ($this->results as $h => $r) {
            if ($r['status'] === ProcessContract::Succeed) {
                ++$aggregated;
            }
        }

        return $aggregated === count($this->results);
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
    public function qualifyAsFinished(): bool
    {
        $aggregated = 0;
        foreach ($this->results as $h => $r) {
            if (in_array($r['status'], [ProcessContract::Succeed, ProcessContract::Failed])) {
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
        if (!in_array($this->status, [ProcessContract::New, ProcessContract::Canceled])) {
            return false;
        }

        $aggregated = 0;
        foreach ($this->results as $h => $r) {
            if ($r['status'] === ProcessContract::New) {
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
        if (!array_key_exists($handler, $this->results)) {
            throw new MissingHandler(
                sprintf('This process doesn\'t contain handler [%s]', $handler)
            );
        }

        return $this->results[$handler];
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function results(): array
    {
        return $this->results;
    }

    /**
     * Mark process as started.
     *
     * @return int
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function start(): int
    {
        if (!$this->qualifyToStart()) {
            throw new InvalidAction(
                sprintf('Process [%s] cannot be started.', $this->id)
            );
        }

        $this->status    = ProcessContract::Pending;
        $this->startedAt = CarbonImmutable::now();

        return (int)$this->startedAt->valueOf();
    }

    /**
     * Return actual status of the process.
     *
     * @return string
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
            'id'          => $this->id,
            'bus'         => $this->bus,
            'process'     => $this->process,
            'status'      => $this->status,
            'handlers'    => $this->handlers,
            'results'     => $this->results,
            'related'     => $this->related,
            'data'        => $this->data,
            'pid'         => $this->pid,
            'committedBy' => $this->committedBy,
            'committedAt' => $this->committedAt->getPreciseTimestamp(3),
            'startedAt'   => $this->startedAt ? $this->startedAt->getPreciseTimestamp(3) : null,
            'finishedAt'  => $this->finishedAt ? $this->finishedAt->getPreciseTimestamp(3) : null
        ];
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
                ProcessContract::New, ProcessContract::Pending,
                ProcessContract::Finished, ProcessContract::Canceled
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
                ProcessContract::New, ProcessContract::Pending,
                ProcessContract::Succeed, ProcessContract::Failed
            ]
        )) {
            throw new InvalidAction(
                sprintf('Trying to set unknown status [%s]', $candidate)
            );
        }

        return $candidate;
    }
}
