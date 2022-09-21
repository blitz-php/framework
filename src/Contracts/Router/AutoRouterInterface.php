<?php

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
