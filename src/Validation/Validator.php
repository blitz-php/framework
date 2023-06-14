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

use Dimtrovich\Validation\Validator as BaseValidator;

class Validator extends BaseValidator
{
    /**
     * {@inheritDoc}
     */
    protected static string $validationClass = Validation::class;

    public static function validate(array $data, array $rules, array $messages = []): array
    {
        return static::make($data, $rules, $messages)->validate();
    }
}
