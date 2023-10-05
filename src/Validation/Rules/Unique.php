<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Validation\Rules;

use BlitzPHP\Contracts\Database\ConnectionInterface;
use BlitzPHP\Wolke\Model;

class Unique extends AbstractRule
{
    protected $message        = ':attribute :value has been used';
    protected $fillableParams = ['table', 'column', 'ignore'];

    /**
     * The name of the ID column.
     */
    protected string $idColumn = 'id';

    protected string $deletedAtColumn = '';

    public function __construct(protected ConnectionInterface $db)
    {
    }

    /**
     * Ignore the given ID during the unique check.
     */
    public function ignore(mixed $id, ?string $idColumn = null): self
    {
        if (class_exists(Model::class) && $id instanceof Model) {
            return $this->ignoreModel($id, $idColumn);
        }

        $this->params['ignore'] = $id;
        $this->idColumn         = $idColumn ?? 'id';

        return $this;
    }

    /**
     * Ignore the given model during the unique check.
     */
    public function ignoreModel(Model $entity, ?string $idColumn = null): self
    {
        $this->idColumn         = $idColumn ?? $entity->getKeyName();
        $this->params['ignore'] = $entity->{$this->idColumn};

        return $this;
    }

    /**
     * Ignore soft deleted models during the unique check.
     */
    public function withoutTrashed(string $deletedAtColumn = 'deleted_at'): self
    {
        $this->deletedAtColumn = $deletedAtColumn;

        return $this;
    }

    public function check($value): bool
    {
        $this->requireParameters(['table']);

        $table  = $this->parameter('table');
        $ignore = $this->parameter('ignore');
        $column = $this->parameter('column');
        $column = $column ?: $this->getAttribute()->getKey();

        $builder = $this->db->table($table)->where($column, $value);

        if ($ignore) {
            $builder->where($this->idColumn . ' !=', $ignore);
        }
        if ('' !== $this->deletedAtColumn) {
            $builder->where($this->deletedAtColumn . ' IS NULL');
        }

        return $builder->count() === 0;
    }
}
