<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Initializer;

/**
 * Cette classe est utilisee pour deplacer ou supprimer certains fichiers des packages
 * c'est notament le cas avec le helpers de Laravel lors de l'installation d'ignition car ce helpers a la function *env* qui entre en colision avec celle de blitz
 *
 * @codeCoverageIgnore
 *
 * @internal
 */
final class ComposerScripts
{
    /**
     * Cette méthode statique est appelée par Composer après chaque événement de mise à jour,
     * exp., `composer install`, `composer update`, `composer remove`.
     */
    public static function postUpdate()
    {
        self::removeLaravelHelpers();
    }

    private static function removeLaravelHelpers(): void
    {
        $file = dirname(__DIR__, 4) . '/illuminate/support/helpers.php';

        if (file_exists($file)) {
            file_put_contents($file, '');
        }
    }
}
