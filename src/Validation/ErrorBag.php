<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Validation;

use Rakit\Validation\ErrorBag as RakitErrorBag;

class ErrorBag extends RakitErrorBag
{
    /**
     * Renvoie les erreurs de validation d'une cle sous forme de chaine
     */
    public function line(string $key, string $separator = ', ', string $format = ':message'): ?string
    {
        if ([] === $errors = $this->get($key, $format)) {
            return null;
        }

        return join($separator, $errors);
    }
}
