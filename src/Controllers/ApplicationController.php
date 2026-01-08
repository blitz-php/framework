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
    protected function view(string $view, array $data = [], array $options = []): View
    {
        /**
         * @var array<class-string<self>, string>
         */
        static $cachedPaths = [];

        // N'est-il pas namespaced ? on cherche le dossier en fonction du controleur
        if (! str_contains($view, '\\') && ! str_starts_with($view, '/')) {
            if (! isset($cachedPaths[static::class])) {
                $reflection = new ReflectionClass(static::class);
                ['dirname' => $dirname, 'filename' => $filename] = pathinfo($reflection->getFileName());

                [$dirname, $filename] = str_ireplace(['Controllers', 'Controller'], ['Views', ''], [$dirname, $filename]);

                $fullpath = $dirname . DS . strtolower($filename) . DS;

                $cachedPaths[static::class] = is_dir($fullpath) ? $fullpath : $dirname . DS;
            }

            $view = $cachedPaths[static::class] . $view;
        }

        /** @var View */
        $viewer = service('viewer');

        $viewer->setData($data + $this->viewDatas)->options($options);

        if ($this->layout !== '') {
            $viewer->layout($this->layout);
        }

        if (empty($data['title'])) {
            $controllerName = str_ireplace(['App\Controllers', 'Controller'], '', static::class);
            $func           = Dispatcher::getMethod();

            $viewer->setVar('title', $controllerName . ' - ' . $func);
        }

        return $viewer->display($view);
    }

    /**
     * Charge et rend directement une vue
     */
    protected function render(array|string $view = '', ?array $data = [], ?array $options = []): ResponseInterface
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
