<?php

declare(strict_types=1);

namespace Mrluke\Bus\Contracts;

use stdClass;

/**
 * Process data model.
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@gmail.com>
 * @author  Krzysztof Ustowski <krzysztof.ustowski@movecloser.pl>
 * @licence MIT
 * @link     https://github.com/mr-luke/bus
 * @package Mrluke\Bus\Contracts
 */
interface Process
{
    const CANCELED = 'canceled';

    const FINISHED = 'finished';

    const FAILED = 'failed';

    const NEW = 'new';

    const PENDING = 'pending';

    const SUCCEED = 'succeed';

    /**
     * Apply handler data object to process.
     *
     * @param mixed|null $data
     * @return void
     */
    public function applyData(mixed $data): void;

    /**
     * Apply result for given handler.
     *
     * @param string                              $handler
     * @param string                              $status
     * @param \Mrluke\Bus\Contracts\HandlerResult $result
     * @return void
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     */
    public function applyHandlerResult(
        string        $handler,
        string        $status,
        HandlerResult $result
    ): void;

    /**
     * Apply related processes to process.
     *
     * @param array|string|null $related
     * @return void
     */
    public function applyRelated(array|string|null $related): void;

    /**
     * Apply result for given handler.
     *
     * @param string      $handler
     * @param string      $status
     * @param string|null $feedback
     * @return void
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     */
    public function applyResult(
        string  $handler,
        string  $status,
        ?string $feedback = null
    ): void;

    /**
     * Determine if persistence occurred.
     *
     * @return bool
     */
    public function beenPersisted(): bool;

    /**
     * Mark process as canceled.
     *
     * @return void
     */
    public function cancel(): void;

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
        array  $handlers,
        int    $auth
    ): Process;

    /**
     * Mark process as finished.
     *
     * @return void
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function finish(): void;

    /**
     * Create instance from database model.
     *
     * @param \stdClass $model
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public static function fromDatabase(stdClass $model): Process;

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
     * Determine if process is finished with success.
     *
     * @param string|null $handler
     * @return bool
     * @throws \Mrluke\Bus\Exceptions\MissingHandler
     */
    public function isSuccessful(string $handler = null): bool;

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
     * Return list of related Processes.
     *
     * @return array|null
     */
    public function related(): ?array;

    /**
     * Return list of data from handlers.
     *
     * @return array|null
     */
    public function data(): ?array;

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
     * @return void
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function start(): void;

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
