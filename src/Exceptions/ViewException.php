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
    public static function invalidCellMethod(string $class, string $method)
    {
        return new static(lang('View.invalidCellMethod', ['class' => $class, 'method' => $method]));
    }

    public static function missingCellParameters(string $class, string $method)
    {
        return new static(lang('View.missingCellParameters', ['class' => $class, 'method' => $method]));
    }

    public static function invalidCellParameter(string $key)
    {
        return new static(lang('View.invalidCellParameter', [$key]));
    }

    public static function noCellClass()
    {
        return new static(lang('View.noCellClass'));
    }

    public static function invalidCellClass(?string $class = null)
    {
        return new static(lang('View.invalidCellClass', [$class]));
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
