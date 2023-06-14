<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Debug\Toolbar\Collectors;

use BlitzPHP\Database\Connection\BaseConnection;
use BlitzPHP\Event\Event;

/**
 * Collecteur pour l'onglet Base de données de la barre d'outils de débogage.
 *
 * @credit	<a href="https://codeigniter.com">CodeIgniter 4.2 - CodeIgniter\Debug\Toolbar\Collectors\Database</a>
 */
class DatabaseCollector extends BaseCollector
{
    /**
     * {@inheritDoc}
     */
    protected $hasTimeline = true;

    /**
     * {@inheritDoc}
     */
    protected $hasTabContent = true;

    /**
     * {@inheritDoc}
     */
    protected $hasVarData = false;

    /**
     * {@inheritDoc}
     */
    protected $title = 'Database';

    /**
     * Tableau de connexions à la base de données.
     *
     * @var BaseConnection[]
     */
    protected $connections;

    /**
     * Les instances de requête qui ont été collectées via l'événement DBQuery.
     *
     * @var stdClass[]
     */
    protected static $queries = [];

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->getConnections();
    }

    /**
     * La méthode statique utilisée lors des événements pour collecter des données.
     */
    public static function collect(Event $event)
    {
        /**
         * @var \BlitzPHP\Database\Result\BaseResult
         */
        $result = $event->getTarget();
        $config = (object) config('toolbar');

        // Fournit la valeur par défaut au cas où elle n'est pas définie
        $max = $config->max_queries ?: 100;

        if (count(static::$queries) < $max) {
            static::$queries[] = (object) $result->details();
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function formatTimelineData(): array
    {
        $data = [];

        foreach ($this->connections as $alias => $connection) {
            $data[] = [
                'name'      => 'Connecting to Database: "' . $connection->getDatabase() . '". Config: "' . $alias . '"',
                'component' => 'Database',
                'start'     => $connection->getConnectStart(),
                'duration'  => $connection->getConnectDuration(),
            ];
        }

        foreach (static::$queries as $query) {
            $data[] = [
                'name'      => 'Query',
                'component' => 'Database',
                'query'     => $query->sql,
                'start'     => $query->start,
                'duration'  => $query->duration,
            ];
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function display(): array
    {
        // Mots clés que nous voulons mettre en gras
        $highlight = [
            'SELECT',
            'DISTINCT',
            'FROM',
            'WHERE',
            'AND',
            'INNER JOIN',
            'LEFT JOIN',
            'RIGHT JOIN',
            'JOIN',
            'ORDER BY',
            'ASC',
            'DESC',
            'GROUP BY',
            'LIMIT',
            'INSERT',
            'INTO',
            'VALUES',
            'UPDATE',
            'OR ',
            'HAVING',
            'OFFSET',
            'NOT IN',
            'IN',
            'NOT LIKE',
            'LIKE',
            'COUNT',
            'MAX',
            'MIN',
            'ON',
            'AS',
            'AVG',
            'SUM',
            'UPPER',
            'LOWER',
            '(',
            ')',
        ];

        $data = [
            'queries' => [],
        ];

        foreach (static::$queries as $query) {
            $sql = $query->sql;

            foreach ($highlight as $term) {
                $sql = str_replace($term, "<strong>{$term}</strong>", $sql);
            }

            $data['queries'][] = [
                'duration'      => (number_format($query->duration, 5) * 1000) . ' ms',
                'sql'           => $sql,
                'affected_rows' => $query->affected_rows,
            ];
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function getBadgeValue(): int
    {
        return count(static::$queries);
    }

    /**
     * {@inheritDoc}
     *
     * @return string Le nombre de requêtes (entre parenthèses) ou une chaîne vide.
     */
    public function getTitleDetails(): string
    {
        return '(' . count(static::$queries) . ' Queries across ' . ($countConnection = count($this->connections)) . ' Connection' .
                ($countConnection > 1 ? 's' : '') . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty(): bool
    {
        return empty(static::$queries);
    }

    /**
     * {@inheritDoc}
     */
    public function icon(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAADMSURBVEhLY6A3YExLSwsA4nIycQDIDIhRWEBqamo/UNF/SjDQjF6ocZgAKPkRiFeEhoYyQ4WIBiA9QAuWAPEHqBAmgLqgHcolGQD1V4DMgHIxwbCxYD+QBqcKINseKo6eWrBioPrtQBq/BcgY5ht0cUIYbBg2AJKkRxCNWkDQgtFUNJwtABr+F6igE8olGQD114HMgHIxAVDyAhA/AlpSA8RYUwoeXAPVex5qHCbIyMgwBCkAuQJIY00huDBUz/mUlBQDqHGjgBjAwAAACexpph6oHSQAAAAASUVORK5CYII=';
    }

    /**
     * Obtient les connexions à partir de la configuration de la base de données
     */
    private function getConnections()
    {
        $this->connections = \BlitzPHP\Config\Database::getConnections();
    }
}
