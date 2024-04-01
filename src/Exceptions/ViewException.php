<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Exceptions;

class ViewException extends FrameworkException
{
    public static function invalidComponentMethod(string $class, string $method)
    {
        return new static(lang('View.invalidComponentMethod', ['class' => $class, 'method' => $method]));
    }

    public static function missingComponentParameters(string $class, string $method)
    {
        return new static(lang('View.missingComponentParameters', ['class' => $class, 'method' => $method]));
    }

    public static function invalidComponentParameter(string $key)
    {
        return new static(lang('View.invalidComponentParameter', [$key]));
    }

    public static function noComponentClass()
    {
        return new static(lang('View.noComponentClass'));
    }

    public static function invalidComponentClass(?string $class = null)
    {
        return new static(lang('View.invalidComponentClass', [$class]));
    }

    public static function tagSyntaxError(string $output)
    {
        return new static(lang('View.tagSyntaxError', [$output]));
    }

    public static function invalidDecorator(string $className)
    {
        return new static(lang('View.invalidDecoratorClass', [$className]));
    }
}
