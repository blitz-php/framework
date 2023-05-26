<?php

namespace BlitzPHP\Validation;

use BlitzPHP\Utilities\String\Text;
use Dimtrovich\Validation\Rule as DimtrovichRule;


/**
 * {@inheritDoc}
 *
 * @method static \BlitzPHP\Validation\Rules\Unique unique(string $table, ?string $column = null, mixed $except = null)
 */
abstract class Rule extends DimtrovichRule
{
    public static function __callStatic(string $name, array $arguments = [])
    {
        $name = Text::snake($name);

        return Validator::rule($name, ...$arguments);
    }
}
