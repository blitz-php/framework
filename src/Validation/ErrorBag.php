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

use Dimtrovich\Validation\ErrorBag as DimtrovichErrorBag;

class ErrorBag extends DimtrovichErrorBag
{
    /**
     * Verifie s'il n'y a aucune erreur dans le bag a erreur
     */
    public function empty(): bool
    {
        return $this->count() === 0;
    }
}
