<?php

namespace BlitzPHP\Exceptions;

use BlitzPHP\Contracts\Http\StatusCode;

class ValidationException extends FrameworkException
{
    /**
     * Liste des erreurs de validation
     */
    private array $errors = [];

    /**
     * Code d'erreur
     *
     * @var int
     */
    protected $code = StatusCode::BAD_REQUEST;


    /**
     * Recupere les erreurs de validation
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Definie les erreurs de validation
     */
    public function setErrors(array $errors): self
    {
        $this->errors = $errors;

        return $this;
    }
}
