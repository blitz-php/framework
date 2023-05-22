<?php

namespace BlitzPHP\Validation\Rules;

use BlitzPHP\Contracts\Database\ConnectionInterface;
use Dimtrovich\Validation\Rules\AbstractRule;

class Unique extends AbstractRule
{
    protected $message = ":attribute :value has been used";

    protected $fillableParams = ['table', 'column', 'except'];

    public function __construct(protected ConnectionInterface $db)
    {
    }

    public function check($value): bool
    {
        $this->requireParameters(['table']);

        $table  = $this->parameter('table');
        $except = $this->parameter('except');
        $column = $this->parameter('column');
        $column = $column ?: $this->getAttribute()->getKey(); 

        if ($except && $except == $value) {
            return true;
        }

        return $this->db->table($table)->where($column, $value)->count() === 0;
    }
}
