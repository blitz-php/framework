<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Formatter;

use BlitzPHP\Exceptions\FormatException;

class Formatter
{
    /**
     * Lorsque vous effectuez une négociation de contenu avec la requête, il s'agit des
     * formats disponibles pris en charge par votre application.
     * Un formateur valide doit exister pour le format spécifié.
     *
     * Ces formats ne sont vérifiés que lorsque les données sont transmises à la réponse ()
     * La méthode est un tableau.
     *
     * @var string[]
     */
    protected static $supportedResponseFormats = [
        'application/json',
        'application/xml', // XML lisible par machine
        'text/xml', // XML lisible par l'homme
    ];

    /**
     * Répertorie la classe à utiliser pour formater les réponses avec un type particulier.
     * Pour chaque type mime, indiquez la classe à utiliser.
     *
     * @var array<string, string>
     */
    protected static $formatters = [
        'application/json' => JsonFormatter::class,
        'json'             => JsonFormatter::class,
        'application/csv'  => CsvFormatter::class,
        'csv'              => CsvFormatter::class,
        'application/xml'  => XmlFormatter::class,
        'text/xml'         => XmlFormatter::class,
        'xml'              => XmlFormatter::class,

        'php/array' => ArrayFormatter::class,
        'array'     => ArrayFormatter::class,
    ];

    /**
     * Une méthode Factory pour renvoyer le formateur approprié pour le type mime donné.
     *
     * @throws FormatException
     */
    public static function type(string $mime): FormatterInterface
    {
        if (! array_key_exists($mime, self::$formatters)) {
            throw FormatException::invalidMime($mime);
        }

        $className = self::$formatters[$mime];

        if (! class_exists($className)) {
            throw FormatException::invalidFormatter($className);
        }

        $class = new $className();

        if (! $class instanceof FormatterInterface) {
            throw FormatException::invalidFormatter($className);
        }

        return $class;
    }
}
