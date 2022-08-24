<?php

namespace BlitzPHP\Contracts\Database;

/**
 * ConnectionInterface
 */
interface ConnectionInterface
{
    /**
     * Initialise la connexion/les paramètres de la base de données.
     *
     * @return mixed
     */
    public function initialize();

    /**
     * Connexion à la base de données.
     *
     * @return mixed
     */
    public function connect(bool $persistent = false);

    /**
     * Créez une connexion persistante à la base de données.
     *
     * @return mixed
     */
    public function persistentConnect();

    /**
     * Conservez ou établissez la connexion si aucune requête n'a été envoyée pendant une durée supérieure au délai d'inactivité du serveur.
     *
     * @return mixed
     */
    public function reconnect();

    /**
     * Renvoie l'objet de connexion réel. Si une connexion 'lecture' et 'écriture' a été spécifiée,
	 * vous pouvez transmettre l'un ou l'autre terme pour obtenir cette connexion.
	 * Si vous transmettez l'un ou l'autre des alias et qu'une seule connexion est présente, il doit renvoyer la seule connexion.
     *
     * @return mixed
     */
    public function getConnection(?string $alias = null);

    /**
     * Sélectionnez une table de base de données spécifique à utiliser.
     *
     * @return mixed
     */
    public function setDatabase(string $databaseName);

    /**
     * Renvoie le nom de la base de données en cours d'utilisation.
     */
    public function getDatabase(): string;

    /**
     * Renvoie la dernière erreur rencontrée par cette connexion.
     * Doit retourner ce format : ['code' => string|int, 'message' => string]
     * intval(code) === 0 signifie "pas d'erreur".
     *
     * @return array<string, int|string>
     */
    public function error(): array;

    /**
     * Le nom de la plateforme utilisée (MySQLi, mssql, etc)
     */
    public function getPlatform(): string;

    /**
     * Renvoie une chaîne contenant la version de la base de données utilisée.
     */
    public function getVersion(): string;

    /**
     * Orchestre une requête sur la base de données.
	 * Les requêtes doivent utiliser des objets Database\Statement pour stocker la requête et la construire.
	 * Cette méthode fonctionne avec le cache.
     *
     * Doit gérer automatiquement différentes connexions pour les requêtes de lecture/écriture si nécessaire.
     *
     * @param mixed ...$binds
     *
     * @return ResultInterface|bool|Query
     */
    public function query(string $sql, $binds = null);

    /**
     * Effectue une requête de base sur la base de données.
	 * Aucune liaison ou mise en cache n'est effectuée, et les transactions ne sont pas traitées.
	 * Prend simplement une chaîne de requête brute et renvoie l'ID de résultat spécifique à la base de données.
     *
     * @return mixed
     */
    public function simpleQuery(string $sql);

    /**
     * Renvoie une instance du générateur de requêtes pour cette connexion.
     *
     * @param array|string $tableName
     *
     * @return BaseBuilder Builder.
     */
    public function table($tableName);

    /**
     * Renvoie l'objet d'instruction de la dernière requête.
     *
     * @return mixed
     */
    public function getLastQuery();

    /**
     * Escapade "intelligente"
     *
     * Échappe les données en fonction du type.
     * Définit les types booléens et nuls.
     *
     * @param mixed $str
     *
     * @return mixed
     */
    public function escape($str);

    /**
     * Autorise les appels personnalisés au moteur de base de données qui ne sont pas
     * pris en charge via notre couche de base de données.
     *
     * @param array ...$params
     *
     * @return mixed
     */
    public function callFunction(string $functionName, ...$params);

    /**
     * Détermine si l'instruction est une requête de type écriture ou non.
     *
     * @param string $sql
     */
    public function isWriteType($sql): bool;
}
