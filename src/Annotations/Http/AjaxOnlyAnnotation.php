<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Annotations\Http;

use BlitzPHP\Annotations\BaseAnnotation;

/**
 * Annotation permettant de limiter l'accès à un contrôleur/méthode uniquement par appel ajax
 * Ceci est notament utiliser dans les RestController
 *
 * @usage('class'=>true, 'method'=> true, 'inherited'=>true)
 */
class AjaxOnlyAnnotation extends BaseAnnotation
{
    /**
     * @var bool
     */
    public $value = true;
}
