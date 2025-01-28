<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Security\Encryption\Handlers;

use BlitzPHP\Contracts\Security\EncrypterInterface;
use BlitzPHP\Utilities\String\Text;

/**
 * Classe de base pour les gestionnaires de chiffrement
 */
abstract class BaseHandler implements EncrypterInterface
{
    /**
     * Clé de démarrage
     */
    protected ?string $key = '';

    /**
     * Constructeur
     */
    public function __construct(?object $config = null)
    {
        $config ??= (object) config('encryption');

        // rendre les paramètres facilement accessibles
        foreach (get_object_vars($config) as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            } elseif (property_exists($this, $key = Text::camel($key))) {
				$this->{$key} = $value;
			}
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Byte-safe substr()
     */
    protected static function substr(string $str, int $start, ?int $length = null): string
    {
        return mb_substr($str, $start, $length, '8bit');
    }

    /**
     * Fourni un accès en lecture seule à certaines de nos propriétés
     *
     * @param string $key Nom de la propriete
     *
     * @return array|bool|int|string|null
     */
    public function __get($key)
    {
        if ($this->__isset($key)) {
            return $this->{$key};
        }

        return null;
    }

    /**
     * Assure la vérification de certaines de nos propriétés
     *
     * @param string $key Nom de la propriete
     */
    public function __isset($key): bool
    {
        return property_exists($this, $key);
    }
}
