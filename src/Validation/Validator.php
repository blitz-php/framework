<?php 

namespace BlitzPHP\Validation;

use Dimtrovich\Validation\Validator as BaseValidator;

class Validator extends BaseValidator
{
    public static function make(array $data, array $rules, array $messages = []): Validation
    {
        $instance = new Validation();

        $instance->data($data);
        $instance->rules($rules);
        $instance->messages($messages);

        return $instance;
    }
    
    public static function validate(array $data, array $rules, array $messages = []): array
    {
        return static::make($data, $rules, $messages)->validate();
    }
}