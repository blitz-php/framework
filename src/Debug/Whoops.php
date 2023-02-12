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

use BlitzPHP\Loader\Services;
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
 * Necessite l'instalation de flip/whoops 
 */
class Whoops
{
    /**
     * Demarre le processus
     *
     * @return void
     */
    public static function init()
    {
        if (! class_exists('Whoops\Run')) {
            return ;
        }

        $whoops  =  new Run();

        if (! is_online()) {       
            if (Misc::isCommandLine()) {
                $whoops->pushHandler(new PlainTextHandler);
            }
            else if (Misc::isAjaxRequest()) {
                $whoops->pushHandler(new JsonResponseHandler);
            }
            else {
                $whoops->pushHandler(new PrettyPageHandler);
            }
        }

        /**
         * On log toutes les erreurs
         */
        $whoops->pushHandler(function(Throwable $exception, Inspector $inspector, RunInterface $run) {
            /**
             * @var Logger
             */
            $logger = Services::logger();
            $logger->error($exception);
         
            return Handler::DONE;       
        });

        $whoops->register();
    }
}
