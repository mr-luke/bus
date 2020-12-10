<?php

declare(strict_types=1);

namespace Mrluke\Bus\Contracts;

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

    const Failed = 'failed';

    const New = 'new';

    const Pending = 'pending';

    const Succeed = 'succeed';
}
