<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cache;

/**
 * L'interface de cache de BlitzPHP
 */
interface CacheInterface extends \Psr\SimpleCache\CacheInterface
{
    /**
     * Écrivez les données de la clé dans un moteur de cache si elles n'existent pas déjà.
     *
     * @param mixed $value Tout sauf une ressource.
     *
     * @return bool Vrai si les données ont été mises en cache avec succès, faux en cas d'échec.
     *              Ou si la clé existait déjà.
     */
    public function add(string $key, mixed $value): bool;

    /**
     * Incrémenter un nombre sous la clé et renvoyer la valeur incrémentée
     *
     * @param int $offset Combien ajouter
     *
     * @return false|int Nouvelle valeur incrémentée, false sinon
     */
    public function increment(string $key, int $offset = 1);

    /**
     * Décrémenter un nombre sous la clé et renvoyer la valeur décrémentée
     *
     * @param int $offset Combien soustraire
     *
     * @return false|int Nouvelle valeur incrémentée, false sinon
     */
    public function decrement(string $key, int $offset = 1);

    /**
     * Efface toutes les valeurs appartenant au groupe nommé.
     *
     * Chaque implémentation doit décider si réellement
     * supprimer les clés ou simplement augmenter une valeur de génération de groupe
     * pour obtenir le même résultat.
     */
    public function clearGroup(string $group): bool;

	/**
     * Renvoie des informations sur l'ensemble du cache.
     *
     * Les informations retournées et la structure des données
     * varie selon le gestionnaire.
     *
     * @return array|false|object|null
     */
    public function info();
}
