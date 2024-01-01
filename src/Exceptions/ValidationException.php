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
use BlitzPHP\Validation\ErrorBag;
use Dimtrovich\Validation\Exceptions\ValidationException as BaseValidationException;
use Rakit\Validation\ErrorBag as RakitErrorBag;

class ValidationException extends BaseValidationException
{
    /**
     * Code d'erreur
     *
     * @var int
     */
    protected $code = StatusCode::BAD_REQUEST;

    /**
     * {@inheritDoc}
     */
    public function setErrors(?RakitErrorBag $errors): self
    {
        $this->errors = new ErrorBag($errors->toArray());

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getErrors(): ?ErrorBag
    {
        return $this->errors;
    }
}
