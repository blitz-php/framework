<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Formatter;

use BlitzPHP\Exceptions\FormatException;
use SimpleXMLElement;

/**
 * Formateur de données XML
 */
class XmlFormatter implements FormatterInterface
{
    public function __construct()
    {
        // SimpleXML est installé par défaut, mais il est préférable de vérifier, puis de fournir une solution de repli.
        if (! extension_loaded('simplexml')) {
            throw FormatException::missingExtension();
        }

        helper('inflector');
    }

    /**
     * {@inheritDoc}
     *
     * @return false|string Représentation XML d'une valeur
     *                      false en cas d'erreur de formattage
     */
    public function format($data)
    {
        $basenode  = 'xml';
        $structure = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><{$basenode} />");

        $this->arrayToXml((array) $data, $structure, $basenode);

        return $structure->asXML();
    }

    /**
     * {@inheritDoc}
     *
     * @param string $data Chaine XML
     */
    public function parse(string $data): array
    {
        $xml = @simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($xml === false) {
            return [];
        }

        $json = json_encode($xml);

        return json_decode($json, true);
    }

    /**
     * Une méthode récursive pour convertir un tableau en une chaîne XML valide.
     */
    protected function arrayToXml(array $data, SimpleXMLElement &$structure, string $basenode): void
    {
        foreach ($data as $key => $value) {
            // change false/true en 0/1
            if (is_bool($value)) {
                $value = (int) $value;
            }

            // pas de touches numériques dans notre xml s'il vous plait !
            if (is_numeric($key)) {
                // crée une clé de chaîne...
                $key = singular($basenode) !== $basenode ? singular($basenode) : 'item';
            }

            // remplace tout ce qui n'est pas alphanumérique
            $key = preg_replace('/[^a-z_\-0-9]/i', '', $key);

            if ($key === '_attributes' && (is_array($value) || is_object($value))) {
                $attributes = $value;
                if (is_object($attributes)) {
                    $attributes = get_object_vars($attributes);
                }

                foreach ($attributes as $attribute_name => $attribute_value) {
                    $structure->addAttribute($attribute_name, $attribute_value);
                }
            }
            // s'il y a un autre tableau trouvé appelez récursivement cette fonction
            elseif (is_array($value) || is_object($value)) {
                $node = $structure->addChild($key);

                // appel récursif
                $this->arrayToXml($value, $node, $key);
            } else {
                // ajouter un seul noeud
                $value = htmlspecialchars(html_entity_decode($value ?? '', ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');

                $structure->addChild($key, $value);
            }
        }
    }
}
