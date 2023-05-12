<?php

declare(strict_types=1);

namespace Mrluke\Bus;

use Exception;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use Mrluke\Bus\Contracts\HandlerResult;
use Mrluke\Bus\Contracts\Process;
use Mrluke\Bus\Contracts\ProcessRepository;
use Mrluke\Bus\Exceptions\InvalidAction;
use Mrluke\Bus\Exceptions\MissingProcess;
use Mrluke\Configuration\Contracts\ArrayHost;

/**
 * Database implementation of ProcessRepository
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@gmail.com>
 * @author  Krzysztof Ustowski <krzysztof.ustowski@movecloser.pl>
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus
 */
class DatabaseProcessRepository implements ProcessRepository
{
    /**
     * Package config instance.
     *
     * @var \Mrluke\Configuration\Contracts\ArrayHost
     */
    protected ArrayHost $config;

    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\Connection
     */
    protected Connection $connection;

    /**
     * @var \Illuminate\Contracts\Auth\Guard
     */
    protected Guard $guard;

    /**
     * @param \Mrluke\Configuration\Contracts\ArrayHost $config
     * @param \Illuminate\Database\Connection           $connection
     * @param \Illuminate\Contracts\Auth\Guard          $guard
     */
    public function __construct(ArrayHost $config, Connection $connection, Guard $guard)
    {
        $this->config = $config;
        $this->connection = $connection;
        $this->guard = $guard;
    }

    /**
     * @inheritDoc
     */
    public function applySubResult(
        Process|string $processId,
        string         $handler,
        string         $status,
        HandlerResult  $result
    ): Process {
        $process = is_string($processId) ? $this->find($processId) : $processId;

        $payload = [
            'results' => $process->applyResult($handler, $status, $result->getFeedback()),
            'related' => $process->applyRelated($result->getRelated()),
            'data' => $process->applyData($result->getData()),
            'status' => $process->status()
        ];

        if (!is_null($payload['data'])) {
            $payload['data'] = array_filter($payload['data'], function($item) {
                return serialize($item);
            });
        }

        foreach ($payload as $k => $v) {
            if (in_array($k, ['results', 'data', 'related']) && !is_null($v)) {
                $payload[$k] = json_encode($v);
            }
        }

        $this->getBuilder()->where('id', $process->id())->update($payload);

        return $process;
    }

    /**
     * @inheritDoc
     */
    public function cancel(Process|string $processId): Process
    {
        $process = is_string($processId) ? $this->find($processId) : $processId;

        if ($process->isPending() || $process->isFinished()) {
            throw new InvalidAction(
                sprintf('Cannot cancel touched process [%s]', $process->id())
            );
        }

        $this->getBuilder()->where('id', $process->id())->update(
            [
                'finished_at' => $process->cancel(),
                'status' => $process->status()
            ]
        );

        return $process;
    }

    /**
     * @inheritDoc
     */
    public function count(string $status = null): int
    {
        $query = $this->getBuilder();

        if ($status) {
            $query->where('status', \Mrluke\Bus\Process::verifyStatus($status));
        }

        return $query->count();
    }

    /**
     * @inheritDoc
     */
    public function create(string $busName, string $process, array $handlers): Process
    {
        if (count($handlers) === 0) {
            throw new InvalidAction('Cannot create process with no handlers');
        }

        $process = \Mrluke\Bus\Process::create(
            $busName,
            $process,
            $handlers,
            $this->guard->id()
        );

        $payload = [];
        foreach ($process->toArray() as $k => $v) {
            $payload[Str::snake($k)] = $v;

            if (in_array($k, ['results', 'data', 'related']) && !is_null($payload[$k])) {
                $payload[$k] = json_encode($payload[$k]);
            }
        }


        if (!$this->getBuilder()->insert($payload)) {
            throw new Exception('Creating new process failed.');
        }

        return $process;
    }

    /**
     * @inheritDoc
     */
    public function delete(Process|string $processId): void
    {
        $this->getBuilder()->delete(
            is_string($processId) ? $processId : $processId->id()
        );
    }

    /**
     * @inheritDoc
     */
    public function find(string $processId): Process
    {
        $model = $this->getBuilder()->find($processId);

        if (!$model) {
            throw new MissingProcess(
                sprintf('Cannot find process of id [%s]', $processId)
            );
        }

        return \Mrluke\Bus\Process::fromDatabase($model);
    }

    /**
     * @inheritDoc
     */
    public function finish(Process|string $processId): Process
    {
        $process = is_string($processId) ? $this->find($processId) : $processId;

        if ($process->isFinished()) {
            throw new InvalidAction(
                sprintf('Trying to finish already finished process [%s].', $process->id())
            );
        }

        $this->getBuilder()->where('id', $process->id())->update(
            [
                'finished_at' => $process->finish(),
                'status' => $process->status()
            ]
        );

        return $process;
    }

    /**
     * @inheritDoc
     */
    public function start(Process|string $processId): Process
    {
        $process = is_string($processId) ? $this->find($processId) : $processId;

        if ($process->isPending()) {
            throw new InvalidAction(
                sprintf('Process [%s] already started.', $process->id())
            );
        }

        $this->getBuilder()->where('id', $process->id())->update(
            [
                'started_at' => $process->start(),
                'status' => $process->status()
            ]
        );

        return $process;
    }

    /**
     * Return builder with correct table set.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    private function getBuilder(): Builder
    {
        return $this->connection->table($this->config->get('table'));
    }
}
