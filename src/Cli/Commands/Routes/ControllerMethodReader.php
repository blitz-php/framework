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
    public function __construct(private string $namespace)
    {
    }

    /**
     * @phpstan-param class-string $class
     *
     * @return array<int, array{route: string, handler: string}>
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
        $uriByClass = $this->getUriByClass($classname);

        if ($this->hasRemap($reflection)) {
            $methodName = '_remap';

            $routeWithoutController = $this->getRouteWithoutController(
                $classShortname,
                $defaultController,
                $uriByClass,
                $classname,
                $methodName
            );
            $output = [...$output, ...$routeWithoutController];

            $output[] = [
                'route'   => $uriByClass . '[/...]',
                'handler' => '\\' . $classname . '::' . $methodName,
            ];

            return $output;
        }

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();

            $route = $uriByClass . '/' . $methodName;

            // Exclut BaseController et initialize
            if (preg_match('#\AbaseController.*#', $route) === 1) {
                continue;
            }
            if (preg_match('#.*/initialize\z#', $route) === 1) {
                continue;
            }

            if ($methodName === $defaultMethod) {
                $routeWithoutController = $this->getRouteWithoutController(
                    $classShortname,
                    $defaultController,
                    $uriByClass,
                    $classname,
                    $methodName
                );
                $output = [...$output, ...$routeWithoutController];

                $output[] = [
                    'route'   => $uriByClass,
                    'handler' => '\\' . $classname . '::' . $methodName,
                ];
            }

            $output[] = [
                'route'   => $route . '[/...]',
                'handler' => '\\' . $classname . '::' . $methodName,
            ];
        }

        return $output;
    }

    /**
     * Si la classe a une méthode _remap().
     */
    private function hasRemap(ReflectionClass $class): bool
    {
        if ($class->hasMethod('_remap')) {
            $remap = $class->getMethod('_remap');

            return $remap->isPublic();
        }

        return false;
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
     * Obtient une route sans contrôleur par défaut.
     */
    private function getRouteWithoutController(
        string $classShortname,
        string $defaultController,
        string $uriByClass,
        string $classname,
        string $methodName
    ): array {
        $output = [];

        if ($classShortname === $defaultController) {
            $pattern                = '#' . preg_quote(lcfirst($defaultController), '#') . '\z#';
            $routeWithoutController = rtrim(preg_replace($pattern, '', $uriByClass), '/');
            $routeWithoutController = $routeWithoutController ?: '/';

            $output[] = [
                'route'   => $routeWithoutController,
                'handler' => '\\' . $classname . '::' . $methodName,
            ];
        }

        return $output;
    }
}
