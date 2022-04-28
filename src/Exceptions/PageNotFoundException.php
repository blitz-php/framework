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

use BlitzPHP\Loader\Services;
use OutOfBoundsException;

class PageNotFoundException extends OutOfBoundsException implements ExceptionInterface
{
    use DebugTraceableTrait;

    /**
     * Code d'erreur
     *
     * @var int
     */
    protected $code = 404;

    public static function pageNotFound(?string $message = null)
    {
        return new static($message ?? self::lang('HTTP.pageNotFound'));
    }

    public static function emptyController()
    {
        return new static(self::lang('HTTP.emptyController'));
    }

    public static function controllerNotFound(string $controller, string $method)
    {
        return new static(self::lang('HTTP.controllerNotFound', [$controller, $method]));
    }

    public static function methodNotFound(string $method)
    {
        return new static(self::lang('HTTP.methodNotFound', [$method]));
    }

    /**
     * Obtenir le message système traduit
     *
     * Utilisez une instance de langue non partagée dans les services.
     * Si une instance partagée est créée, la langue
     * ont les paramètres régionaux actuels, donc même si les utilisateurs appellent
     * `$this->request->setLocale()` dans le contrôleur ensuite,
     * les paramètres régionaux de la langue ne seront pas modifiés.
     */
    private static function lang(string $line, array $args = []): string
    {
        $lang = Services::language(null, false);

        return $lang->getLine($line, $args);
    }
}
