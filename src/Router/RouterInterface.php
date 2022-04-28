<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Router;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Expected behavior of a Router.
 */
interface RouterInterface
{
    /**
     * Stores a reference to the RouteCollection object.
     *
     * @param Request $request
     */
    public function __construct(RouteCollectionInterface $routes, ?ServerRequestInterface $request = null);

    /**
     * Scans the URI and attempts to match the current URI to the
     * one of the defined routes in the RouteCollection.
     *
     * @param string $uri
     *
     * @return mixed
     */
    public function handle(?string $uri = null);

    /**
     * Returns the name of the matched controller.
     *
     * @return mixed
     */
    public function controllerName();

    /**
     * Returns the name of the method to run in the
     * chosen container.
     *
     * @return mixed
     */
    public function methodName();

    /**
     * Returns the binds that have been matched and collected
     * during the parsing process as an array, ready to send to
     * instance->method(...$params).
     *
     * @return mixed
     */
    public function params();

    /**
     * Définit la valeur qui doit être utilisée pour correspondre au fichier index.php. Valeurs par défaut
     * à index.php mais cela vous permet de le modifier au cas où vous utilisez
     * quelque chose comme mod_rewrite pour supprimer la page. Vous pourriez alors le définir comme une chaine vide=
     */
    public function setIndexPage(string $page);
}
