<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\HotReloader;

use RecursiveFilterIterator;
use RecursiveIterator;

/**
 * @internal
 *
 * @psalm-suppress MissingTemplateParam
 * 
 * @credit	<a href="https://codeigniter.com">CodeIgniter 4.6 - CodeIgniter\HotReloader\IteratorFilter</a>
 */
final class IteratorFilter extends RecursiveFilterIterator implements RecursiveIterator
{
    private array $watchedExtensions = [];

    public function __construct(RecursiveIterator $iterator)
    {
        parent::__construct($iterator);

        $this->watchedExtensions = config('toolbar.watched_extensions', []);
    }

    /**
     * Appliquer des filtres aux fichiers dans l'itérateur.
     */
    public function accept(): bool
    {
        if (! $this->current()->isFile()) {
            return true;
        }

        $filename = $this->current()->getFilename();

        // Ignorer les fichiers et répertoires cachés.
        if ($filename[0] === '.') {
            return false;
        }

        // Ne consommez que les fichiers qui vous intéressent.
        $ext = trim(strtolower($this->current()->getExtension()), '. ');

        return in_array($ext, $this->watchedExtensions, true);
    }
}
