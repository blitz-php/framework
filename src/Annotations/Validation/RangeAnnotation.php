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
use mindplay\annotations\AnnotationException;

/**
 * Spécifie la validation par rapport à une valeur numérique minimale et/ou maximale.
 *
 * @usage('property'=>true, 'inherited'=>true)
 */
class RangeAnnotation extends BaseAnnotation
{
    /**
     * @var float|int
     *
     * Valeur numérique minimale (entier ou virgule flottante)
     */
    public $min;

    /**
     * @var float|int
     *
     * * Valeur numérique maximale (entier ou virgule flottante)
     */
    public $max;

    /**
     * Initialisation de l'annotation.
     */
    public function initAnnotation(array $properties)
    {
        if (isset($properties[0])) {
            if (isset($properties[1])) {
                $this->min = $properties[0];
                $this->max = $properties[1];
                unset($properties[1]);
            } else {
                $this->max = $properties[0];
            }

            unset($properties[0]);
        }

        parent::initAnnotation($properties);

        if ($this->min !== null && ! is_int($this->min) && ! is_float($this->min)) {
            throw new AnnotationException('RangeAnnotation requires a numeric (float or int) min property');
        }

        if ($this->max !== null && ! is_int($this->max) && ! is_float($this->max)) {
            throw new AnnotationException('RangeAnnotation requires a numeric (float or int) max property');
        }

        if ($this->min === null && $this->max === null) {
            throw new AnnotationException('RangeAnnotation requires a min and/or max property');
        }

        $this->value = [$this->min, $this->max];
    }
}
