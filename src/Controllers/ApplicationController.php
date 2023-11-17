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

use BlitzPHP\Container\Services;
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
     * @var array Données partagées entre toutes les vue chargées à partir d'un controleur
     */
    protected $viewDatas = [];

    /**
     * @var string Layout a utiliser
     */
    protected $layout;

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
            $path                                            = implode(DS, [$dirname, $filename]) . DS;
        }

        $viewer = Services::viewer();

        $viewer->setData($data)->setOptions($options);

        if (! empty($this->layout) && is_string($this->layout)) {
            $viewer->setLayout($this->layout);
        }

        if (! empty($this->viewDatas) && is_array($this->viewDatas)) {
            $viewer->addData($this->viewDatas);
        }

		if (empty($data['title'])) {
			if (! is_string($controllerName = Dispatcher::getController(false))) {
				$controllerName = static::class;
			}
			$controllerName = str_ireplace(['App\Controllers', 'Controller'], '', $controllerName);

			$dbt  = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
			$func = isset($dbt[1]['function']) ? $dbt[1]['function'] : Dispatcher::getMethod();

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
            $view = isset($dbt[1]['function']) ? $dbt[1]['function'] : '';
        }

        if (empty($view) && empty($data)) {
            $dbt  = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $view = isset($dbt[1]['function']) ? $dbt[1]['function'] : '';
        }

        if (empty($view)) {
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
