<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Contracts\Session;

/**
 * Comportement attendu d'un conteneur de session utilisé avec BlitzPHP.
 *
 * @credit <a href="http://codeigniter.com">CodeIgniter - Session</a>
 */
interface SessionInterface
{
    /**
     * Régénère l'ID de session.
     *
     * @param bool $destroy Les anciennes données de session doivent-elles être détruites ?
     */
    public function regenerate(bool $destroy = false): void;

    /**
     * Détruit la session en cours.
     */
    public function destroy(): bool;

    /**
     * Définit les données utilisateur dans la session.
     *
     * Si $data est une chaîne, alors elle est interprétée comme une
     * clé de propriété de session, et $value devrait être non nul.
     *
     * Si $data est un tableau, on s'attend à ce qu'il s'agisse
     * d'un tableau de paires clé/valeur à définir comme propriétés de session.
     *
     * @param array|string                            $data  Nom de propriété ou tableau associatif de propriétés
     * @param array|bool|float|int|object|string|null $value Valeur de la propriété si une seule clé est fournie
     */
    public function set(array|string $data, mixed $value = null): void;

    /**
     * Obtenez les données utilisateur qui ont été définies dans la session.
     *
     * Si la propriété existe en tant que "normale", la renvoie.
     * Sinon, renvoie un tableau de toutes les valeurs de données temporaires ou flash avec la clé de propriété.
     *
     * @param string $key Identifiant de la propriété de session à récupérer
     *
     * @return array|bool|float|int|object|string|null La ou les valeurs de la propriété
     */
    public function get(?string $key = null): mixed;

    /**
     * Retourne si un index existe dans le tableau de session.
     *
     * @param string $key Identifiant de la propriété de session qui nous intéresse.
     */
    public function has(string $key): bool;

    /**
     * Supprimer une ou plusieurs propriétés de session.
     *
     * Si $key est un tableau, il est interprété comme un tableau d'identificateurs de
     * propriété de chaîne à supprimer. Sinon, il est interprété comme l'identifiant
     * d'une propriété de session spécifique à supprimer.
     *
     * @param array|string $key Identifiant de la ou des propriétés de session à supprimer.
     */
    public function remove(array|string $key): void;

    /**
     * Définit les données dans la session qui ne dureront que pour une seule demande.
     * Parfait pour une utilisation avec des messages de mise à jour de statut à usage unique.
     *
     * Si $data est un tableau, il est interprété comme un tableau associatif de paires clé/valeur pour les propriétés flashdata.
     * Sinon, il est interprété comme l'identifiant d'une propriété flashdata spécifique, avec $value contenant la valeur de la propriété.
     *
     * @param array|string                            $data  Identificateur de propriété ou tableau associatif de propriétés
     * @param array|bool|float|int|object|string|null $value Valeur de la propriété si $data est un scalaire
     */
    public function setFlashdata(array|string $data, array|bool|float|int|object|string|null $value = null): void;

    /**
     * Récupérez un ou plusieurs éléments de données flash de la session.
     *
     * Si la clé de l'élément est nulle, renvoie toutes les données flash.
     *
     * @param string $key Identificateur de propriété
     *
     * @return array|null La valeur de la propriété demandée, ou un tableau associatif de celles-ci
     */
    public function getFlashdata(?string $key = null): ?array;

    /**
     * Maintient un seul élément de données flash en vie pour une requête supplémentaire.
     *
     * @param array|string $key Identificateur de propriété ou tableau d'entre eux
     */
    public function keepFlashdata(array|string $key): void;

    /**
     * Marquez une ou plusieurs propriétés de session comme données flash.
     *
     * @param array|string $key Identificateur de propriété ou tableau d'entre eux
     *
     * @return false si l'une des propriétés n'est pas déjà définie
     */
    public function markAsFlashdata(array|string $key): bool;

    /**
     * Décochez les données de la session en tant que données flash.
     *
     * @param array|string $key Identificateur de propriété ou tableau d'entre eux
     */
    public function unmarkFlashdata(array|string $key);

    /**
     * Récupérez toutes les clés des données de session marquées comme données flash.
     *
     * @return array Les noms de propriété de toutes les données flash
     */
    public function getFlashKeys(): array;

    /**
     * Définit de nouvelles données dans la session et les marque comme données temporaires
     * avec une durée de vie définie.
     *
     * @param array|string                            $data  Clé de données de session ou tableau associatif d'éléments
     * @param array|bool|float|int|object|string|null $value Valeur à stocker
     * @param int                                     $ttl   Durée de vie en secondes
     */
    public function setTempdata(array|string $data, array|bool|float|int|object|string|null $value = null, int $ttl = 300): void;

    /**
     * Renvoie soit un seul élément de données temporaires, soit toutes les données temporaires actuellement
     * en session.
     *
     * @param string $key Clé de données de session
     *
     * @return array|bool|float|int|object|string|null Valeur des données de session ou null si introuvable.
     */
    public function getTempdata(?string $key = null);

    /**
     * Supprime une seule donnée temporaire de la session.
     */
    public function removeTempdata(string $key): void;

    /**
     * Marquer une ou plusieurs données comme étant temporaires, ce qui signifie que
     * il a une durée de vie définie au sein de la session.
     *
     * @param array|string $key Identificateur de propriété ou tableau d'entre eux
     * @param int          $ttl Durée de vie, en secondes
     *
     * @return bool False si l'une des propriétés n'a pas été définie
     */
    public function markAsTempdata(array|string $key, int $ttl = 300): bool;

    /**
     * Décoche les données temporaires de la session, supprimant ainsi leur
     * durée de vie et lui permettant de vivre aussi longtemps que la session.
     *
     * @param array|string $key Identificateur de propriété ou tableau d'entre eux
     */
    public function unmarkTempdata(array|string $key): void;

    /**
     * Récupérez les clés de toutes les données de session qui ont été marquées comme données temporaires.
     */
    public function getTempKeys(): array;
}
