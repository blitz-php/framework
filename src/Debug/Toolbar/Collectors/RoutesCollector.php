<?php

namespace BlitzPHP\Debug\Toolbar\Collectors;

use BlitzPHP\Loader\Services;
use BlitzPHP\Router\Dispatcher;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

/**
 * Collecteur de routes pour la barre d'outils de débogage
 * 
 * @credit	<a href="https://codeigniter.com">CodeIgniter 4.2 - CodeIgniter\Debug\Toolbar\Collectors\Routes</a>
 */
class RoutesCollector extends BaseCollector
{
	/**
	 * {@inheritDoc}
	 */
	protected $hasTimeline = false;

	/**
	 * {@inheritDoc}
	 */
	protected $hasTabContent = true;

	/**
	 * {@inheritDoc}
	 */
	protected $title = 'Routes';

	//--------------------------------------------------------------------

	/**
	 * {@inheritDoc}
	 * 
	 * @throws \ReflectionException
	 */
	public function display(): array
	{
		$rawRoutes = Services::routes(true);
		$router    = Services::router(null, null, true);

		/*
		 * Route correspondante
		 */
		$route = $router->getMatchedRoute();

		$controllerName = $router->controllerName();
		if (empty($controllerName)) {
			$controllerName = Dispatcher::getController();
		}
		$methodName = Dispatcher::getMethod();

		// Récupère nos paramètres
		// Route sous forme de callback
		if (is_callable($controllerName)) {
			$method = new ReflectionFunction($controllerName);
		}
		else {
			try {
				$method = new ReflectionMethod($controllerName, !empty($methodName) ? $methodName : $router->methodName());
			}
			catch (ReflectionException $e) {
				// Si nous sommes ici, la méthode n'existe pas
				// et est probablement calculé dans _remap.
				$method = new ReflectionMethod($controllerName, '_remap');
			}	
		}

		$rawParams = $method->getParameters();

		$params = [];
		foreach ($rawParams as $key => $param) {
			$params[] = [
                'name'  => '$' . $param->getName() . ' = ',
                'value' => $router->params()[$key] ??
                    ' <empty> | default: '
                    . var_export(
                        $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
                        true
                    ),
            ];
		}

		$matchedRoute = [
			[
				'directory'  => $router->directory(),
				'controller' => $router->controllerName(),
				'method'     => $router->methodName(),
				'paramCount' => count($router->params()),
				'truePCount' => count($params),
				'params'     => $params ?? [],
			],
		];

		/*
		* Routes définies
		*/
		$routes    = [];
		$methods    = [
			'get',
			'head',
			'post',
			'patch',
			'put',
			'delete',
			'options',
			'trace',
			'connect',
			'cli',
		];

		foreach ($methods as $method) {
			$raw = $rawRoutes->getRoutes($method, true);
			
			foreach ($raw as $route => $handler) {
				$tab = [
					'method' => strtoupper($method),
					'route' => $route,    
					'name' => '',
					'handler' => ''
				];

				// filtre pour les chaînes, car les callback ne sont pas affichables
				if (is_string($handler)) {
					$tab['handler'] = $handler;
				}
				if (is_array($handler)) {
					$tab['handler'] = is_string($handler['handler']) ? $handler['handler'] : 'Closure';
					$tab['name'] = $handler['name'];
				}
				$routes[] = $tab;
			}
		}

		return [
			'matchedRoute' => $matchedRoute,
			'routes'       => $routes,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getBadgeValue(): int
	{
		$rawRoutes = Services::routes(true);

		return count($rawRoutes->getRoutes());
	}

	/**
	 * {@inheritDoc}
	 *
	 * Icon from https://icons8.com - 1em package
	 */
	public function icon(): string
	{
		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAFDSURBVEhL7ZRNSsNQFIUjVXSiOFEcuQIHDpzpxC0IGYeE/BEInbWlCHEDLsSiuANdhKDjgm6ggtSJ+l25ldrmmTwIgtgDh/t37r1J+16cX0dRFMtpmu5pWAkrvYjjOB7AETzStBFW+inxu3KUJMmhludQpoflS1zXban4LYqiO224h6VLTHr8Z+z8EpIHFF9gG78nDVmW7UgTHKjsCyY98QP+pcq+g8Ku2s8G8X3f3/I8b038WZTp+bO38zxfFd+I6YY6sNUvFlSDk9CRhiAI1jX1I9Cfw7GG1UB8LAuwbU0ZwQnbRDeEN5qqBxZMLtE1ti9LtbREnMIuOXnyIf5rGIb7Wq8HmlZgwYBH7ORTcKH5E4mpjeGt9fBZcHE2GCQ3Vt7oTNPNg+FXLHnSsHkw/FR+Gg2bB8Ptzrst/v6C/wrH+QB+duli6MYJdQAAAABJRU5ErkJggg==';
	}
}
