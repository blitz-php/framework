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

use BlitzPHP\Exceptions\FrameworkException;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * @internal
 *
 * @credit	<a href="https://codeigniter.com">CodeIgniter 4.6 - CodeIgniter\HotReloader\DirectoryHasher</a>
 */
final class DirectoryHasher
{
    /**
     * Génère une valeur MD5 de tous les répertoires surveillés par le rechargeur à chaud,
     * comme défini dans le fichier app/Config/toolbar.php.
     *
     * Il s'agit de l'empreinte actuelle de l'application.
     */
    public function hash(): string
    {
        return md5(implode('', $this->hashApp()));
    }

    /**
     * Génère un tableau de hachages md5 pour tous les répertoires surveillés par le Hot Reloader,
     * comme défini dans app/Config/toolbar.php.
     */
    public function hashApp(): array
    {
        $hashes = [];

        $watchedDirectories = config('toolbar.watched_directories', []);

        foreach ($watchedDirectories as $directory) {
            if (is_dir(ROOTPATH . $directory)) {
                $hashes[$directory] = $this->hashDirectory(ROOTPATH . $directory);
            }
        }

        return array_unique(array_filter($hashes));
    }

    /**
     * Génère un hachage MD5 d'un répertoire donné et de tous ses fichiers * qui correspondent aux extensions surveillées
     * définies dans app/Config/toolbar.php.
     */
    public function hashDirectory(string $path): string
    {
        if (! is_dir($path)) {
            throw FrameworkException::invalidDirectory($path);
        }

        $directory = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
        $filter    = new IteratorFilter($directory);
        $iterator  = new RecursiveIteratorIterator($filter);

        $hashes = [];

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $hashes[] = md5_file($file->getRealPath());
            }
        }

        return md5(implode('', $hashes));
    }
}
