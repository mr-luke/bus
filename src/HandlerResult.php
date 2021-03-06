<?php

declare(strict_types=1);

namespace Mrluke\Bus;

use Mrluke\Bus\Contracts\HandlerResult as Contract;

/**
 * Database implementation of ProcessRepository
 *
 * @author  Krzysztof Ustowski <krzysztof.ustowski@movecloser.pl>
 * @version 1.0.0
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus
 */
class HandlerResult implements Contract
{

    /**
     * @var array|null
     */
    protected ?array $related;

    /**
     * @var mixed|null
     */
    protected $data;

    /**
     * @var string|null
     */
    protected ?string $feedback;

    /**
     * HandlerResult constructor.
     *
     * @param string|null    $feedback
     * @param mixed|null $data
     * @param array|null     $related
     */
    public function __construct(
        ?string $feedback = null,
        $data = null,
        ?array $related = null
    ) {
        $this->data     = $data;
        $this->related  = $related;
        $this->feedback = $feedback;
    }

    /**
     * @inheritDoc
     */
    public function getData()
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
