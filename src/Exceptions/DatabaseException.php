<?php

namespace BlitzPHP\Exceptions;

use Error;

class DatabaseException extends Error implements ExceptionInterface
{
    /**
     * Exit status code
     *
     * @var int
     */
    protected $code = 8;
}
