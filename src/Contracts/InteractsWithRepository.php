<?php

namespace Mrluke\Bus\Contracts;

use stdClass;

interface InteractsWithRepository
{
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
     * Determine if persistence occurred.
     *
     * @return bool
     */
    public function beenPersisted(): bool;

    /**
     * Mark process of persisted.
     *
     * @return void
     */
    public function markAsPersisted(): void;

    /**
     * Normalize data for persistence.
     *
     * @return array
     */
    public function toDatabase(): array;
}
