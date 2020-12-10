<?php

namespace Mrluke\Bus;

use Mrluke\Bus\Contracts\Process;
use Mrluke\Bus\Contracts\ProcessRepository;

class DatabaseProcessRepository implements ProcessRepository
{

    /**
     * @inheritDoc
     */
    public function applySubResult(string $processId, array $result): Process
    {
        // TODO: Implement applySubResult() method.
    }

    /**
     * @inheritDoc
     */
    public function cancelProcess(string $processId): Process
    {
        // TODO: Implement cancelProcess() method.
    }

    /**
     * @inheritDoc
     */
    public function countProcesses(string $status = null): int
    {
        // TODO: Implement countProcesses() method.
    }

    /**
     * @inheritDoc
     */
    public function create(string $busName, string $process, array $handlers): Process
    {
        // TODO: Implement create() method.
    }

    /**
     * @inheritDoc
     */
    public function find(string $processId): Process
    {
        // TODO: Implement find() method.
    }

    /**
     * @inheritDoc
     */
    public function markAsFailed(string $processId): Process
    {
        // TODO: Implement markAsFailed() method.
    }

    /**
     * @inheritDoc
     */
    public function markAsSucceed(string $processId): Process
    {
        // TODO: Implement markAsSucceed() method.
    }
}
