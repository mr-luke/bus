<?php

declare(strict_types=1);

namespace Mrluke\Bus;

use Mrluke\Bus\Contracts\HandlerResult as Contract;

/**
 * @author  Krzysztof Ustowski <krzysztof.ustowski@movecloser.pl>
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus
 */
class HandlerResult implements Contract
{
    /**
     * HandlerResult constructor.
     *
     * @param string|null $feedback
     * @param mixed|null  $data
     * @param array|null  $related
     */
    public function __construct(
        protected readonly ?string $feedback = null,
        protected readonly mixed   $data = null,
        protected readonly ?array  $related = null
    ) {
        //
    }

    /**
     * @inheritDoc
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function getFeedback(): ?string
    {
        return $this->feedback;
    }

    /**
     * @inheritDoc
     */
    public function getRelated(): ?array
    {
        return $this->related;
    }
}
