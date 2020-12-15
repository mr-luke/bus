<?php

declare(strict_types=1);

namespace Mrluke\Bus;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonSerializable;
use stdClass;

use Mrluke\Bus\Contracts\Process as ProcessContract;
use Mrluke\Bus\Exceptions\InvalidAction;
use Mrluke\Bus\Exceptions\MissingHandler;

/**
 * Class Process
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus
 */
class Process implements Arrayable, JsonSerializable, ProcessContract
{
    /**
     * @var string
     */
    private $bus;

    /**
     * @var \Carbon\CarbonImmutable
     */
    private $committedAt;

    /**
     * @var int|null
     */
    private $committedBy;

    /**
     * @var \Carbon\Carbon|null
     */
    private $finishedAt;

    /**
     * @var int
     */
    private $handlers;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $process;

    /**
     * @var \Carbon\Carbon|null
     */
    private $startedAt;

    /**
     * @var string
     */
    private $status;

    /**
     * @var array
     */
    private $results;

    /**
     * @param string                       $id
     * @param string                       $bus
     * @param string                       $process
     * @param string                       $status
     * @param int                          $handlers
     * @param array                        $results
     * @param int|null                     $committedBy
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
        ?int $committedBy,
        CarbonImmutable $committedAt,
        ?CarbonImmutable $startedAt = null,
        ?CarbonImmutable $finishedAt = null
    ) {
        $this->id = $id;
        $this->bus = $bus;
        $this->process = $process;
        $this->status = self::verifyStatus($status);
        $this->handlers = $handlers;
        $this->results = $results;
        $this->committedBy = $committedBy;
        $this->committedAt = $committedAt;
        $this->startedAt = $startedAt;
        $this->finishedAt = $finishedAt;
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
        $this->status = ProcessContract::Canceled;
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

        $id = Str::uuid()->toString();

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
            $auth,
            CarbonImmutable::now()
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

        $this->status = ProcessContract::Finished;
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

        return new self(
            $model->id,
            $model->bus,
            $model->process,
            $model->status,
            (int)$model->handlers,
            json_decode($model->results, true),
            $model->committed_by,
            CarbonImmutable::createFromTimestampMs($model->committed_at),
            $model->started_at ? CarbonImmutable::createFromTimestampMs($model->started_at) : null,
            $model->finished_at ? CarbonImmutable::createFromTimestampMs($model->finished_at) : null
        );
    }

    /**
     * Determine if process can be marked as finished.
     *
     * @return bool
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
     * Determine if process can be started.
     *
     * @return bool
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

        $this->status = ProcessContract::Pending;
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
            'committedBy' => $this->committedBy,
            'committedAt' => (int)$this->committedAt->valueOf(),
            'startedAt'   => $this->startedAt ? (int)$this->startedAt->valueOf() : null,
            'finishedAt'  => $this->finishedAt ? (int)$this->finishedAt->valueOf() : null
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
