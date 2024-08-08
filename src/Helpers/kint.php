<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Kint\Kint;

// Ce helper est automatiquement chargé par BlitzPHP.

if (! function_exists('dd')) {
    /**
     * Imprime un rapport de débogage Kint et coupe le script.
     *
     * @param array ...$vars
     *
     * @codeCoverageIgnore Ne peut pas etre tester ... presence de "exit"
     */
    function dd(...$vars): void
    {
        if (class_exists(Kint::class)) {
            Kint::$aliases[] = 'dd';
            Kint::dump(...$vars);
        }

        exit;
    }
}

if (! function_exists('dump')) {
    /**
     * Imprime un rapport de débogage Kint sans couper le script.
     *
     * @param array ...$vars
     *
     * @codeCoverageIgnore Ne peut pas etre tester
     */
    function dump(...$vars): void
    {
        if (class_exists(Kint::class)) {
            Kint::$aliases[] = 'dump';
            Kint::dump(...$vars);
        }
    }
}

if (! function_exists('d') && ! class_exists(Kint::class)) {
    // Au cas où Kint n'est pas chargé
    /**
     * @param array $vars
     */
    function d(...$vars): int
    {
        return 0;
    }
}

if (! function_exists('trace')) {
    /**
     * Fournit un backtrace au point d'exécution actuel, à partir de Kint.
     *
     * @return int|string
     *
     * @codeCoverageIgnore Ne peut pas etre tester
     */
    function trace()
    {
        if (! class_exists(Kint::class)) {
            return 0;
        }

        Kint::$aliases[] = 'trace';

        return Kint::trace();
    }
}
