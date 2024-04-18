<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Validation;

use BlitzPHP\Utilities\String\Text;
use Dimtrovich\Validation\Rule as DimtrovichRule;

/**
 * {@inheritDoc}
 *
 * @method static \BlitzPHP\Database\Validation\Rules\Exists exists(string $table, ?string $column = null)
 * @method static \BlitzPHP\Database\Validation\Rules\Unique unique(string $table, ?string $column = null, mixed $ignore = null)
 */
abstract class Rule extends DimtrovichRule
{
    public static function __callStatic(string $name, array $arguments = [])
    {
        $name = Text::snake($name);

        return Validator::rule($name, ...$arguments);
    }
}
