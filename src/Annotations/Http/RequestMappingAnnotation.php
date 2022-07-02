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
use mindplay\annotations\AnnotationException;

/**
 * Annotation pour le mappage des requêtes Web sur les méthodes dans les ccontrôleurs
 * avec des signatures de méthode flexibles.
 *
 * @usage('class'=>true, 'method'=> true, 'inherited'=>true)
 */
class RequestMappingAnnotation extends BaseAnnotation
{
    /**
     * Méthodes autorisés
     */
    private const VALID_METHODS = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD', 'TRACE'];

    /**
     * @var string[]
     *
     * Les méthodes de requête HTTP à mapper, en limitant le mappage principal :
     * GET, POST, HEAD, OPTIONS, PUT, PATCH, DELETE, TRACE.
     * <p><b>Pris en charge au niveau du contrôleur ainsi qu'au niveau de la méthode !</b>
     * Lorsqu'il est utilisé au niveau du contrôleur, tous les mappages au niveau de la méthode héritent de ce
     * Restriction de la méthode HTTP.
     */
    public $method;

    /**
     * @var string
     *
     * Les URI de mappage de chemin (par exemple, {@code "/profile"}).
     * <p>Les modèles de chemin de style Ant sont également pris en charge (par exemple, {@code "/profile/**"}).
     * Au niveau de la méthode, les chemins relatifs (par exemple, {@code "edit"}) sont pris en charge
     * dans le mappage principal exprimé au niveau du contôleur.
     * <p><b>Pris en charge au niveau du contrôleur ainsi qu'au niveau de la méthode !</b>
     * Lorsqu'ils sont utilisés au niveau du contrôleur, tous les mappages au niveau de la méthode héritent
     * ce mappage primaire, en le rétrécissant pour une méthode de gestionnaire spécifique.
     */
    public $path;

    /**
     * Initialisation de l'annotation.
     */
    public function initAnnotation(array $properties)
    {
        if (isset($properties[0])) {
            if (isset($properties[1])) {
                $this->method = (array) $properties[0];

                if (! is_string($properties[1])) {
                    throw new AnnotationException('RequestMappingAnnotation requires a string as path property');
                }

                $this->path = $properties[1];

                unset($properties[1]);
            } else {
                if (is_string($properties[0])) {
                    $this->path = $properties[0];
                } elseif (is_array($properties[0])) {
                    $this->method = $properties[0];
                } else {
                    throw new AnnotationException('Invalid type for RequestMappingAnnotation properties');
                }
            }

            unset($properties[0]);
        }

        parent::initAnnotation($properties);

        if ($this->method === ['*']) {
            $this->method = null;
        }
        if (! empty($this->method)) {
            $this->method = array_map('strtoupper', $this->method);

            foreach ($this->method as $method) {
                if (! in_array($method, self::VALID_METHODS, true)) {
                    throw new AnnotationException('`' . $method . '` is not a valid method for RequestMappingAnnotation');
                }
            }
        }
    }
}
