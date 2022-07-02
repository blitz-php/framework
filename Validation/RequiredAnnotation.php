<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Annotations\Validation;

use BlitzPHP\Annotations\BaseAnnotation;

/**
 * Spécifie la validation nécessitant une valeur non vide.
 *
 * @usage('property'=>true, 'inherited'=>true)
 */
class RequiredAnnotation extends BaseAnnotation
{
    public $value = true;
}
