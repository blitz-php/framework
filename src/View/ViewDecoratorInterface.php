<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\View;

/**
 * Les decorateurs de vues sont des classes simples qui ont la possibilité de modifier la sortie des appels view() avant qu'elle ne soit mise en cache.
 */
interface ViewDecoratorInterface
{
    /**
     * Prend $html et a la possibilité de le modifier.
     * DOIT renvoyer le HTML modifié.
     */
    public static function decorate(string $html): string;
}
