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

use BlitzPHP\Loader\Services;
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
        $path = '';

        // N'est-il pas namespaced ? on cherche le dossier en fonction du controleur
        if (strpos($view, '\\') === false) {
            $reflection = new ReflectionClass(static::class);
            $path       = str_replace([CONTROLLER_PATH, 'Controller', '.php'], '', $reflection->getFileName());
            $path       = strtolower($path) . '/';
        }

        $object = Services::viewer();

        $object->setData($data)->setOptions($options);

        if (! empty($this->layout) && is_string($this->layout)) {
            $object->setLayout($this->layout);
        }

        if (! empty($this->viewDatas) && is_array($this->viewDatas)) {
            $object->addData($this->viewDatas);
        }

        return $object->display($path . $view)->setVar('title', str_ireplace('Controller', '', Dispatcher::getController(false)) . ' - ' . Dispatcher::getMethod());
    }

    /**
     * Charge et rend directement une vue
     */
    final protected function render(?string $view = null, ?array $data = [], ?array $options = []): ResponseInterface
    {
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
    final protected function addData(string|array $key, $value = null): self
    {
        $data = $key;

        if (is_string($key)) {
            $data = [$key => $value];
        }

        $this->viewDatas = array_merge($this->viewDatas, $data);

        return $this;
    }
}
