<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Debug;

use BlitzPHP\Container\Services;
use Spatie\Ignition\Ignition;
use Throwable;
use Whoops\Exception\Inspector;
use Whoops\Handler\Handler;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Whoops\RunInterface;
use Whoops\Util\Misc;

/**
 * Capture et affiche les erreurs et exceptions via whoops
 *
 * Necessite l'instalation de `flip/whoops` ou `spatie/ignition`
 */
class Debugger
{
    /**
     * Demarre le processus
     *
     * @return void
     */
    public static function init()
    {
        if (class_exists(Run::class)) {
            return self::initWhoops();
        }
        if (class_exists(Ignition::class)) {
            return self::initIgnition();
        }
    }

    /**
     * Initialisation du debugger a travers filp/Whoops
     */
    private static function initWhoops()
    {
        $debugger = new Run();

        if (! is_online()) {
            if (Misc::isCommandLine()) {
                $debugger->pushHandler(new PlainTextHandler());
            } elseif (Misc::isAjaxRequest()) {
                $debugger->pushHandler(new JsonResponseHandler());
            } else {
                $debugger->pushHandler(new PrettyPageHandler());
            }
        }

        /**
         * On log toutes les erreurs
         */
        $debugger->pushHandler(static function (Throwable $exception, Inspector $inspector, RunInterface $run) {
            Services::logger()->error($exception);

            return Handler::DONE;
        });

        $debugger->register();
    }

    /**
     * Initialisation du debugger a travers spatie/ignition
     *
     * @todo customisation du debugger et log des erreurs
     */
    private static function initIgnition()
    {
        $debugger = Ignition::make();

        $debugger->applicationPath(ROOTPATH)
            ->shouldDisplayException(! on_prod())
            ->register();
    }
}
