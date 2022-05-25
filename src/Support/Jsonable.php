<?php

/**
 * This file is part of Blitz PHP framework - Contracts.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Contracts\Support;

interface Jsonable
{
    /**
     * Convert the object to its JSON representation.
     */
    public function toJson(int $options = 0): string;
}
