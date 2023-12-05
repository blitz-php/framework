<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Spec\BlitzPHP\App\Views;

/**
 * Cette classe n'est utilisée que pour fournir un point de référence pendant les tests afin de s'assurer que les choses fonctionnent comme prévu.
 */
class BadDecorator
{
    public static function decorate(string $html): string
    {
        return $html;
    }
}
