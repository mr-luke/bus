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
     * @var array
     */
    protected array $related;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var string
     */
    protected string $feedback;

    /**
     * HandlerResult constructor.
     *
     * @param string        $feedback
     * @param \Serializable $data
     * @param array         $related
     */
    public function __construct(string $feedback, \Serializable $data, array $related = [])
    {
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
    public function getRelated(): array
    {
        return $this->related;
    }

    /**
     * @inheritDoc
     */
    public function getFeedback(): string
    {
        return $this->feedback;
    }
}
