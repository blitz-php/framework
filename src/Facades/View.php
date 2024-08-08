<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Facades;

use BlitzPHP\Validation\ErrorBag;
use BlitzPHP\Container\Services;

/**
 * @method static bool                exists(string $view, ?string $ext = null, array $options = [])        Verifie qu'un fichier de vue existe
 * @method static \BlitzPHP\View\View first(string $view, array $data = [], array $options = [])            Utilise le premier fichier de vue trouvé pour le rendu
 * @method static string              get(bool|string $compress = 'auto')                                   Recupere et retourne le code html de la vue créée
 * @method static \BlitzPHP\View\View layout(string $layout)                                                Definit le layout a utiliser par les vues
 * @method static \BlitzPHP\View\View make(string $view, array $data = [], array $options = [])             Crée une instance de vue prêt à être utilisé
 * @method static void                render()                                                              Affiche la vue generee au navigateur
 * @method static void                share(array|Closure|string $key, mixed $value = null)                 Defini les données partagées entre plusieurs vues
 * @method static \BlitzPHP\View\View with(array|string $key, mixed $value = null, ?string $context = null) Définit plusieurs éléments de données de vue à la fois.
 * @method static \BlitzPHP\View\View withErrors((array | ErrorBag | string) $errors) Ajoute des erreurs à la session en tant que Flashdata.
 *
 * @see \BlitzPHP\View\View
 */
final class View extends Facade
{
    protected static function accessor(): object
    {
        return Services::viewer();
    }
}
