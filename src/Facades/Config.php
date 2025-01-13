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

use Nette\Schema\Schema;

/**
 * @method static bool  exists(string $key)                                  Détermine si une clé de configuration existe.
 * @method static mixed get(string $key, mixed $default = null)              Renvoie une configuration de l'application.
 * @method static bool  has(string $key)                                     Détermine s'il y'a une clé de configuration.
 * @method static bool  missing(string $key)                                 Détermine s'il manque une clé de configuration.
 * @method static $this ghost((array | string) $key, ?Schema $schema = null) Rend disponible un groupe de configuration qui n'existe pas (pas de fichier de configuration). Ceci est notament utilse pour definir des configurations à la volée
 * @method static void  reset(null|array|string $keys = null)                Reinitialise une configuration en fonction des donnees initiales issues des fichiers de configurations.
 * @method static void  set(string $key, mixed $value)                       Définit une configuration de l'application.
 *
 * @see \BlitzPHP\Config\Config
 */
final class Config extends Facade
{
    protected static function accessor(): object
    {
        return service('config');
    }
}
