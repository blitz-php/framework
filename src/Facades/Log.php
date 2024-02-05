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
 * @method static void emergency(string|Stringable $message, array $context = []) Le système est inutilisable.
 * @method static void action(string|Stringable $message, array $context = []) Des mesures doivent être prises immédiatement.
 * @method static void critical(string|Stringable $message, array $context = []) Conditions critiques.
 * @method static void error(string|Stringable $message, array $context = []) Erreurs d'exécution qui ne nécessitent pas d'action immédiate, mais qui doivent être enregistrées et surveillées.
 * @method static void warning(string|Stringable $message, array $context = []) Événements exceptionnels qui ne sont pas des erreurs.
 * @method static void notice(string|Stringable $message, array $context = []) Des événements normaux mais significatifs.
 * @method static void info(string|Stringable $message, array $context = []) Événements intéressants.
 * @method static void debug(string|Stringable $message, array $context = []) Informations détaillées sur le débogage.
 * @method static void log(int|string $level, string|Stringable $message, array $context = []) Enregistrements avec un niveau arbitraire.
 *
 * @see \BlitzPHP\Debug\Logger
 */
final class Log extends Facade
{
    protected static function accessor(): object
    {
        return Services::logger();
    }
}
