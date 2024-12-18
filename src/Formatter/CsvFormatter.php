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
 * Formateur de données en CSV
 *
 * @see http://www.metashock.de/2014/02/create-csv-file-in-memory-php/
 */
class CsvFormatter implements FormatterInterface
{
    /**
     * Délimiteur de champ (un seul caractère)
     */
    private string $delimiter = ',';

    /**
     * Encadrement du champ (un seul caractère).
     */
    private string $enclosure = '"';

    /**
     * {@inheritDoc}
     *
     * @return string|null Une chaine formatée en CSV
     */
    public function format($data)
    {
        // Utiliser un seuil de 1 Mo (1024 * 1024)
        $handle = fopen('php://temp/maxmemory:1048576', 'wb');
        if ($handle === false) {
            return null;
        }

        if (! is_array($data)) {
            $data = (array) $data;
        }

        // Vérifie s'il s'agit d'un tableau multidimensionnel
        if (isset($data[0]) && count($data) !== count($data, COUNT_RECURSIVE)) {
            $headings = array_keys($data[0]);
        } else {
            $headings = array_keys($data);
            $data     = [$data];
        }

        // Appliquer les en-têtes
        fputcsv($handle, $headings, $this->delimiter, $this->enclosure);

        foreach ($data as $record) {
            // Si l'enregistrement n'est pas un tableau, alors break.
            // C'est parce que le 2ème paramètre de fputcsv() doit être un tableau
            if (! is_array($record)) {
                break;
            }

            // Suppression de la notification "conversion de tableau en chaîne".
            // Gardez le "mal" @ ici.
            $record = @array_map('strval', $record);

            fputcsv($handle, $record, $this->delimiter, $this->enclosure);
        }

        rewind($handle);

        $csv = stream_get_contents($handle);

        fclose($handle);

        // Convertit l'encodage UTF-8 en UTF-16LE qui est pris en charge par MS Excel
        return mb_convert_encoding($csv, 'UTF-16LE', 'UTF-8');
    }

    /**
     * {@inheritDoc}
     *
     * @param string $data Chaine CSV
     *
     * @return array A multi-dimensional array with the outer array being the number of rows
     *               and the inner arrays the individual fields
     */
    public function parse(string $data): array
    {
        $array = [];
        $lines = explode("\n", trim($data));

        foreach ($lines as $line) {
            $array[] = str_getcsv($line, $this->delimiter, $this->enclosure);
        }

        $head = array_shift($array);

        if ($array === []) {
            return $head;
        }

        $result = [];

        foreach ($array as $values) {
            $result[] = array_combine($head, $values);
        }

        return $result;
    }

    /**
     * Recupère le délimiteur de champ
     */
    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * Définit le délimiteur de champ
     */
    public function setDelimiter(string $delimiter): self
    {
        $this->delimiter = $delimiter[0] ?? ',';

        return $this;
    }

    /**
     * Recupère l'encadrement du champ (un seul caractère).
     */
    public function getEnclosure(): string
    {
        return $this->enclosure;
    }

    /**
     * Set définit l'encadrement du champ (un seul caractère).
     */
    public function setEnclosure(string $enclosure): self
    {
        $this->enclosure = $enclosure[0] ?? '"';

        return $this;
    }
}
