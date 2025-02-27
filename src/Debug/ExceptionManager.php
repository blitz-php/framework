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
        return $debugger->pushHandler(static function (Throwable $exception, InspectorInterface $inspector, RunInterface $run) use ($config): int {
            $exception_code = $exception->getCode();
            if ($exception_code >= 400 && $exception_code < 600) {
                $run->sendHttpCode($exception_code);
            }

            if (true === $config['log'] && ! in_array($exception->getCode(), $config['ignore_codes'], true)) {
                service('logger')->error($exception);
            }

            if (is_dir($config['error_view_path'])) {
                $files = array_map(static fn (SplFileInfo $file) => $file->getFilenameWithoutExtension(), service('fs')->files($config['error_view_path']));
            } else {
                $files = [];
            }

            if (in_array((string) $exception->getCode(), $files, true)) {
                $view = new View();
                $view->setAdapter(config('view.active_adapter', 'native'), ['view_path_locator' => $config['error_view_path']])
                    ->display((string) $exception->getCode())
                    ->setData(['message' => $exception->getMessage()])
                    ->render();

                return Handler::QUIT;
            }
            if (in_array('production', $files, true) && is_online()) {
                $view = new View();
                $view->setAdapter(config('view.active_adapter', 'native'), ['view_path_locator' => $config['error_view_path']])
                    ->display('production')
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
            } elseif (is_string($handler) && class_exists($handler)) {
                $class = service('container')->make($handler);
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
            if (Misc::isAjaxRequest() || service('request')->isJson()) {
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
                $values = match ($name) {
                    '_GET'     => $_GET,
                    '_POST'    => $_POST,
                    '_COOKIE'  => $_COOKIE,
                    '_SERVER'  => $_SERVER,
                    '_ENV'     => $_ENV,
                    '_FILES'   => $_FILES ?: [],
                    '_SESSION' => $_SESSION ?? [],
                    default    => [],
                };

                foreach (array_keys($values) as $key) {
                    $handler->blacklist($name, $key);
                }
            }
        }

        return $handler;
    }
}
