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

use OutOfBoundsException;

class LoadException extends OutOfBoundsException implements ExceptionInterface
{
    use DebugTraceableTrait;

    /**
     * Code d'erreur
     *
     * @var int
     */
    protected $code = 404;

    public static function helperNotFound(string $helper): self
    {
        return new static(self::lang('Loader.helperNotFound', [$helper]));
    }

    public static function libraryNotFound(string $library): self
    {
        return new static(self::lang('Loader.libraryNotFound', [$library]));
    }

    public static function libraryDontExist(string $library): self
    {
        return new static(self::lang('Loader.libraryDontExist', [$library]));
    }

    public static function modelNotFound(string $model, string $path): self
    {
        return new static(self::lang('Loader.modelNotFound', [$model, $path]));
    }

    public static function modelDontExist(string $model, string $path): self
    {
        return new static(self::lang('Loader.modelDontExist', [$model, $path]));
    }

    public static function langNotFound(string $lang): self
    {
        return new static(self::lang('Loader.langNotFound', [$lang]));
    }

    public static function controllerNotFound(string $controller, string $path): self
    {
        return new static(self::lang('Loader.controllerNotFound', [$controller, $path]));
    }

    public static function controllerDontExist(string $controller, string $path): self
    {
        return new static(self::lang('Loader.controllerDontExist', [$controller, $path]));
    }

    public static function providersDefinitionDontExist(string $filename): self
    {
        return new static('Unable to load system services definition file. The `'.$filename.'` file does not exist or cannot be read.');
    }
}
