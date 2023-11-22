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

use BlitzPHP\Container\Services;

/**
 * @method static  mixed call(array|callable|string $callable, array $parameters = []) Appelez la fonction donnée en utilisant les paramètres donnés.
 *                                                                             Les paramètres manquants seront résolus à partir du conteneur.
 * @method static string debugEntry(string $name) Obtenir les informations de débogage de l'entrée.
 * @method static mixed  get(string $name)                       Renvoie une entrée du conteneur par son nom.
 * @method static bool  has(string $name)                       Testez si le conteneur peut fournir quelque chose pour le nom donné.
 * @method static bool  bound(string $name)                       Verifie qu'une entree a été explicitement définie dans le conteneur.
 * @method static void  add(string $key, \Closure $callback)      Defini un element au conteneur sous forme de factory.
 * 															Si l'element existe déjà, il sera remplacé.
 * @method static void  addIf(string $key, \Closure $callback)      Defini un element au conteneur sous forme de factory.
 * 															Si l'element existe déjà, il sera ignoré.
 * @method static void  merge(array $keys)      					Defini plusieurs elements au conteneur sous forme de factory.
 * 															L'element qui existera déjà sera remplacé par la correspondance du tableau.
 * @method static void  mergeIf(array $keys)      					Defini plusieurs elements au conteneur sous forme de factory.
 * 															L'element qui existera déjà sera ignoré.
 * @method static array  getKnownEntryNames()                       Obtenez des entrées de conteneur définies.
 * @method static object injectOn(object $instance)                 Injectez toutes les dépendances sur une instance existante.
 * @method static void   set(string $name, mixed $value)            Définissez un objet ou une valeur dans le conteneur.
 * @method static mixed  make(string $name, array $parameters = []) Construire une entrée du conteneur par son nom.
 *                                                           Cette méthode se comporte comme singleton() sauf qu'elle résout à nouveau l'entrée à chaque fois.
 *                                                           Par exemple, si l'entrée est une classe, une nouvelle instance sera créée à chaque fois.
 *                                                           Cette méthode fait que le conteneur se comporte comme une usine.
 *
 * @see \BlitzPHP\Container\Container
 */
final class Container extends Facade
{
	protected static function accessor(): object
    {
        return Services::container();
    }
}
