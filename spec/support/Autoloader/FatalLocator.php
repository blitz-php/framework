<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Spec\BlitzPHP\Autoloader;

use BlitzPHP\Autoloader\Locator;
use RuntimeException;

/**
 * Un remplacement de localisateur conçu pour lever des exceptions lorsqu'il est utilisé pour indiquer quand la recherche a réellement lieu.
 */
class FatalLocator extends Locator
{
    /**
     * {@inheritDoc}
     */
    public function locateFile(string $file, ?string $folder = null, string $ext = 'php')
    {
        $folder ??= 'null';

        throw new RuntimeException("locateFile({$file}, {$folder}, {$ext})");
    }

    /**
     * {@inheritDoc}
     */
    public function search(string $path, string $ext = 'php', bool $prioritizeApp = true): array
    {
        $prioritizeApp = $prioritizeApp ? 'true' : 'false';

        throw new RuntimeException("search({$path}, {$ext}, {$prioritizeApp})");
    }
}
