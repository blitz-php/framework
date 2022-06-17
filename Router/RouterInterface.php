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

use Psr\Http\Message\ServerRequestInterface;

/**
 * Comportement attendu d'un routeur.
 */
interface RouterInterface
{
    /**
     * Stocke une référence à l'objet RouteCollection.
     */
    public function init(RouteCollectionInterface $routes, ServerRequestInterface $request);

    /**
     * Analyse l'URI et tente de faire correspondre l'URI actuel au
     * l'une des routes définies dans la RouteCollection.
     *
     * @param string $uri
     *
     * @return mixed
     */
    public function handle(?string $uri = null);

    /**
     * Renvoie le nom du contrôleur correspondant.
     *
     * @return mixed
     */
    public function controllerName();

    /**
     * Renvoie le nom de la méthode à exécuter dans le
     * conteneur choisi.
     *
     * @return mixed
     */
    public function methodName();

    /**
     * Renvoie les liaisons qui ont été mises en correspondance et collectées
     * pendant le processus d'analyse sous forme de tableau, prêt à être envoyé à
     * instance->méthode(...$params).
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
