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
use BlitzPHP\View\View;
use Symfony\Component\Finder\SplFileInfo;
use Throwable;
use Whoops\Handler\Handler;
use Whoops\Handler\HandlerInterface;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Inspector\InspectorInterface;
use Whoops\Run;
use Whoops\RunInterface;
use Whoops\Util\Misc;

/**
 * Gestionnaire d'exceptions
 */
class ExceptionManager
{
    /**
     * Gestionnaire d'exceptions de type http (404, 500) qui peuvent avoir une page d'erreur personnalisée.
     */
    public static function registerHttpErrors(Run $debugger, array $config): Run
    {
        return $debugger->pushHandler(static function (Throwable $exception, InspectorInterface $inspector, RunInterface $run) use($config) {
            if (true === $config['log']) {
                if (! in_array($exception->getCode(), $config['ignore_codes'], true)) {
                    Services::logger()->error($exception);
                }
            }

            $files = array_map(fn(SplFileInfo $file) => $file->getFilenameWithoutExtension(), Services::fs()->files($config['error_view_path']));

            if (in_array((string)$exception->getCode(), $files, true)) {
                $view = new View();
                $view->setAdapter(config('view.active_adapter', 'native'), ['view_path_locator' => $config['error_view_path']])
                    ->display((string)$exception->getCode())
                    ->setData(['message' => $exception->getMessage()])
                    ->render();
                    
                return Handler::QUIT;
            }     
            
            return Handler::DONE;
        });
    }

    /**
     * Gestionnaire d'applications fournis par le developpeur.
     */
    public static function registerAppHandlers(Run $debugger, array $config): Run
    {

        foreach ($config['handlers'] ?? [] as $handler) {
            
            if (is_callable($handler)) {
                $debugger->pushHandler($handler);
            } else if(is_string($handler) && class_exists($handler)) {
                $class = Services::container()->make($handler);
                if (is_callable($class) || $class instanceof HandlerInterface) {
                    $debugger->pushHandler($class);
                }
            }
        }

        return $debugger;
    }

    /**
     * Gestionnaire d'erreurs globales whoops
     */
    public static function registerWhoopsHandler(Run $debugger, array $config): Run
    {
        if (Misc::isCommandLine()) {
            $debugger->pushHandler(new PlainTextHandler());
        }

        if (! is_online()) {
            if (Misc::isAjaxRequest()) {
                $debugger->pushHandler(new JsonResponseHandler());
            } else {
                $handler = new PrettyPageHandler(); 

                $handler->setEditor($config['editor'] ?: PrettyPageHandler::EDITOR_VSCODE);
                $handler->setPageTitle($config['title'] ?: $handler->getPageTitle());
                $handler->setApplicationRootPath(APP_PATH);
                $handler->setApplicationPaths([APP_PATH, SYST_PATH, VENDOR_PATH]);

                $handler = self::setBlacklist($handler, $config['blacklist']);

                foreach ($config['data'] as $label => $data) {
                    if (is_array($data)) {
                        $handler->addDataTable($label, $data);
                    } elseif (is_callable($data)) {
                        $handler->addDataTableCallback($label, $data);
                    }
                }                
                
                $debugger->pushHandler($handler);
            }
        }

        return $debugger;
    }


    /**
     * Enregistre les elements blacklisté dans l'affichage du rapport d'erreur
     */
    private static function setBlacklist(PrettyPageHandler $handler, array $blacklists): PrettyPageHandler
    {
        foreach ($blacklists as $blacklist) {
            [$name, $key] = explode('/', $blacklist) + [1 => '*'];

            if ($name[0] !== '_') {
                $name = '_' . $name;
            }

            $name = strtoupper($name);
            
            if ($key !== '*') {
                foreach (explode(',', $key) as $k) {
                    $handler->blacklist($name, $k);
                }
            } else {
                $values = match($name) {
                    '_GET'     => $_GET,
                    '_POST'    => $_POST,
                    '_COOKIE'  => $_COOKIE,
                    '_SERVER'  => $_SERVER,
                    '_ENV'     => $_ENV,
                    '_FILES'   => $_FILES ?? [],
                    '_SESSION' => $_SESSION ?? [],
                    default    => [],
                };
                foreach ($values as $key => $value) {
                    $handler->blacklist($name, $key);
                }
            }
        }

        return $handler;
    }
}
