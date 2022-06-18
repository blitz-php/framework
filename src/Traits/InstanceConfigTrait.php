<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Traits;

use BlitzPHP\Utilities\Arr;
use Exception;
use InvalidArgumentException;

/**
 * Un trait pour lire et écrire la configuration de l'instance
 *
 * Les objets d'implémentation doivent déclarer une propriété `$_defaultConfig`.
 */
trait InstanceConfigTrait
{
    /**
     * Configuration d'exécution
     *
     * @var array<string, mixed>
     */
    protected $_config = [];

    /**
     * Si la propriété config a déjà été configurée avec les valeurs par défaut
     *
     * @var bool
     */
    protected $_configInitialized = false;

    /**
     * Définit la configuration.
     *
     * ### Utilisation
     *
     * Définition d'une valeur spécifique :
     *
     * ```
     * $this->setConfig('key', $value);
     * ```
     *
     * Définition d'une valeur imbriquée :
     *
     * ```
     * $this->setConfig('some.nested.key', $value);
     * ```
     *
     * Mise à jour de plusieurs paramètres de configuration en même temps :
     *
     * ```
     * $this->setConfig(['one' => 'value', 'other' => 'value']);
     * ```
     *
     * @param array<string, mixed>|string $key   La clé à définir, ou un tableau complet de configurations.
     * @param bool                        $merge Que ce soit pour fusionner ou écraser de manière récursive la configuration existante, la valeur par défaut est true.
     * @param mixed|null                  $value
     *
     * @throws Exception Lorsque vous essayez de définir une clé qui n'est pas valide.
     */
    public function setConfig($key, $value = null, bool $merge = true): self
    {
        if (! $this->_configInitialized) {
            $this->_config            = $this->_defaultConfig;
            $this->_configInitialized = true;
        }

        $this->_configWrite($key, $value, $merge);

        return $this;
    }

    /**
     * Renvoie la configuration.
     *
     * ### Utilisation
     *
     * Lecture de toute la config :
     *
     * ```
     * $this->getConfig();
     * ```
     *
     * Lecture d'une valeur spécifique :
     *
     * ```
     * $this->getConfig('key');
     * ```
     *
     * Lecture d'une valeur imbriquée :
     *
     * ```
     * $this->getConfig('some.nested.key');
     * ```
     *
     * Lecture avec valeur par défaut :
     *
     * ```
     * $this->getConfig('some-key', 'default-value');
     * ```
     *
     * @param mixed|null $default
     *
     * @return mixed Données de configuration à la clé nommée ou null si la clé n'existe pas.
     */
    public function getConfig(?string $key = null, $default = null)
    {
        if (! $this->_configInitialized) {
            $this->_config            = $this->_defaultConfig;
            $this->_configInitialized = true;
        }

        $return = $this->_configRead($key);

        return $return ?? $default;
    }

    /**
     * Renvoie la configuration pour cette clé spécifique.
     *
     * La valeur de configuration de cette clé doit exister, elle ne peut jamais être nulle.
     *
     * @throws InvalidArgumentException
     */
    public function getConfigOrFail(string $key)
    {
        $config = $this->getConfig($key);
        if ($config === null) {
            throw new InvalidArgumentException(sprintf('Expected configuration `%s` not found.', $key));
        }

        return $config;
    }

    /**
     * Fusionnez la configuration fournie avec la configuration existante. Contrairement à `config()` qui fait
     * une fusion récursive pour les clés imbriquées, cette méthode effectue une fusion simple.
     *
     * Définition d'une valeur spécifique :
     *
     * ```
     * $this->configShallow('key', $value);
     * ```
     *
     * Définition d'une valeur imbriquée :
     *
     * ```
     * $this->configShallow('some.nested.key', $value);
     * ```
     *
     * Mise à jour de plusieurs paramètres de configuration en même temps :
     *
     * ```
     * $this->configShallow(['one' => 'value', 'other' => 'value']);
     * ```
     *
     * @param array<string, mixed>|string $key   La clé à définir, ou un tableau complet de configurations.
     * @param mixed|null                  $value
     */
    public function configShallow($key, $value = null): self
    {
        if (! $this->_configInitialized) {
            $this->_config            = $this->_defaultConfig;
            $this->_configInitialized = true;
        }

        $this->_configWrite($key, $value, 'shallow');

        return $this;
    }

    /**
     * Lit une clé de configuration.
     */
    protected function _configRead(?string $key)
    {
        if ($key === null) {
            return $this->_config;
        }

        if (strpos($key, '.') === false) {
            return $this->_config[$key] ?? null;
        }

        $return = $this->_config;

        foreach (explode('.', $key) as $k) {
            if (! is_array($return) || ! isset($return[$k])) {
                $return = null;
                break;
            }

            $return = $return[$k];
        }

        return $return;
    }

    /**
     * Écrit une clé de configuration.
     *
     * @param array<string, mixed>|string $key   Clé sur laquelle écrire.
     * @param bool|string                 $merge True pour fusionner de manière récursive, 'shallow' pour une fusion simple,
     *                                           false pour écraser, la valeur par défaut est false.
     * @param mixed                       $value
     *
     * @throws Exception si vous tentez d'écraser la configuration existante
     */
    protected function _configWrite($key, $value, $merge = false): void
    {
        if (is_string($key) && $value === null) {
            $this->_configDelete($key);

            return;
        }

        if ($merge) {
            $update = is_array($key) ? $key : [$key => $value];
            if ($merge === 'shallow') {
                $this->_config = array_merge($this->_config, Arr::expand($update));
            } else {
                $this->_config = Arr::merge($this->_config, Arr::expand($update));
            }

            return;
        }

        if (is_array($key)) {
            foreach ($key as $k => $val) {
                $this->_configWrite($k, $val);
            }

            return;
        }

        if (strpos($key, '.') === false) {
            $this->_config[$key] = $value;

            return;
        }

        $update = &$this->_config;
        $stack  = explode('.', $key);

        foreach ($stack as $k) {
            if (! is_array($update)) {
                throw new Exception(sprintf('Cannot set %s value', $key));
            }

            $update[$k] ??= [];

            $update = &$update[$k];
        }

        $update = $value;
    }

    /**
     * Supprime une seule clé de configuration.
     *
     * @throws Exception si vous tentez d'écraser la configuration existante
     */
    protected function _configDelete(string $key): void
    {
        if (strpos($key, '.') === false) {
            unset($this->_config[$key]);

            return;
        }

        $update = &$this->_config;
        $stack  = explode('.', $key);
        $length = count($stack);

        foreach ($stack as $i => $k) {
            if (! is_array($update)) {
                throw new Exception(sprintf('Cannot unset %s value', $key));
            }

            if (! isset($update[$k])) {
                break;
            }

            if ($i === $length - 1) {
                unset($update[$k]);
                break;
            }

            $update = &$update[$k];
        }
    }
}
