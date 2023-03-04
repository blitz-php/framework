<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Contracts\Database;

use InvalidArgumentException;
use PDO;

/**
 * Interface BuilderInterface
 */
interface BuilderInterface
{
    /**
     * Constructor
     */
    public function __construct(ConnectionInterface $db, ?array $options = null);

    /**
     * Renvoie la connexion actuelle à la base de données
     */
    public function db(): ConnectionInterface;

    /**
     * Définit un statut de mode de test.
     */
    public function testMode(bool $mode = true): self;

    /**
     * Recupere le nom de la table principale.
     */
    public function getTable(): string;

    /**
     * Génère la partie FROM de la requête
     *
     * @param string|string[]|null $from
     */
    public function from($from, bool $overwrite = false): self;

    /**
     *Génère la partie FROM de la requête
     *
     * @param string|string[]|null $from
     *
     * @alias self::from()
     */
    public function table($from): self;

    /**
     * Définit la table dans laquelle les données seront insérées
     */
    public function into(string $table): self;

    public function fromSubquery(self $builder, string $alias = ''): self;

    /**
     * Génère la partie JOIN de la requête
     *
     * @param string       $table  Table à joindre
     * @param array|string $fields Champs à joindre
     *
     * @throws InvalidArgumentException Lorsque $fields est une chaine et qu'aucune table n'a ete au prealable definie
     */
    public function join(string $table, array|string $fields, string $type = 'INNER', bool $escape = false): self;

    /**
     * Génère la partie WHERE de la requête.
     * Sépare plusieurs appels avec 'AND'.
     *
     * @param array|string $field Un nom de champ ou un tableau de champs et de valeurs.
     * @param mixed        $value Une valeur de champ à comparer
     */
    public function where($field, $value = null, bool $escape = true): self;

    /**
     * Ajouter des champs pour les tri
     *
     * @param string|string[] $field Un nom de champ ou un tableau de champs
     */
    public function orderBy(string|array $field, string $direction = 'ASC', bool $escape = true): self;

    /**
     * Ajoute des champs à regrouper.
     *
     * @param string|string[] $field Nom de champ ou tableau de noms de champs
     */
    public function groupBy($field, bool $escape = true): self;

    /**
     * Ajoute des conditions de type HAVING.
     * Sépare plusieurs appels avec 'AND'.
     *
     * @param array|string $field Un nom de champ ou un tableau de champs et de valeurs.
     * @param string       $value Une valeur de champ à comparer
     */
    public function having($field, $value = null, bool $escape = true): self;

    /**
     * Ajoute une limite à la requête.
     */
    public function limit(int $limit, ?int $offset = null): self;

    /**
     * Ajoute un décalage à la requête.
     */
    public function offset(int $offset, ?int $limit = null): self;

    /**
     * Définit un indicateur qui indique au compilateur de chaîne de requête d'ajouter DISTINCT.
     */
    public function distinct(bool $value = true): self;

    /**
     * Construit une requête de sélection.
     *
     * @param string|string[] $fields Nom de champ ou tableau de noms de champs à sélectionner
     */
    public function select($fields = '*', ?int $limit = null, ?int $offset = null): self;

    /**
     * Définit un indicateur qui indique au compilateur de chaîne de requête d'ajouter IGNORE.
     */
    public function ignore(bool $value = true): self;

    /**
     * Construit une requête d'insertion.
     *
     * @param array|object $data    Tableau ou objet de clés et de valeurs à insérer
     * @param bool         $execute Spécifié si nous voulons exécuter directement la requête
     *
     * @return ResultInterface|self|string
     */
    public function insert(array|object $data = [], bool $escape = true, bool $execute = true);

    /**
     * Construit une requête de mise à jour.
     *
     * @param array|object|string $data    Tableau ou objet de clés et de valeurs, ou chaîne littérale
     * @param bool                $execute Spécifié si nous voulons exécuter directement la requête
     *
     * @return ResultInterface|self|string
     */
    public function update(array|string|object $data = [], bool $escape = true, bool $execute = true);

    /**
     * Construit une requête de remplacement (REPLACE INTO).
     *
     * @param array|object $data    Tableau ou objet de clés et de valeurs à remplacer
     * @param bool         $execute Spécifié si nous voulons exécuter directement la requête
     *
     * @return ResultInterface|self|string
     */
    public function replace(array|object $data = [], bool $escape = true, bool $execute = true);

    /**
     * Construit une requête de suppression.
     *
     * @param array $where   Conditions de suppression
     * @param bool  $execute Spécifié si nous voulons exécuter directement la requête
     *
     * @return ResultInterface|self|string
     */
    public function delete(?array $where = null, ?int $limit = null, bool $execute = true);

    /**
     * Allows key/value pairs to be set for insert(), update() or replace().
     *
     * @param array|object|string $key   Nom du champ, ou tableau de paire champs/valeurs
     * @param mixed               $value Valeur du champ, si $key est un simple champ
     */
    public function set($key, $value = '', ?bool $escape = null): self;

    /**
     * Obtient la valeur minimale d'un champ spécifié.
     *
     * @param string|null $key    Clé de cache
     * @param int         $expire Délai d'expiration en secondes
     *
     * @return float|string float en mode reel et string (la chaîne SQL) en mode test
     */
    public function min(string $field, ?string $key = null, int $expire = 0);

    /**
     * Obtient la valeur maximale d'un champ spécifié.
     *
     * @param string|null $key    Clé de cache
     * @param int         $expire Délai d'expiration en secondes
     *
     * @return float|string float en mode reel et string (la chaîne SQL) en mode test
     */
    public function max(string $field, ?string $key = null, int $expire = 0);

    /**
     * Obtient la somme des valeurs d'un champ spécifié.
     *
     * @param string|null $key    Clé de cache
     * @param int         $expire Délai d'expiration en secondes
     *
     * @return float|string float en mode reel et string (la chaîne SQL) en mode test
     */
    public function sum(string $field, ?string $key = null, int $expire = 0);

    /**
     * Obtient la valeur moyenne pour un champ spécifié.
     *
     * @param string|null $key    Clé de cache
     * @param int         $expire Délai d'expiration en secondes
     *
     * @return float|string float en mode reel et string (la chaîne SQL) en mode test
     */
    public function avg(string $field, ?string $key = null, int $expire = 0);

    /**
     * Obtient le nombre d'enregistrements pour une table.
     *
     * @param string|null $key    Clé de cache
     * @param int         $expire Délai d'expiration en secondes
     *
     * @return int|string int en mode reel et string (la chaîne SQL) en mode test
     */
    public function count(string $field = '*', ?string $key = null, int $expire = 0);

    // Méthodes d'extraction de données

    /**
     * Execute une requete sql donnée
     *
     * @return bool|QueryInterface|ResultInterface BaseResult quand la requete est de type "lecture", bool quand la requete est de type "ecriture", Query quand on a une requete preparee
     */
    public function query(string $sql, array $params = []);

    /**
     * Exécute une instruction sql.
     *
     * @param string|null $key    Clé de cache
     * @param int         $expire Délai d'expiration en secondes
     *
     * @return bool|QueryInterface|ResultInterface BaseResult quand la requete est de type "lecture", bool quand la requete est de type "ecriture", Query quand on a une requete preparee
     */
    public function execute(?string $key = null, int $expire = 0);

    /**
     * Recupere plusieurs lignes des resultats de la reauete select.
     *
     * @param string|null $key    Clé de cache
     * @param int         $expire Délai d'expiration en secondes
     */
    public function result(int|string $type = PDO::FETCH_OBJ, ?string $key = null, int $expire = 0): array;

    /**
     * Recupere plusieurs lignes des resultats de la reauete select.
     *
     * @param int|string  $type
     * @param string|null $key    Clé de cache
     * @param int         $expire Délai d'expiration en secondes
     *
     * @alias self::result()
     */
    public function all($type = PDO::FETCH_OBJ, ?string $key = null, int $expire = 0): array;

    /**
     * Recupere la premiere ligne des resultats de la requete select..
     *
     * @param int|string  $type
     * @param string|null $key    Clé de cache
     * @param int         $expire Délai d'expiration en secondes
     *
     * @return mixed
     */
    public function first($type = PDO::FETCH_OBJ, ?string $key = null, int $expire = 0);

    /**
     * Recupere un resultat precis dans les resultat d'une requete en BD
     *
     * @param int|string  $type
     * @param string|null $key    Clé de cache
     * @param int         $expire Délai d'expiration en secondes
     *
     * @return mixed La ligne souhaitee
     */
    public function row(int $index, $type = PDO::FETCH_OBJ, ?string $key = null, int $expire = 0);

    /**
     * Recupere la valeur d'un ou de plusieurs champs.
     *
     * @param string|string[] $name   Le nom du/des champs de la base de donnees
     * @param string|null     $key    Cle du cache
     * @param int             $expire Délai d'expiration en secondes
     *
     * @return mixed|mixed[] La valeur du/des champs
     */
    public function value(string|array $name, ?string $key = null, int $expire = 0);

    /**
     * Recupere les valeurs d'un ou de plusieurs champs.
     *
     * @param string|string[] $name   Le nom du/des champs de la base de donnees
     * @param string|null     $key    Cle du cache
     * @param int             $expire Délai d'expiration en secondes
     *
     * @return mixed[] La/les valeurs du/des champs
     */
    public function values(string|array $name, ?string $key = null, int $expire = 0): array;

    /**
     * Recupere la requete sql courrante et reinitialise le builder.
     */
    public function sql(bool $preserve = false): string;
}
