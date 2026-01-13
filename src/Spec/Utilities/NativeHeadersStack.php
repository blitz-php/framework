<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Spec\Utilities;

/**
 * Classe utilitaire permettant de simuler la gestion native des en-têtes PHP dans les tests unitaires.
 *
 * @internal Cette classe est réservée à des fins de test.
 */
final class NativeHeadersStack
{
    /**
     * Simule si les en-têtes ont été envoyés.
     */
    public static bool $headersSent = false;

    /**
     * Stocke la liste des en-têtes.
     *
     * @var list<string>
     */
    public static array $headers = [];

    /**
     * Réinitialise la pile d'en-têtes aux valeurs par défaut.
	 * Appelez cette fonction dans beforeEach/setUp pour garantir un état propre entre les tests.
     */
    public static function reset(): void
    {
        self::$headersSent = false;
        self::$headers     = [];
    }

    /**
     * Vérifie si un en-tête spécifique existe dans la pile.
     *
     * @param string $header La chaîne exacte de l'en-tête (par exemple, 'Content-Type: text/html')
     */
    public static function has(string $header): bool
    {
        return in_array($header, self::$headers, true);
    }

    /**
     * Ajoute un en-tête à la pile.
     *
     * @param string $header L'en-tête à ajouter (par exemple, 'Content-Type: text/html')
     */
    public static function push(string $header): void
    {
        self::$headers[] = $header;
    }
}
