<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

if (! function_exists('autoload_foo')) {
    function autoload_foo(): string
    {
        return "Je suis chargé automatiquement par Autoloader via \$files\u{a0}!";
    }
}

if (! defined('AUTOLOAD_CONSTANT')) {
    define('AUTOLOAD_CONSTANT', 'foo');
}
