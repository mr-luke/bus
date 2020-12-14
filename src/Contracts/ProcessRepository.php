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
     * @param string      $processId
     * @param string      $handler
     * @param string      $status
     * @param string|null $feedback
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     * @throws \Mrluke\Bus\Exceptions\MissingProcess
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function applySubResult(
        string $processId,
        string $handler,
        string $status,
        string $feedback = null
    ): Process;

    /**
     * Cancel process if possible.
     *
     * @param string $processId
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\MissingProcess
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function cancel(string $processId): Process;

    /**
     * Count processes by given status.
     *
     * @param string|null $status
     * @return int
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function count(string $status = null): int;

    /**
     * Crete new process that should be watch.
     *
     * @param string $busName
     * @param string $process
     * @param array  $handlers
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function create(string $busName, string $process, array $handlers): Process;

    /**
     * Delete process by given id.
     *
     * @param string $processId
     * @return void
     */
    public function delete(string $processId): void;

    /**
     * Retrieve process by given id.
     *
     * @param string $processId
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\MissingProcess
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function find(string $processId): Process;

    /**
     * Mark process as finished.
     *
     * @param string $processId
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\MissingProcess
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function finish(string $processId): Process;

    /**
     * Start process of given id.
     *
     * @param string $processId
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     * @throws \Mrluke\Bus\Exceptions\MissingProcess
     */
    public function start(string $processId): Process;
}
