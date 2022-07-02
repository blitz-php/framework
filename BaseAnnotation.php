<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Annotations;

use mindplay\annotations\Annotation;
use ReflectionClass;

/**
 * Classe abstraite servant de base pour les annotations
 */
abstract class BaseAnnotation extends Annotation
{
    /**
     * @var mixed
     *
     * La valeur de cette annotation
     */
    public $value;

    /**
     * Recupère la valeur de cette annotation
     *
     * @return mixed
     */
    public function getValue()
    {
        if (! empty($this->value)) {
            return $this->value;
        }

        $value = [];

        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties() as $property) {
            $value[$property->getName()] = $property->getValue();
        }

        return $value;
    }
}
