<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Publisher;

use RuntimeException;

/**
 * Remplacer le contenu du texte
 * 
 * @credit <a href="http://codeigniter.com">CodeIgniter 4 - \CodeIgniter\Publisher\ContentReplacer</a>
 */
class ContentReplacer
{
    /**
     * Remplacer le contenu
     *
     * @param array $replaces [search => replace]
     */
    public function replace(string $content, array $replaces): string
    {
        return strtr($content, $replaces);
    }

    /**
     * Ajouter du texte
     *
     * @param string $text Texte à ajouter.
     * @param string $pattern Modèle de recherche d'expression régulière.
     * @param string $replace Remplacement de Regexp incluant le texte à ajouter.
     *
     * @return string|null Contenu mis à jour, ou null si non mis à jour.
     */
    private function add(string $content, string $text, string $pattern, string $replace): ?string
    {
        $return = preg_match('/' . preg_quote($text, '/') . '/u', $content);

        if ($return === false) {
            // Erreur d'expression régulière.
            throw new RuntimeException('Erreur d\'expression régulière. Code d\'erreur PCRE: ' . preg_last_error());
        }

        if ($return === 1) {
            // Il a déjà été mis à jour.
            return null;
        }

        $return = preg_replace($pattern, $replace, $content);

        if ($return === null) {
            // Erreur d'expression régulière.
            throw new RuntimeException('Erreur d\'expression régulière. Code d\'erreur PCRE: ' . preg_last_error());
        }

        return $return;
    }

    /**
     * Ajouter une ligne après la ligne avec la chaîne
     *
     * @param string $content Contenu entier.
     * @param string $line Ligne à ajouter.
     * @param string $after Chaîne à rechercher.
     *
     * @return string|null Contenu mis à jour, ou null si non mis à jour.
     */
    public function addAfter(string $content, string $line, string $after): ?string
    {
        $pattern = '/(.*)(\n[^\n]*?' . preg_quote($after, '/') . '[^\n]*?\n)/su';
        $replace = '$1$2' . $line . "\n";

        return $this->add($content, $line, $pattern, $replace);
    }

    /**
     * Ajouter une ligne avant la ligne avec la chaîne
     *
     * @param string $content Contenu entier.
     * @param string $line Ligne à ajouter.
     * @param string $before String à rechercher.
     *
     * @return string|null Contenu mis à jour, ou null si non mis à jour.
     */
    public function addBefore(string $content, string $line, string $before): ?string
    {
        $pattern = '/(\n)([^\n]*?' . preg_quote($before, '/') . ')(.*)/su';
        $replace = '$1' . $line . "\n" . '$2$3';

        return $this->add($content, $line, $pattern, $replace);
    }
}
