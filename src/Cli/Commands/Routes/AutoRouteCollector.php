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

/**
 * Collecte des données pour la liste des routes automatiques.
 */
final class AutoRouteCollector
{
    /**
     * @param string $namespace Namespace dans lequel on recherche
     */
    public function __construct(private string $namespace, private string $defaultController, private string $defaultMethod)
    {
    }

    /**
     * @return array<int, array<int, string>>
     * @phpstan-return list<list<string>>
     */
    public function get(): array
    {
        $finder = new ControllerFinder($this->namespace);
        $reader = new ControllerMethodReader($this->namespace);

        $tbody = [];

        foreach ($finder->find() as $class) {
            $output = $reader->read(
                $class,
                $this->defaultController,
                $this->defaultMethod
            );

            foreach ($output as $item) {
                $tbody[] = [
                    'auto',
                    $item['route'],
                    '',
                    $item['handler'],
                ];
            }
        }

        return $tbody;
    }
}
