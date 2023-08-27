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

use BlitzPHP\Autoloader\Locator;
use BlitzPHP\Container\Services;

/**
 * Recherche tous les contrôleurs dans un namespace pour la liste des routes automatiques.
 */
final class ControllerFinder
{
    private Locator $locator;

    /**
     * @param string $namespace Namespace dans lequel on recherche
     */
    public function __construct(private string $namespace)
    {
        $this->locator = Services::locator();
    }

    /**
     * @return string[]
     */
    public function find(): array
    {
        $nsArray = explode('\\', trim($this->namespace, '\\'));
        $count   = count($nsArray);
        $ns      = '';
        $files   = [];

        for ($i = 0; $i < $count; $i++) {
            $ns .= '\\' . array_shift($nsArray);
            $path = implode('\\', $nsArray);

            $files = $this->locator->listNamespaceFiles($ns, $path);

            if ($files !== []) {
                break;
            }
        }

        $classes = [];

        foreach ($files as $file) {
            if (is_file($file)) {
                $classnameOrEmpty = $this->locator->getClassname($file);

                if ($classnameOrEmpty !== '') {
                    $classname = $classnameOrEmpty;

                    $classes[] = $classname;
                }
            }
        }

        return $classes;
    }
}
