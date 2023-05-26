<?php 

namespace BlitzPHP\Validation;

use Dimtrovich\Validation\Validator as BaseValidator;

class Validator extends BaseValidator
{
    /**
     * {@inheritDoc}
     */
    protected static string $validationClass = Validation::class;
    
    public static function validate(array $data, array $rules, array $messages = []): array
    {
        return static::make($data, $rules, $messages)->validate();
    }
}