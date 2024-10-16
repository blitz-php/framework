<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Routes;

use ReflectionClass;
use ReflectionMethod;

/**
 * Lit un contrôleur et renvoie une liste de listes de routes automatiques.
 */
final class ControllerMethodReader
{
    /**
     * @param string $namespace Namespace par défaut
     */
    public function __construct(private readonly string $namespace, private readonly array $httpMethods)
    {
    }

    /**
     * @phpstan-param class-string $class
     *
     * @return         array<int, array{route: string, handler: string}>
     * @phpstan-return list<array{route: string, handler: string}>
     */
    public function read(string $class, string $defaultController = 'Home', string $defaultMethod = 'index'): array
    {
        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract()) {
            return [];
        }

        $classname      = $reflection->getName();
        $classShortname = $reflection->getShortName();

        $output     = [];
        $classInUri = $this->getUriByClass($classname);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();

            foreach ($this->httpMethods as $httpVerb) {
                if (str_starts_with($methodName, $httpVerb)) {
                    // Enleve le prefixe des verbes HTTP
                    $methodInUri = lcfirst(substr($methodName, strlen($httpVerb)));

                    // Verifie si c'est la methode par defaut
                    if ($methodInUri === $defaultMethod) {
                        $routeForDefaultController = $this->getRouteForDefaultController(
                            $classShortname,
                            $defaultController,
                            $classInUri,
                            $classname,
                            $methodName,
                            $httpVerb,
                            $method
                        );

                        if ($routeForDefaultController !== []) {
                            // Le contrôleur est le contrôleur par défaut.
                            // Il n'a qu'un itinéraire pour la méthode par défaut.
                            // Les autres méthodes ne seront pas routées même si elles existent.
                            $output = [...$output, ...$routeForDefaultController];

                            continue;
                        }

                        [$params, $routeParams] = $this->getParameters($method);

                        // Route pour la methode par defaut
                        $output[] = [
                            'method'       => $httpVerb,
                            'route'        => $classInUri,
                            'route_params' => $routeParams,
                            'handler'      => '\\' . $classname . '::' . $methodName,
                            'params'       => $params,
                        ];

                        continue;
                    }

                    $route = $classInUri . '/' . $methodInUri;

                    [$params, $routeParams] = $this->getParameters($method);

                    // S'il s'agit du contrôleur par défaut, la méthode ne sera pas routée.
                    if ($classShortname === $defaultController) {
                        $route = 'x ' . $route;
                    }

                    $output[] = [
                        'method'       => $httpVerb,
                        'route'        => $route,
                        'route_params' => $routeParams,
                        'handler'      => '\\' . $classname . '::' . $methodName,
                        'params'       => $params,
                    ];
                }
            }
        }

        return $output;
    }

    private function getParameters(ReflectionMethod $method): array
    {
        $params      = [];
        $routeParams = '';
        $refParams   = $method->getParameters();

        foreach ($refParams as $param) {
            $required = true;
            if ($param->isOptional()) {
                $required = false;

                $routeParams .= '[/..]';
            } else {
                $routeParams .= '/..';
            }

            // [variable_name => required?]
            $params[$param->getName()] = $required;
        }

        return [$params, $routeParams];
    }

    /**
     * @phpstan-param class-string $classname
     *
     * @return string Partie du chemin URI du ou des dossiers et du contrôleur
     */
    private function getUriByClass(string $classname): string
    {
        // retire le namespace
        $pattern = '/' . preg_quote($this->namespace, '/') . '/';
        $class   = ltrim(preg_replace($pattern, '', $classname), '\\');

        $classParts = explode('\\', $class);
        $classPath  = '';

        foreach ($classParts as $part) {
            // mettre la première lettre en minuscule, car le routage automatique
            // met la première lettre du chemin URI en majuscule et recherche le contrôleur
            $classPath .= lcfirst($part) . '/';
        }

        return rtrim($classPath, '/');
    }

    /**
     * Obtient une route pour le contrôleur par défaut.
     *
     * @return list<array>
     */
    private function getRouteForDefaultController(
        string $classShortname,
        string $defaultController,
        string $uriByClass,
        string $classname,
        string $methodName,
        string $httpVerb,
        ReflectionMethod $method
    ): array {
        $output = [];

        if ($classShortname === $defaultController) {
            $pattern                = '#' . preg_quote(lcfirst($defaultController), '#') . '\z#';
            $routeWithoutController = rtrim(preg_replace($pattern, '', $uriByClass), '/');
            $routeWithoutController = $routeWithoutController ?: '/';

            [$params, $routeParams] = $this->getParameters($method);

            if ($routeWithoutController === '/' && $routeParams !== '') {
                $routeWithoutController = '';
            }

            $output[] = [
                'method'       => $httpVerb,
                'route'        => $routeWithoutController,
                'route_params' => $routeParams,
                'handler'      => '\\' . $classname . '::' . $methodName,
                'params'       => $params,
            ];
        }

        return $output;
    }
}
