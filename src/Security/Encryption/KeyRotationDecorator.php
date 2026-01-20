<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Security\Encryption;

use BlitzPHP\Contracts\Security\EncrypterInterface;
use BlitzPHP\Exceptions\EncryptionException;

/**
 * Décorateur de Rotation de Clés
 *
 * Enveloppe toute implémentation d'EncrypterInterface pour fournir un fallback automatique
 * vers les clés de chiffrement précédentes lors du déchiffrement. Cela permet une rotation
 * de clé transparente sans nécessiter le re-chiffrement des données existantes.
 */
class KeyRotationDecorator implements EncrypterInterface
{
    /**
     * @param EncrypterInterface $innerHandler  Le gestionnaire de chiffrement enveloppé
     * @param list<string>       $previousKeys  Tableau des clés de chiffrement précédentes
     */
    public function __construct(private readonly EncrypterInterface $innerHandler, private readonly array $previousKeys)
	{
    }

    /**
     * {@inheritDoc}
     */
    public function encrypt(string $data, array|string|null $params = null): string
    {
        return $this->innerHandler->encrypt($data, $params);
    }

    /**
     * {@inheritDoc}
     *
     * Tente d'abord le déchiffrement avec la clé actuelle. Si cela échoue et qu'aucune
     * clé explicite n'a été fournie dans $params, essaie chaque clé précédente.
     *
     * @throws EncryptionException
     */
   	public function decrypt(string $data, array|string|null $params = null): string
    {
        try {
            return $this->innerHandler->decrypt($data, $params);
        } catch (EncryptionException $e) {
			// Si une clé explicite est fournie, ne pas tenter les anciennes clés
			if ($this->hasExplicitKey($params)) {
				throw $e;
			}

			return $this->tryWithPreviousKeys($data, $params, $e);
        }
    }

	/**
     * {@inheritDoc}
     */
    public function getKey(): string
    {
        return $this->innerHandler->getKey();
    }

    /**
     * Délègue l'accès aux propriétés au gestionnaire interne.
     *
     * @param string $key Nom de la propriété
	 *
     * @return array|bool|int|string|null
     */
    public function __get(string $key)
    {
        if (method_exists($this->innerHandler, '__get')) {
            return $this->innerHandler->__get($key);
        }

        return null;
    }

    /**
     * Délègue la vérification d'existence de propriété au gestionnaire interne.
     *
     * @param string $key Nom de la propriété
     */
    public function __isset(string $key): bool
    {
        if (method_exists($this->innerHandler, '__isset')) {
            return $this->innerHandler->__isset($key);
        }

        return false;
    }

    /**
     * Vérifie si une clé explicite a été fournie dans les paramètres.
     *
     * @param array|string|null $params Paramètres de déchiffrement
	 *
     * @return bool True si une clé explicite est présente
     */
	private function hasExplicitKey(array|string|null $params): bool
	{
		if (is_string($params)) {
			return true;
		}

		if (is_array($params)) {
			return isset($params['key']) && $params['key'] !== null;
		}

		return false;
	}

    /**
     * Tente le déchiffrement avec chaque clé précédente.
     *
     * @param string $data Données à déchiffrer
     * @param array|string|null $params Paramètres de déchiffrement
     * @param EncryptionException $original Exception originale à lancer si tout échoue
	 *
     * @return string Données déchiffrées
	 *
     * @throws EncryptionException
     */
	private function tryWithPreviousKeys(string $data, array|string|null $params, EncryptionException $original): string
	{
		if ($this->previousKeys === []) {
			throw $original;
		}

		foreach ($this->previousKeys as $previousKey) {
			try {
				$previousParams = $this->prepareParamsWithKey($params, $previousKey);
				return $this->innerHandler->decrypt($data, $previousParams);
			} catch (EncryptionException) {
				continue;
			}
		}

		throw $original;
	}

    /**
     * Prépare les paramètres de déchiffrement avec une clé spécifique.
     *
     * @param array|string|null $params Paramètres originaux
     * @param string $key Clé à utiliser
	 *
     * @return array|string Paramètres mis à jour
     */
	private function prepareParamsWithKey(array|string|null $params, string $key): array|string
	{
		if (is_array($params)) {
			return array_merge($params, ['key' => $key]);
		}

		return $key;
	}
}
