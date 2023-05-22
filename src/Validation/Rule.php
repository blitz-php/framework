<?php

namespace BlitzPHP\Validation;

use Dimtrovich\Validation\Rule as DimtrovichRule;


/**
 * {@inheritDoc}
 *
 * @method static \BlitzPHP\Validation\Rules\Unique unique(string $table, ?string $column = null, mixed $except = null)
 */
abstract class Rule extends DimtrovichRule
{

}
