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

use BlitzPHP\Loader\Services;

/**
 * Formateur de données JSON
 */
class JsonFormatter implements FormatterInterface
{
    /**
     * Prend les données fournies et les formate.
     *
     * @param mixed $data
     *
     * @return false|string Représentation Json d'une valeur
     *                      false en cas d'erreur de formattage
     */
    public function format($data)
    {
        // Obtenir le paramètre de rappel (si défini)
        $callback = Services::request()->getQuery('callback');

        if (empty($callback)) {
            return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // Nous n'honorons qu'un rappel jsonp qui sont des identifiants javascript valides
        if (preg_match('/^[a-z_\$][a-z0-9\$_]*(\.[a-z_\$][a-z0-9\$_]*)*$/i', $callback)) {
            // Renvoie les données sous forme de json encodé avec un rappel
            return $callback . '(' . json_encode($data, JSON_UNESCAPED_UNICODE) . ');';
        }

        // Une fonction de rappel jsonp non valide a été fournie.
        // Bien que je ne pense pas que cela devrait être codé en dur ici
        $data['warning'] = 'INVALID JSONP CALLBACK: ' . $callback;

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
