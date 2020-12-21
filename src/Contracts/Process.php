<?php

declare(strict_types=1);

namespace Mrluke\Bus\Contracts;

use stdClass;

/**
 * Process data model.
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@gmail.com>
 * @version 1.0.0
 * @licence MIT
 * @link     https://github.com/mr-luke/bus
 * @package Mrluke\Bus\Contracts
 */
interface Process
{
    const Canceled = 'canceled';

    const Finished = 'finished';

    const Failed = 'failed';

    const New = 'new';

    const Pending = 'pending';

    const Succeed = 'succeed';

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
    ): array;

    /**
     * Mark process as canceled.
     *
     * @return int
     */
    public function cancel(): int;

    /**
     * Create new process.
     *
     * @param string $busName
     * @param string $process
     * @param array  $handlers
     * @param int    $auth
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public static function create(
        string $busName,
        string $process,
        array $handlers,
        int $auth
    ): Process;

    /**
     * Return id of process.
     *
     * @return string
     */
    public function id(): string;

    /**
     * Determine if process is already finished.
     *
     * @return bool
     */
    public function isFinished(): bool;

    /**
     * Determine if process is pending.
     *
     * @return bool
     */
    public function isPending(): bool;

    /**
     * Mark process as finished.
     *
     * @return int
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function finish(): int;

    /**
     * Create instance from database model.
     *
     * @param \stdClass $model
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public static function fromDatabase(stdClass $model): Process;

    /**
     * Determine if process can be marked as finished.
     *
     * @return bool
     */
    public function qualifyAsFinished(): bool;

    /**
     * Determine if process can be started.
     *
     * @return bool
     */
    public function qualifyToStart(): bool;

    /**
     * Return result of given process.
     *
     * @param string $handler
     * @return array
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     */
    public function resultOf(string $handler): array;

    /**
     * Return results of Process.
     *
     * @return array
     */
    public function results(): array;

    /**
     * Mark process as started.
     *
     * @return int
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function start(): int;

    /**
     * Return actual status of the process.
     *
     * @return string
     */
    public function status(): string;

    /**
     * Filter unknown statuses.
     *
     * @param string $candidate
     * @return string
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public static function verifyStatus(string $candidate): string;

    /**
     * Filter unknown statuses.
     *
     * @param string $candidate
     * @return string
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public static function verifySubStatus(string $candidate): string;
}
