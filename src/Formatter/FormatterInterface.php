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
 * Formatter interface
 */
interface FormatterInterface
{
    /**
     * Prend les données fournies et les formate.
     *
     * @param array|string $data
     *
     * @return mixed
     */
    public function format($data);

    /**
     * Prend les données fournies et les decode
     *
     * @return array Resultat de la chaîne décodée ou tableau vide
     */
    public function parse(string $data): array;
}
