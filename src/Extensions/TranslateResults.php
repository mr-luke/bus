<?php

namespace Mrluke\Bus\Extensions;

/**
 * Class TranslateResults
 *
 * @author  Łukasz Sitnicki <lukasz.sitnicki@movecloser.pl>
 * @version 1.0.0
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
     * @return string|null
     * @codeCoverageIgnore
     */
    protected function processResult($mixedResults): ?string
    {
        if (is_array($mixedResults)) {
            return json_encode($mixedResults);
        }

        if (is_bool($mixedResults) || is_numeric($mixedResults) || is_string($mixedResults)) {
            return (string) $mixedResults;
        }

        return null;
    }
}
