<?php

declare(strict_types=1);

namespace Mrluke\Bus\Contracts;

/**
 * Interface ProcessRepository
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus\Contracts
 */
interface ProcessRepository
{
    /**
     * Apply sub-result to process.
     *
     * @param  string $processId
     * @param  array  $result
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\MissingProcess
     */
    public function applySubResult(string $processId, array $result): Process;

    /**
     * Cancel process if possible.
     *
     * @param  string $processId
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function cancelProcess(string $processId): Process;

    /**
     * Count processes by given status.
     *
     * @param  string|null $status
     * @return int
     */
    public function countProcesses(string $status = null): int;

    /**
     * Crete new process that should be watch.
     *
     * @param  string $busName
     * @param  string $process
     * @param  array  $handlers
     * @return \Mrluke\Bus\Contracts\Process
     */
    public function create(string $busName, string $process, array $handlers): Process;

    /**
     * Retrieve process by given id.
     *
     * @param  string $processId
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\MissingProcess
     */
    public function find(string $processId): Process;

    /**
     * Mark process as failed.
     *
     * @param  string $processId
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\MissingProcess
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function markAsFailed(string $processId): Process;

    /**
     * Mark process as succeed.
     *
     * @param  string $processId
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\MissingProcess
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function markAsSucceed(string $processId): Process;
}
