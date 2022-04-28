<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Http;

use Laminas\Diactoros\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;

class ServerRequest extends \Laminas\Diactoros\ServerRequest
{
    /**
     * Creer une requête à partir des variables superglobales
     *
     * // @phpstan-ignore-next-line
     *
     * @param array $server  $_SERVER superglobal //
     * @param array $query   $_GET superglobal
     * @param array $body    $_POST superglobal
     * @param array $cookies $_COOKIE superglobal
     * @param array $files   $_FILES superglobal
     */
    public static function fromGlobals(): ServerRequestInterface
    {
        $args = func_get_args();

        return ServerRequestFactory::fromGlobals(...$args);
    }

    public function getLocale(): string
    {
        return 'fr';
    }
}
