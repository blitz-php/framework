<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Models;

use BlitzPHP\Config\Database;
use BlitzPHP\Database\Connection\BaseConnection;
use BlitzPHP\Wolke\Model;

abstract class BaseEntity extends Model
{
    /**
     * {@inheritDoc}
     * 
     * @internal Permet l'initialisation de la base de donnees pour l'ORM Wolke
     */
    public static function resolveConnection(?string $connection = null): BaseConnection
    {
        return static::$resolver = Database::connect($connection);
    }
}
