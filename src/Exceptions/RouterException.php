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

/**
 * RouterException
 */
class RouterException extends FrameworkException
{
    /**
     * Levé lorsque le type de paramètre réel ne correspond pas
     * les types attendus.
     *
     * @return RouterException
     */
    public static function invalidParameterType()
    {
        return new static(lang('Router.invalidParameterType'));
    }

    /**
     * Levée lorsqu'une route par défaut n'est pas définie.
     *
     * @return RouterException
     */
    public static function missingDefaultRoute()
    {
        return new static(lang('Router.missingDefaultRoute'));
    }

    /**
     * Lancer lorsque le contrôleur ou sa méthode est introuvable.
     *
     * @return RouterException
     */
    public static function controllerNotFound(string $controller, string $method)
    {
        return new static(lang('HTTP.controllerNotFound', [$controller, $method]));
    }

    /**
     * Lancer lorsque la route n'est pas valide.
     *
     * @return RouterException
     */
    public static function invalidRoute(string $route)
    {
        return new static(lang('HTTP.invalidRoute', [$route]));
    }

    /**
     * Throw when dynamic controller.
     *
     * @return RouterException
     */
    public static function dynamicController(string $handler)
    {
        return new static(lang('Router.invalidDynamicController', [$handler]));
    }

    /**
     * Throw when controller name has `/`.
     *
     * @return RouterException
     */
    public static function invalidControllerName(string $handler)
    {
        return new static(lang('Router.invalidControllerName', [$handler]));
    }
}
