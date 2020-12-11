<?php

declare(strict_types=1);

namespace Mrluke\Bus;

use Exception;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Mrluke\Configuration\Contracts\ArrayHost;

use Mrluke\Bus\Contracts\Process;
use Mrluke\Bus\Contracts\ProcessRepository;
use Mrluke\Bus\Exceptions\InvalidAction;
use Mrluke\Bus\Exceptions\MissingProcess;

/**
 * Database implementation of ProcessRepository
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
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
    protected $config;

    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\Connection
     */
    protected $connection;

    /**
     * @var \Illuminate\Contracts\Auth\Guard
     */
    protected $guard;

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
        string $processId,
        string $handler,
        string $status,
        string $feedback = null
    ): Process {
        $process = $this->find($processId);

        $this->getBuilder()->where('id', $processId)->update(
            [
                'results' => $process->applyResult($handler, $status, $feedback),
                'status'  => $process->status()
            ]
        );

        return $process;
    }

    /**
     * @inheritDoc
     */
    public function cancel(string $processId): Process
    {
        $process = $this->find($processId);

        if ($process->isPending() || $process->isFinished()) {
            throw new InvalidAction(
                sprintf('Cannot cancel touched process [%s]', $processId)
            );
        }

        $this->getBuilder()->where('id', $processId)->update(
            [
                'finished_at' => $process->cancel(),
                'status'      => $process->status()
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

        $process = \Mrluke\Bus\Process::create($busName, $process, $handlers, $this->guard->id());

        $payload = $process->toArray();
        $payload['results'] = json_encode($payload['results']);

        if (!$this->getBuilder()->insert($payload)) {
            throw new Exception('Creating new process failed.');
        }

        return $process;
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
    public function finish(string $processId): Process
    {
        $process = $this->find($processId);

        if ($process->isFinished()) {
            throw new InvalidAction(
                sprintf('Trying to finish already finished process [%s].', $processId)
            );
        }

        $this->getBuilder()->where('id', $processId)->update(
            [
                'finished_at' => $process->finish(),
                'status'      => $process->status()
            ]
        );

        return $process;
    }

    /**
     * @inheritDoc
     */
    public function start(string $processId): Process
    {
        $process = $this->find($processId);

        if ($process->isPending()) {
            throw new InvalidAction(
                sprintf('Process [%s] already started.', $processId)
            );
        }

        $this->getBuilder()->where('id', $processId)->update(
            [
                'started_at' => $process->start(),
                'status'     => $process->status()
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
