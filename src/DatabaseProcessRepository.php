<?php

declare(strict_types=1);

namespace Mrluke\Bus;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Mrluke\Bus\Contracts\InteractsWithRepository;
use Mrluke\Bus\Contracts\Process;
use Mrluke\Bus\Contracts\ProcessRepository;
use Mrluke\Bus\Exceptions\MissingProcess;
use Mrluke\Bus\Exceptions\RuntimeException;
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
    public function delete(Process|string $processId): void
    {
        $this->getBuilder()->delete(
            is_string($processId) ? $processId : $processId->id()
        );
    }

    /**
     * @inheritDoc
     */
    public function persist(InteractsWithRepository $process): void
    {
        $process->beenPersisted()
            ? $this->updateProcess($process)
            : $this->storeProcess($process);
    }

    /**
     * @inheritDoc
     */
    public function retrieve(string $processId): Process
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
     * @throws \Mrluke\Bus\Exceptions\RuntimeException
     */
    protected function storeProcess(InteractsWithRepository $process): void
    {
        $payload = $process->toDatabase();

        if (!$this->getBuilder()->insert($payload)) {
            throw new RuntimeException('Creating new process failed.');
        }

        $process->markAsPersisted();
    }

    /**
     * @throws \Mrluke\Bus\Exceptions\RuntimeException
     */
    protected function updateProcess(InteractsWithRepository $process): void
    {
        $payload = $process->toDatabase();

        $toTrim = ['bus', 'committed_at', 'committed_by', 'handlers', 'id', 'pid', 'process'];
        foreach ($toTrim as $key) {
            unset($payload[$key]);
        }

        if(!$this->getBuilder()->where('id', $process->id())->update($payload)) {
            throw new RuntimeException('Updating the process failed.');
        }
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
