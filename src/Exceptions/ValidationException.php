<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

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
