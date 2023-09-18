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

class Exists extends AbstractRule
{
    protected $message        = ':attribute :value do not exist';
    protected $fillableParams = ['table', 'column'];


    public function __construct(protected ConnectionInterface $db)
    {
    }

    public function check($value): bool
    {
        $this->requireParameters(['table']);

        $table  = $this->parameter('table');
        $column = $this->parameter('column');
        $column = $column ?: $this->getAttribute()->getKey();

		$builder = $this->db->table($table)->where($column, $value);

        return $builder->count() > 0;
    }
}
