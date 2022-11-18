<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Models;

use BadMethodCallException;
use BlitzPHP\Core\Database;
use BlitzPHP\Database\BaseBuilder;
use BlitzPHP\Database\Contracts\ConnectionInterface;

/**
 * The Model class extends BaseModel and provides additional
 * convenient features that makes working with a SQL database
 * table less painful.
 *
 * It will:
 *      - automatically connect to database
 *      - allow intermingling calls to the builder
 *      - removes the need to use Result object directly in most cases
 *
 * @property ConnectionInterface $db
 *
 * @method array                         all(int|string $type = \PDO::FETCH_OBJ, ?string $key = null, int $expire = 0)
 * @method float                         avg(string $field, ?string $key = null, int $expire = 0)
 * @method $this                         between(string $field, $value1, $value2)
 * @method int                           count(string $field = '*', ?string $key = null, int $expire = 0)
 * @method $this                         distinct()
 * @method \BlitzPHP\Database\BaseResult execute(?string $key = null, int $expire = 0)
 * @method mixed                         first(int|string $type = \PDO::FETCH_OBJ, ?string $key = null, int $expire = 0)
 * @method $this                         from(string|string[]|null $from, bool $overwrite = false)
 * @method $this                         fromSubquery(self $builder, string $alias = '')
 * @method $this                         fullJoin(string $table, array|string $fields)
 * @method $this                         group(string|string[] $field, ?bool $escape = null)
 * @method $this                         groupBy(string|string[] $field, ?bool $escape = null)
 * @method $this                         having(array|string $field, $values = null, ?bool $escape = null)
 * @method $this                         havingIn(string $field, array|callable|self $param)
 * @method $this havingLike(array|string $field, $match = '', string $side = 'both', bool $escape = true, bool $insensitiveSearch = false): self
 * @method $this havingNotIn(string $field, array|callable|self $param)
 * @method $this havingNotLike(array|string $field, $match = '', string $side = 'both', bool $escape = true, bool $insensitiveSearch = false): self
 * @method $this in(string $key, array|callable|self $param)
 * @method $this innerJoin(string $table, array|string $fields)
 * @method $this into(string $table)
 * @method $this join(string $table, array|string $fields, string $type = 'INNER')
 * @method $this leftJoin(string $table, array|string $fields, bool $outer = false)
 * @method $this like(array|string $field, $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
 * @method $this limit(int $limit, ?int $offset = null)
 * @method float max(string $field, ?string $key = null, int $expire = 0)
 * @method float min(string $field, ?string $key = null, int $expire = 0)
 * @method $this notBetween(string $field, $value1, $value2)
 * @method $this notHavingLike($field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
 * @method $this notIn(string $key, array|callable|self $param)
 * @method $this notLike(array|string $field, $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
 * @method $this notWhere(array|string $key, $value = null, ?bool $escape = null)
 * @method $this offset(int $offset, ?int $limit = null)
 * @method mixed one(int|string $type = \PDO::FETCH_OBJ, ?string $key = null, int $expire = 0)
 * @method $this orBetween(string $field, $value1, $value2)
 * @method $this order(string|string[] $field, string $direction = 'ASC', ?bool $escape = null)
 * @method $this orderBy(string|string[] $field, string $direction = 'ASC', ?bool $escape = null)
 * @method $this orHaving(array|string $field, $values = null, ?bool $escape = null)
 * @method $this orHavingIn(string $field, array|callable|self $param)
 * @method $this orHavingLike(array|string $field, $match = '', string $side = 'both', bool $escape = true, bool $insensitiveSearch = false): self
 * @method $this orHavingNotIn(string $field, array|callable|self $param)
 * @method $this orHavingNotLike(array|string $field, $match = '', string $side = 'both', bool $escape = true, bool $insensitiveSearch = false): self
 * @method $this                         orIn(string $key, array|callable|self $param)
 * @method $this                         orLike(array|string $field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
 * @method $this                         orNotBetween(string $field, $value1, $value2)
 * @method $this                         orNotHavingLike($field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
 * @method $this                         orNotIn(string $key, array|callable|self $param)
 * @method $this                         orNotLike(array|string $field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
 * @method $this                         orNotWhere(array|string $key, $value = null, ?bool $escape = null)
 * @method $this                         orWhere(array|string $key, $value = null, ?bool $escape = null)
 * @method $this                         orWhereBetween(string $field, $value1, $value2)
 * @method $this                         orWhereIn(string $key, array|callable|self $param)
 * @method $this                         orWhereLike(array|string $field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
 * @method $this                         orWhereNotBetween(string $field, $value1, $value2)
 * @method $this                         orWhereNotIn(string $key, array|callable|self $param)
 * @method $this                         orWhereNotLike(array|string $field, string $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
 * @method $this                         orWhereNotNull(string|string[] $field)
 * @method $this                         orWhereNull(string|string[] $field)
 * @method \BlitzPHP\Database\BaseResult query(string $sql, array $params = [])
 * @method $this                         rand(?int $digit = null)
 * @method array                         result(int|string $type = \PDO::FETCH_OBJ, ?string $key = null, int $expire = 0)
 * @method $this                         rightJoin(string $table, array|string $fields, bool $outer = false)
 * @method mixed                         row(int $index, int|string $type = \PDO::FETCH_OBJ, ?string $key = null, int $expire = 0)
 * @method $this                         select(array|string $fields = '*', ?int $limit = null, ?int $offset = null)
 * @method $this                         sortAsc(string|string[] $field, ?bool $escape = null)
 * @method $this                         sortDesc(string|string[] $field, ?bool $escape = null)
 * @method float                         sum(string $field, ?string $key = null, int $expire = 0)
 * @method $this                         table(string|string[]|null $table)
 * @method mixed                         value(string $name, ?string $key = null, int $expire = 0)
 * @method $this                         where(array|string $key, $value = null, ?bool $escape = null)
 * @method $this                         whereBetween(string $field, $value1, $value2)
 * @method $this                         whereIn(string $key, array|callable|self $param)
 * @method $this                         whereLike(array|string $field, $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
 * @method $this                         whereNotBetween(string $field, $value1, $value2)
 * @method $this                         whereNotIn(string $key, array|callable|self $param)
 * @method $this                         whereNotLike(array|string $field, $match = '', string $side = 'both', ?bool $escape = null, bool $insensitiveSearch = false)
 * @method $this                         whereNotNull(string|string[] $field)
 * @method $this                         whereNull(string|string[] $field)
 */
abstract class BaseModel
{
    /**
     * Nom de la table
     *
     * @var string
     */
    protected $table;

    /**
     * Cle primaire.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Groupe de la base de données a utiliser
     *
     * @var string
     */
    protected $group;

    /**
     * Doit-on utiliser l'auto increment.
     *
     * @var bool
     */
    protected $useAutoIncrement = true;

    /**
     * Query Builder
     *
     * @var BaseBuilder|null
     */
    protected $builder;

    /**
     * Holds information passed in via 'set'
     * so that we can capture it (not the builder)
     * and ensure it gets validated first.
     *
     * @var array
     */
    protected $tempData = [];

    /**
     * Escape array that maps usage of escape
     * flag for every parameter.
     *
     * @var array
     */
    protected $escape = [];

    /**
     * Methodes du builder qui ne doivent pas etre utilisees dans le model.
     *
     * @var string[] method name
     */
    private array $builderMethodsNotAvailable = [
        'getCompiledInsert',
        'getCompiledSelect',
        'getCompiledUpdate',
    ];

    public function __construct(?ConnectionInterface $db = null)
    {
        $db ??= Database::connect($this->group);

        $this->db = $db;
    }

    /**
     * Fourni une instance partagee du Query Builder.
     *
     * @throws ModelException
     */
    public function builder(?string $table = null): BaseBuilder
    {
        if ($this->builder instanceof BaseBuilder) {
            // S'assurer que la table utilisee differe de celle du builder
            if ($table && $this->builder->getTable() !== $table) {
                return $this->db->table($table);
            }

            return $this->builder;
        }

        $table = empty($table) ? $this->table : $table;

        // S'assurer qu'on a une bonne connxion a la base de donnees
        if (! $this->db instanceof ConnectionInterface) {
            $this->db = Database::connect($this->group);
        }

        if (empty($table)) {
            $builder = $this->db->table('.')->from([], true);
        } else {
            $builder = $this->db->table($table);
        }

        // Considerer que c'est partagee seulement si la table est correct
        if ($table === $this->table) {
            $this->builder = $builder;
        }

        return $builder;
    }

    /**
     * Provides/instantiates the builder/db connection and model's table/primary key names and return type.
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }

        if (isset($this->db->{$name})) {
            return $this->db->{$name};
        }

        if (isset($this->builder()->{$name})) {
            return $this->builder()->{$name};
        }

        return null;
    }

    /**
     * Verifie si une propriete existe dans le modele, le builder, et la db connection.
     */
    public function __isset(string $name): bool
    {
        if (property_exists($this, $name)) {
            return true;
        }

        if (isset($this->db->{$name})) {
            return true;
        }

        return isset($this->builder()->{$name});
    }

    /**
     * Fourni un acces direct a une methode du builder (si disponible)
     * et la database connection.
     *
     * @return mixed
     */
    public function __call(string $name, array $params)
    {
        $builder = $this->builder();
        $result  = null;

        if (method_exists($this->db, $name)) {
            $result = $this->db->{$name}(...$params);
        } elseif (method_exists($builder, $name)) {
            $this->checkBuilderMethod($name);

            $result = $builder->{$name}(...$params);
        } else {
            throw new BadMethodCallException('Call to undefined method ' . static::class . '::' . $name);
        }

        if ($result instanceof BaseBuilder) {
            return $this;
        }

        return $result;
    }

    /**
     * Verifie si la methode du builder peut etre utilisee dans le modele.
     */
    private function checkBuilderMethod(string $name): void
    {
        if (in_array($name, $this->builderMethodsNotAvailable, true)) {
            //   throw ModelException::forMethodNotAvailable(static::class, $name . '()');
        }
    }
}
