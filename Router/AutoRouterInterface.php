<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Contracts\Router;

/**
 * Comportement attendu d'un AutoRouter.
 *
 * @credit <a href="http://www.codeigniter.com">CodeIgniter 4.2 - CodeIgniter\Router\AutoRouterInterface</a>
 */
interface AutoRouterInterface
{
    /**
     * Renvoie le contrôleur, la méthode et les paramètres de l'URI.
     *
     * @return array [directory_name, controller_name, controller_method, params]
     */
    public function getRoute(string $uri): array;
}
