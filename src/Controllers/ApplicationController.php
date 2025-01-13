<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Controllers;

use BlitzPHP\Router\Dispatcher;
use BlitzPHP\View\View;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use ReflectionException;

/**
 * Le contrôleur de base pour les applications MVC
 */
class ApplicationController extends BaseController
{
    /**
     * Données partagées entre toutes les vue chargées à partir d'un controleur
     */
    protected array $viewDatas = [];

    /**
     * Layout a utiliser
     */
    protected string $layout = '';

    /**
     * Charge une vue
     *
     * @throws ReflectionException
     */
    protected function view(string $view, ?array $data = [], ?array $options = []): View
    {
        $path    = '';
        $data    = (array) $data;
        $options = (array) $options;

        // N'est-il pas namespaced ? on cherche le dossier en fonction du controleur
        if (! str_contains($view, '\\')) {
            $reflection                                      = new ReflectionClass(static::class);
            ['dirname' => $dirname, 'filename' => $filename] = pathinfo($reflection->getFileName());
            $dirname                                         = str_ireplace('Controllers', 'Views', $dirname);
            $filename                                        = strtolower(str_ireplace('Controller', '', $filename));

            $parts = explode('Views', $dirname);
            $base  = array_shift($parts);
            $parts = array_map('strtolower', $parts);
            $parts = [$base, ...$parts];

            $dirname = implode('Views', $parts);
            $path    = implode(DS, [$dirname, $filename]) . DS;

            if (! is_dir($path)) {
                $path = implode(DS, [$dirname]) . DS;
            }
        }

        /** @var View */
        $viewer = service('viewer');

        $viewer->setData($data)->options($options);

        if ($this->layout !== '') {
            $viewer->layout($this->layout);
        }

        if ($this->viewDatas !== [] && is_array($this->viewDatas)) {
            $viewer->addData($this->viewDatas);
        }

        if (empty($data['title'])) {
            if (! is_string($controllerName = Dispatcher::getController(false))) {
                $controllerName = static::class;
            }
            $controllerName = str_ireplace(['App\Controllers', 'Controller'], '', $controllerName);

            $dbt  = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $func = $dbt[1]['function'] ?? Dispatcher::getMethod();

            $viewer->setVar('title', $controllerName . ' - ' . $func);
        }

        return $viewer->display($path . $view);
    }

    /**
     * Charge et rend directement une vue
     */
    final protected function render(array|string $view = '', ?array $data = [], ?array $options = []): ResponseInterface
    {
        if (is_array($view)) {
            $data    = $view;
            $options = $data;

            $dbt  = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $view = $dbt[1]['function'] ?? '';
        }

        if (($view === '' || $view === '0') && ($data === null || $data === [])) {
            $dbt  = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $view = $dbt[1]['function'] ?? '';
        }

        if ($view === '' || $view === '0') {
            $view = Dispatcher::getMethod();
        }

        $view = $this->view($view, $data, $options)->get();

        return $this->response->withBody(to_stream($view));
    }

    /**
     * Defini des donnees à distribuer à toutes les vues
     *
     * @param mixed $value
     */
    final protected function addData(array|string $key, $value = null): self
    {
        $data = $key;

        if (is_string($key)) {
            $data = [$key => $value];
        }

        $this->viewDatas = array_merge($this->viewDatas, $data);

        return $this;
    }
}
