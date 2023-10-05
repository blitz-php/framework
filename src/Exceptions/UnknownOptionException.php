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

use InvalidArgumentException;
use Throwable;

/**
 * @credit league/config (c) Colin O'Dell <colinodell@gmail.com>
 */
final class UnknownOptionException extends InvalidArgumentException
{
    private string $path;

    public function __construct(string $message, string $path, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
