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

/**
 * Formateur de données en tableau
 */
class ArrayFormatter implements FormatterInterface
{
    /**
     * Prend les données fournies et les formate.
     *
     * @param mixed $data
     *
     * @return array Données formatées sous forme de tableau ; sinon, un tableau vide
     */
    public function format($data)
    {
        if (! is_array($data)) {
            $data = (array) $data;
        }

        $array = [];

        foreach ($data as $key => $value) {
            $array[$key] = is_object($value) || is_array($value) ? $this->format($value) : $value;
        }

        return $array;
    }

    /**
     * {@inheritDoc}
     */
    public function parse(string $data): array
    {
        return [$data];
    }
}
