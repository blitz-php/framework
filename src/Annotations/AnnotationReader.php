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

use mindplay\annotations\AnnotationCache;
use mindplay\annotations\AnnotationException;
use mindplay\annotations\Annotations;
use mindplay\annotations\IAnnotation;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Classe permettant de lire les differentes annotations
 */
class AnnotationReader
{
    /**
     * La seule instance d'utilisation de la classe
     *
     * @var object
     */
    protected static $_instance;

    /**
     * Vérifie, instancie et renvoie la seule instance de la classe appelée.
     *
     * @return static
     */
    public static function instance()
    {
        if (! (static::$_instance instanceof static)) {
            $params            = func_get_args();
            static::$_instance = new static(...$params);
        }

        return static::$_instance;
    }

    /**
     * Constructeur
     */
    protected function __construct()
    {
        $cacheDir = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'blitz-php' . DIRECTORY_SEPARATOR . 'annotations';
        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        Annotations::$config['cache'] = new AnnotationCache($cacheDir);
        AnnotationPackager::register(Annotations::getManager());
    }

    /**
     * Inspecte les annotations appliquées à une classe donnée
     *
     * @param object|ReflectionClass|string $class Un nom de classe, un objet ou une instance de ReflectionClass
     * @param string                        $type  Un nom de classe/interface d'annotation facultatif - si spécifié, seules les annotations du type donné sont renvoyées.
     *                                             Alternativement, le préfixe avec "@" invoque la résolution de nom (vous permettant d'interroger par nom d'annotation.)
     *
     * @return Annotation[] Instances d'annotation
     *
     * @throws AnnotationException si un nom de classe donné n'est pas défini
     */
    public static function fromClass($class, ?string $type = null)
    {
        return self::instance()->ofClass($class, $type);
    }

    /**
     * Inspecte les annotations appliquées à une méthode donnée
     *
     * @param object|ReflectionClass|ReflectionMethod|string $class  Un nom de classe, un objet, une ReflectionClass ou une instance de ReflectionMethod
     * @param string|null                                    $method Le nom d'une méthode de la classe donnée (ou null, si le premier paramètre est une ReflectionMethod)
     * @param string                                         $type   Un nom facultatif de classe/d'interface d'annotation - si spécifié, seules les annotations du type donné sont renvoyées.
     *                                                               Alternativement, le préfixe avec "@" invoque la résolution de nom (vous permettant d'interroger par nom d'annotation.)
     *
     * @return IAnnotation[] liste des objets Annotation
     *
     * @throws AnnotationException pour une méthode ou un nom de classe non défini
     */
    public static function fromMethod($class, ?string $method, ?string $type = null)
    {
        return self::instance()->ofMethod($class, $method, $type);
    }

    /**
     * Inspecte les annotations appliquées à une propriété donnée
     *
     * @param object|ReflectionClass|ReflectionProperty|string $class    Un nom de classe, un objet, une ReflectionClass ou une instance de ReflectionProperty
     * @param string|null                                      $property Le nom d'une propriété définie de la classe donnée (ou null, si le premier paramètre est une ReflectionProperty)
     * @param string                                           $type     Un nom de classe/interface d'annotation facultatif - si spécifié, seules les annotations du type donné sont renvoyées.
     *                                                                   Alternativement, le préfixe avec "@" invoque la résolution de nom (vous permettant d'interroger par nom d'annotation.)
     *
     * @return IAnnotation[] liste des objets Annotation
     *
     * @throws AnnotationException pour un nom de classe non défini
     */
    public static function formProperty($class, ?string $property, ?string $type = null)
    {
        return self::instance()->ofProperty($class, $property, $type);
    }

    /**
     * Inspecte les annotations appliquées à une classe donnée
     *
     * @see self::fromClass()
     *
     * @param mixed      $class
     * @param mixed|null $type
     */
    private function ofClass($class, $type = null)
    {
        return Annotations::ofClass($class, $type);
    }

    /**
     * Inspecte les annotations appliquées à une méthode donnée
     *
     * @see self::fromMethod()
     *
     * @param mixed      $class
     * @param mixed|null $type
     */
    private function ofMethod($class, ?string $method, $type = null)
    {
        return Annotations::ofMethod($class, $method, $type);
    }

    /**
     * Inspecte les annotations appliquées à une proprieté donnée
     *
     * @see self::fromProperty()
     *
     * @param mixed      $class
     * @param mixed|null $type
     */
    private function ofProperty($class, ?string $property, $type = null)
    {
        return Annotations::ofProperty($class, $property, $type);
    }
}
