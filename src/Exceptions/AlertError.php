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

use Error;

/**
 * Erreur : une action doit être entreprise immédiatement (système/base de données en panne, etc.)
 */
class AlertError extends Error
{
}
