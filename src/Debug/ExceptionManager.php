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

use BlitzPHP\Exceptions\HttpException;
use BlitzPHP\Exceptions\TokenMismatchException;
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
 * Capture et affiche les erreurs et exceptions via whoops
 *
 * Necessite l'instalation de `flip/whoops`
 */
class ExceptionManager
{
    /**
     * Gestionnaire d'exception (instance Whoops)
     */
    private ?Run $debugger = null;

    /**
     * Configuration du gestionnaire d'exception
     */
    private object $config;

    public function __construct()
    {
        if (class_exists(Run::class)) {
            $this->debugger = new Run();
            $this->config   = (object) config('exceptions');
        }
    }

    /**
     * Demarre le processus
     */
    public function register(): void
    {
        if (! $this->debugger) {
            return;
        }

        $this->registerWhoopsHandler()
            ->registerHttpErrorsHandler()
            ->registerAppHandlers();

        $this->debugger->register();
    }

    /**
     * Enregistre les gestionnaires d'exception spécifiques à l'application.
     *
     * Cette méthode parcourt les gestionnaires configurés et les ajoute au débogueur.
     * Elle prend en charge à la fois les gestionnaires callable et les noms de classe sous forme de chaîne qui peuvent être instanciés.
     */
    private function registerAppHandlers(): self
    {
        foreach ($this->config->handlers as $handler) {
            if (is_callable($handler)) {
                $this->debugger->pushHandler($handler);
            } elseif (is_string($handler) && class_exists($handler)) {
                $class = service('container')->make($handler);
                if (is_callable($class) || $class instanceof HandlerInterface) {
                    $this->debugger->pushHandler($class);
                }
            }
        }

        return $this;
    }

    /**
     * Enregistre un gestionnaire pour les erreurs HTTP.
     *
     * Cette méthode met en place un gestionnaire d'erreurs personnalisé qui traite les exceptions,
     * les consigne si elle est configurée, et tente d'afficher les vues d'erreur appropriées.
     * Elle gère les codes d'état HTTP, la journalisation et les vues d'erreur personnalisées.
     */
    private function registerHttpErrorsHandler(): self
    {
        $this->debugger->pushHandler(function (Throwable $exception, InspectorInterface $inspector, RunInterface $run): int {
            $exception      = $this->prepareException($exception);
            $exception_code = $exception->getCode();

            if ($exception_code >= 400 && $exception_code < 600) {
                $run->sendHttpCode($exception_code);
            }

            if (true === $this->config->log && ! in_array($exception_code, $this->config->ignore_codes, true)) {
                service('logger')->error($exception);
            }

            if (is_dir($this->config->error_view_path)) {
                $files = array_map(static fn (SplFileInfo $file) => $file->getFilenameWithoutExtension(), service('fs')->files($this->config->error_view_path));
            } else {
                $files = [];
            }

            $files = collect($files)->flip()->only($exception_code, is_online() ? 'production' : '')->flip()->all();

            if ($files !== []) {
                $view = new View();

                $view->setAdapter(config('view.active_adapter', 'native'), ['view_path' => $this->config->error_view_path])
                    ->first($files, ['message' => $exception->getMessage()])
                    ->render();

                return Handler::QUIT;
            }

            return Handler::DONE;
        });

        return $this;
    }

    /**
     * Enregistre un gestionnaire de Whoops à des fins de débogage.
     *
     * Cette méthode met en place différents gestionnaires en fonction de l'environnement et des paramètres de configuration.
     * Elle vérifie la ligne de commande, l'état en ligne, les requêtes AJAX et les requêtes JSON.
     * En fonction des conditions, elle utilise PlainTextHandler, JsonResponseHandler ou PrettyPageHandler.
     *
     * Le PrettyPageHandler est configuré avec les paramètres de l'éditeur, le titre de la page, les chemins d'accès à l'application,
     * les données sur liste noire et les tables de données. Il gère également différents types de données pour les tables de données.
     */
    private function registerWhoopsHandler(): self
    {
        if (Misc::isCommandLine()) {
            $this->debugger->pushHandler(new PlainTextHandler(service('logger')));

            return $this;
        }

        if (is_online()) {
            return $this;
        }

        if (Misc::isAjaxRequest() || service('request')->isJson()) {
            $this->debugger->pushHandler(new JsonResponseHandler());

            return $this;
        }

        $handler = new PrettyPageHandler();

        $handler->handleUnconditionally(true);
        $handler->setEditor($this->config->editor ?: PrettyPageHandler::EDITOR_VSCODE);
        $handler->setPageTitle($this->config->title ?: $handler->getPageTitle());
        $handler->setApplicationPaths($this->getApplicationPaths());

        foreach ($this->config->blacklist as $blacklist) {
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

        foreach ($this->config->data as $label => $data) {
            if (is_array($data)) {
                $handler->addDataTable($label, $data);
            } elseif (is_callable($data)) {
                $handler->addDataTableCallback($label, $data);
            }
        }

        $this->debugger->pushHandler($handler);

        return $this;
    }

    /**
     * Préparer l'exception pour le rendu.
     */
    private static function prepareException(Throwable $e): Throwable
    {
        if ($e instanceof TokenMismatchException) {
            return new HttpException($e->getMessage(), 419, $e);
        }

        return $e;
    }

    /**
     * Récupère les chemins d'accès à l'application.
     */
    private function getApplicationPaths(): array
    {
        return collect(service('fs')->directories(base_path()))
            ->flip()
            ->except(base_path('vendor'))
            ->flip()
            ->all();
    }
}
