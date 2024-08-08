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

use SimpleXMLElement;

/**
 * Formateur de données XML
 */
class XmlFormatter implements FormatterInterface
{
    /**
     * @var string
     */
    protected $basenode = 'xml';

    /**
     * @var SimpleXMLElement
     */
    protected $structure;

    /**
     * {@inheritDoc}
     *
     * @return false|string Représentation XML d'une valeur
     *                      false en cas d'erreur de formattage
     */
    public function format($data)
    {
        if (empty($this->structure)) {
            $this->structure = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><{$this->basenode} />");
        }

        // Forcez-le à être quelque chose d'utile
        if (! is_array($data) && ! is_object($data)) {
            $data = (array) $data;
        }

        foreach ($data as $key => $value) {
            // change false/true en 0/1
            if (is_bool($value)) {
                $value = (int) $value;
            }

            // pas de touches numériques dans notre xml s'il vous plait !
            if (is_numeric($key)) {
                helper('inflector');
                // crée une clé de chaîne...
                $key = singular($this->basenode) !== $this->basenode ? singular($this->basenode) : 'item';
            }

            // remplace tout ce qui n'est pas alphanumérique
            $key = preg_replace('/[^a-z_\-0-9]/i', '', $key);

            if ($key === '_attributes' && (is_array($value) || is_object($value))) {
                $attributes = $value;
                if (is_object($attributes)) {
                    $attributes = get_object_vars($attributes);
                }

                foreach ($attributes as $attribute_name => $attribute_value) {
                    $this->structure->addAttribute($attribute_name, $attribute_value);
                }
            }
            // s'il y a un autre tableau trouvé appelez récursivement cette fonction
            elseif (is_array($value) || is_object($value)) {
                $node = $this->structure->addChild($key);

                // appel récursif
                $this->structure = $node;
                $this->basenode  = $key;
                $this->format($value);
            } else {
                // ajouter un seul noeud
                $value = htmlspecialchars(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');

                $this->structure->addChild($key, $value);
            }
        }

        return $this->structure->asXML();
    }

    /**
     * {@inheritDoc}
     *
     * @param string $data Chaine XML
     */
    public function parse(string $data): array
    {
        return $data !== '' && $data !== '0' ? (array) simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA) : [];
    }
}
