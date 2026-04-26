<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\View\Components;

use DOMDocument;
use DOMXPath;

class Slot
{
    /**
     * Stocke les contenus des slots capturés.
     *
     * @var array<string, string>
     */
    protected array $slots = [];

    /**
     * Stocke les noms des slots capturés.
     *
     * @var list<string>
     */
    protected array $stack = [];

    /**
     * Démarre la capture d’un slot nommé (pour les vues natives).
     */
    public function start(string $name): void
    {
        $this->stack[] = $name;

        ob_start();
    }

    /**
     * Arrête la capture du slot courant.
     */
    public function stop(): void
    {
        $name = array_pop($this->stack);

        $this->slots[$name] = ob_get_clean();
    }

    /**
     * Arrête la capture du slot courant.
     *
     * @alias self::stop
     */
    public function end(): void
    {
        $this->stop();
    }

    /**
     * Récupère le contenu d’un slot (ou une valeur par défaut).
     */
    public function get(string $name, string $default = ''): string
    {
        return $this->slots[$name] ?? $default;
    }

    /**
     * Retourne tous les slots.
     */
    public function all(): array
    {
        return $this->slots;
    }

    /**
     * Extrait les slots d’un contenu HTML (balises <x-slot>).
     *
     * @return array{slots: array, default: string}
     */
    public static function extractFromHtml(string $html): array
    {
        $wrapped = '<x-template>' . $html . '</x-template>';

        $doc = new DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($wrapped, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($doc);
        $slots = [];

        // Rechercher tous les nœuds <x-slot> enfants directs du <x-template> racine
        $nodes = iterator_to_array($xpath->query('//x-template/x-slot'));

        foreach ($nodes as $node) {
            $name = $node->getAttribute('name');
            if ($name !== '') {
                // Récupérer le contenu interne (les enfants du <x-slot>)
                $inner = '';

                foreach ($node->childNodes as $child) {
                    $inner .= $doc->saveHTML($child);
                }
                $slots[$name] = trim($inner);
                // Supprimer le nœud <x-slot> de son parent
                $node->parentNode->removeChild($node);
            }
        }

        // Récupérer le contenu par défaut (tout ce qui reste dans le <div> après suppression)
        $default  = '';
        $template = $xpath->query('//x-template')->item(0);
        if ($template) {
            foreach ($template->childNodes as $child) {
                $default .= $doc->saveHTML($child);
            }
        }

        return ['slots' => $slots, 'default' => trim($default)];
    }
}
