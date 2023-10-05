<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Container;

abstract class AbstractProvider
{
    public function __construct(protected Container $container)
    {
    }

    public static function definitions(): array
    {
        // a implementer par les classes filles

        return [];
    }

    public function register(): void
    {
        // à implementer par les classes filles
    }
}
