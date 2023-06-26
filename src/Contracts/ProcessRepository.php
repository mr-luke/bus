<?php

declare(strict_types=1);

namespace Mrluke\Bus\Contracts;

/**
 * Interface ProcessRepository
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@gmail.com>
 * @author  Krzysztof Ustowski <krzysztof.ustowski@movecloser.pl>
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus\Contracts
 */
interface ProcessRepository
{
    /**
     * Count processes by given status.
     *
     * @param string|null $status
     * @return int
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function count(string $status = null): int;

    /**
     * Delete process by given id.
     *
     * @param \Mrluke\Bus\Contracts\Process|string $processId
     * @return void
     */
    public function delete(Process|string $processId): void;

    /**
     * Persist any changes on Process.
     *
     * @param \Mrluke\Bus\Contracts\InteractsWithRepository $process
     * @return void
     * @throws \Mrluke\Bus\Exceptions\RuntimeException
     */
    public function persist(InteractsWithRepository $process): void;

    /**
     * Retrieve process by given id.
     *
     * @param string $processId
     * @return \Mrluke\Bus\Contracts\Process
     * @throws \Mrluke\Bus\Exceptions\MissingProcess
     * @throws \Mrluke\Bus\Exceptions\InvalidAction
     */
    public function retrieve(string $processId): Process;
}
