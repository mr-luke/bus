<?php

namespace Mrluke\Bus\Extensions;

use Mrluke\Bus\Contracts\HandlerResult;

/**
 * Class TranslateResults
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @author  Krzysztof Ustowski <krzysztof.ustowski@movecloser.pl>
 * @version 1.1.0
 * @licence MIT
 * @link    https://github.com/mr-luke/bus
 * @package Mrluke\Bus\Extensions
 */
trait TranslateResults
{
    /**
     * Process result of Handler.
     *
     * @param $mixedResults
     * @return HandlerResult
     * @codeCoverageIgnore
     */
    protected function processResult($mixedResults): HandlerResult
    {
        if ($mixedResults instanceof HandlerResult) {
            return $mixedResults;
        }

        if (is_array($mixedResults)) {
            return new \Mrluke\Bus\HandlerResult(
                json_encode($mixedResults)
            );
        }

        if (is_bool($mixedResults) || is_numeric($mixedResults) || is_string($mixedResults)) {
            return new \Mrluke\Bus\HandlerResult(
                (string)$mixedResults
            );
        }

        return new \Mrluke\Bus\HandlerResult();
    }
}
