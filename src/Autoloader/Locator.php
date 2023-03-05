<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Autoloader;

/**
 * Fourni un chargeur pour les fichiers qui ne sont pas des classes dans un namespace.
 * Fonctionne avec les Helpers, Views, etc.
 * 
 * @credit 		<a href="https://codeigniter.com">CodeIgniter4 - CodeIgniter\Autoloader\FileLocator</a>
 */

class Locator
{
    /**
     * Autoloader a utiliser.
     */
    protected Autoloader $autoloader;

    public function __construct(Autoloader $autoloader)
    {
        $this->setAutoloader($autoloader);
    }
    
    public function setAutoloader(Autoloader $autoloader): self
    {
        $this->autoloader = $autoloader;

        return $this;
    }

    /**
     * Scane les namespace definis, retourne une liste de tous les fichiers
     * contenant la sous partie specifiee par $path.
     *
     * @return string[] Liste des fichiers du chemins
     */
    public function listFiles(string $path): array
    {
        if (empty($path)) {
            return [];
        }

        $files = [];
        helper('filesystem');

        foreach ($this->getNamespaces() as $namespace) {
            $fullPath = $namespace['path'] . $path;
            $fullPath = realpath($fullPath) ?: $fullPath;

            if (! is_dir($fullPath)) {
                continue;
            }

            $tempFiles = get_filenames($fullPath, true, false, false);

            if (! empty($tempFiles)) {
                $files = array_merge($files, $tempFiles);
            }
        }

        return $files;
    }

    /**
     * Retourne les namespace mappees qu'on connait
     *
     * @return array<int, array<string, string>>
     */
    protected function getNamespaces(): array
    {
        $namespaces = [];

        $system = [];

        foreach ($this->autoloader->getNamespace() as $prefix => $paths) {
            foreach ($paths as $path) {
                if ($prefix === 'BlitzPHP') {
                    $system = [
                        'prefix' => $prefix,
                        'path'   => rtrim($path, '\\/') . DIRECTORY_SEPARATOR,
                    ];

                    continue;
                }

                $namespaces[] = [
                    'prefix' => $prefix,
                    'path'   => rtrim($path, '\\/') . DIRECTORY_SEPARATOR,
                ];
            }
        }

        $namespaces[] = $system;

        return $namespaces;
    }

    /**
     * Examine une fichier et retourne le FQCN.
     */
    public function getClassname(string $file): string
    {
        $php       = file_get_contents($file);
        $tokens    = token_get_all($php);
        $dlm       = false;
        $namespace = '';
        $className = '';

        foreach ($tokens as $i => $token) {
            if ($i < 2) {
                continue;
            }

            if ((isset($tokens[$i - 2][1]) && ($tokens[$i - 2][1] === 'phpnamespace' || $tokens[$i - 2][1] === 'namespace')) || ($dlm && $tokens[$i - 1][0] === T_NS_SEPARATOR && $token[0] === T_STRING)) {
                if (! $dlm) {
                    $namespace = 0;
                }
                if (isset($token[1])) {
                    $namespace = $namespace ? $namespace . '\\' . $token[1] : $token[1];
                    $dlm       = true;
                }
            } elseif ($dlm && ($token[0] !== T_NS_SEPARATOR) && ($token[0] !== T_STRING)) {
                $dlm = false;
            }

            if (($tokens[$i - 2][0] === T_CLASS || (isset($tokens[$i - 2][1]) && $tokens[$i - 2][1] === 'phpclass'))
                && $tokens[$i - 1][0] === T_WHITESPACE
                && $token[0] === T_STRING) {
                $className = $token[1];
                break;
            }
        }

        if (empty($className)) {
            return '';
        }

        return $namespace . '\\' . $className;
    }
}
