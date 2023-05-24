<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Traits;

trait ContentReplacer
{
    /**
     * Chemin source
     */
    protected string $sourcePath = __DIR__ .'/../';

    /**
     * Chemin cible pour le replacement
     */
    protected string $distPath = APP_PATH;
    


    /**
     * Recupere le chemin source complet d'un fichier
     */
    protected function sourcePath(string $file): string
    {
        return str_replace('/', DS, rtrim($this->sourcePath, '/\\') . DS . $file);
    }

    /**
     * Recupere le chemin de destination complet d'un fichier
     */
    protected function distPath(string $file): string
    {
        return str_replace('/', DS, rtrim($this->distPath, '/\\') . DS . $file);
    }

    /**
     * @param string $file     Chemin de fichier relatif comme 'config/auth.php'.
     * @param array  $replaces [search => replace]
     */
    protected function copyAndReplace(string $file, array $replaces): void
    {
        $content = file_get_contents($this->sourcePath($file));
        
        $content = strtr($content, $replaces);

        $this->writeFile($file, $content);
    }
    
    /**
     * Écrivez un fichier, attrapez toutes les exceptions et affichez une erreur bien formatée.
     *
     * @param string $file Chemin de fichier relatif comme 'config/auth.php'.
     */
    protected function writeFile(string $file, string $content): void
    {
        helper('filesystem');

        $path      = $this->distPath($file);
        $cleanPath = clean_path($path);

        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (file_exists($path)) {
            $overwrite = (bool) $this->option('f');

            if (! $overwrite && ! $this->confirm("File '{$cleanPath}' already exists in destination. Overwrite?")) {
                $this->error("Skipped {$cleanPath}. If you wish to overwrite, please use the '-f' option or reply 'y' to the prompt.");

                return;
            }
        }

        if (write_file($path, $content)) {
            $this->success($cleanPath, true, 'Created:');
        } else {
            $this->error("Error creating {$cleanPath}.");
        }
    }

    /**
     *
     * @param string $file     Chemin de fichier relatif comme 'Controllers/BaseController.php'.
     * @param array  $replaces [search => replace]
     */
    protected function replace(string $file, array $replaces): bool
    {
        helper('filesystem');

        $path      = $this->distPath($file);
        $cleanPath = clean_path($path);

        $content = file_get_contents($path);

        $output = strtr($content, $replaces);

        if ($output === $content) {
            return false;
        }

        if (write_file($path, $output)) {
            $this->success($cleanPath, true, 'Updated:');

            return true;
        }

        $this->error("Erreur lors de la mise à jour de {$cleanPath}.");

        return false;
    }

    /**
     * @param string $code Code a ajouter.
     * @param string $file hemin de fichier relatif comme 'Controllers/BaseController.php'.
     */
    protected function addContent(string $file, string $code, string $pattern, string $replace): void
    {
        helper('filesystem');

        $path      = $this->distPath($file);
        $cleanPath = clean_path($path);

        $content = file_get_contents($path);

        $output = $this->_addContent($content, $code, $pattern, $replace);

        if ($output === true) {
            $this->error("{$cleanPath} ignoré. Il a déjà été mis à jour.");

            return;
        }
        if ($output === false) {
            $this->error("Erreur lors de la vérification de {$cleanPath}.");

            return;
        }

        if (write_file($path, $output)) {
            $this->success($cleanPath, true, 'Updated:');
        } else {
            $this->error("Erreur lors de la mise à jour de {$cleanPath}.");
        }
    }

    /**
     * @param string $text    Texte à ajouter.
     * @param string $pattern Modèle de recherche d'expression régulière.
     * @param string $replace Remplacement de Regexp incluant le texte à ajouter.
     *
     * @return bool|string true : déjà mis à jour, false : erreur d'expression régulière.
     */
    private function _addContent(string $content, string $text, string $pattern, string $replace)
    {
        $return = preg_match('/' . preg_quote($text, '/') . '/u', $content);

        if ($return === 1) {
            // Il a déjà été mis à jour.

            return true;
        }

        if ($return === false) {
            // Erreur d'expression régulière.

            return false;
        }

        return preg_replace($pattern, $replace, $content);
    }
}
