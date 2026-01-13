<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

if (! function_exists('is_cli')) {
    /**
     * Est-ce CLI ?
     *
     * Teste si une requête a été effectuée à partir de la ligne de commande.
     * Vous pouvez définir la valeur de retour pour le test.
     *
     * @param bool $newReturn valeur de retour à définir
     */
    function is_cli(?bool $newReturn = null): bool
    {
        // Kahlan/PHPUnit s'exécute toujours via l'interface CLI.
        static $returnValue = true;

        if ($newReturn !== null) {
            $returnValue = $newReturn;
        }

        return $returnValue;
    }
}

if (! function_exists('is_online')) {
    /**
     * Est-ce en ligne ?
     *
     * Teste si une requête a été effectuée dans étant sur un serveur en ligne.
     * Vous pouvez définir la valeur de retour pour le test.
     *
     * @param bool $newReturn valeur de retour à définir
     */
    function is_online(?bool $newReturn = null): bool
    {
        // Kahlan/PHPUnit ne s'exécute generalement pas via l'interface CLI.
        static $returnValue = false;

        if ($newReturn !== null) {
            $returnValue = $newReturn;
        }

        return $returnValue;
    }
}
