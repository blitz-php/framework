<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Container;

/**
 * Classe abstraite pour les fournisseurs de services.
 *
 * Les providers définissent les bindings et peuvent avoir un boot post-resolution.
 */
abstract class AbstractProvider
{
    /**
     * Constructeur.
     *
     * @param Container $container Le conteneur d'injection.
     */
    public function __construct(protected Container $container)
    {
    }

    /**
     * Retourne les définitions de bindings.
     *
     * Format : ['abstract' => $concrete|Closure]
     *
     * @return array<string, mixed>
     */
    public static function definitions(): array
    {
        // a implementer par les classes filles

        return [];
    }

    /**
     * Enregistre les bindings dans le conteneur.
     */
    public function register(): void
    {
        // à implementer par les classes filles
    }

    /**
     * Liste les services fournis (pour introspection).
     *
     * @return list<string>
     */
    public function provides(): array
    {
        return array_keys(static::definitions());
    }
}
