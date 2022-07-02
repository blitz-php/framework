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
     * @var array
     */
    protected $connections;

    /**
     * Les instances de requête qui ont été collectées via l'événement DBQuery.
     *
     * @var array
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
     *
     * @internal param $ array \BlitzPHP\Database\Query\Result
     */
    public static function collect(Event $event)
    {
        /**
         * @var \BlitzPHP\Database\Query\Result
         */
        $result = $event->getTarget();

        $config = (object) config('toolbar');

        // Fournit la valeur par défaut au cas où elle n'est pas définie
        $max = $config->max_queries ?: 100;

        if (count(static::$queries) < $max) {
            static::$queries[] = (object) $result->details();
        }

        /*if (count(static::$queries) < $max) {
            $queryString = $query->getQuery();

            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

            if (! is_cli()) {
                // lorsqu'il est appelé dans le navigateur, les deux premiers tableaux de trace
                // proviennent du déclencheur d'événement DB, qui sont inutiles
                $backtrace = array_slice($backtrace, 2);
            }

            static::$queries[] = [
                'query'     => $query,
                'string'    => $queryString,
                'duplicate' => in_array($queryString, array_column(static::$queries, 'string', null), true),
                'trace'     => $backtrace,
            ];
        }*/
    }

    /**
     * Returns timeline data formatted for the toolbar.
     *
     * @return array The formatted data or an empty array.
     */
    protected function formatTimelineData(): array
    {
        $data = [];

        foreach ($this->connections as $alias => $connection) {
            $data[] = [
                'name'      => 'Connecting to Database: "' . $alias . '"',
                'component' => 'Database',
                'start'     => $connection->getConnectStart(),
                'duration'  => $connection->getConnectDuration(),
            ];
        }

        foreach (static::$queries as $query) {
            $data[] = [
                'name'      => 'Query',
                'component' => 'Database',
                'start'     => $query['query']->getStartTime(true),
                'duration'  => $query['query']->getDuration(),
                'query'     => $query['query']->debugToolbarDisplay(),
            ];
        }

        return $data;
    }

    /**
     * Returns the data of this collector to be formatted in the toolbar
     */
    public function display(): array
    {
        $data['queries'] = array_map(static function (array $query) {
            $isDuplicate = $query['duplicate'] === true;

            $firstNonSystemLine = '';

            foreach ($query['trace'] as $index => &$line) {
                // simplify file and line
                if (isset($line['file'])) {
                    $line['file'] = clean_path($line['file']) . ':' . $line['line'];
                    unset($line['line']);
                } else {
                    $line['file'] = '[internal function]';
                }

                // find the first trace line that does not originate from `system/`
                if ($firstNonSystemLine === '' && strpos($line['file'], 'SYSTEMPATH') === false) {
                    $firstNonSystemLine = $line['file'];
                }

                // simplify function call
                if (isset($line['class'])) {
                    $line['function'] = $line['class'] . $line['type'] . $line['function'];
                    unset($line['class'], $line['type']);
                }

                if (strrpos($line['function'], '{closure}') === false) {
                    $line['function'] .= '()';
                }

                $line['function'] = str_repeat(chr(0xC2) . chr(0xA0), 8) . $line['function'];

                // add index numbering padded with nonbreaking space
                $indexPadded = str_pad(sprintf('%d', $index + 1), 3, ' ', STR_PAD_LEFT);
                $indexPadded = preg_replace('/\s/', chr(0xC2) . chr(0xA0), $indexPadded);

                $line['index'] = $indexPadded . str_repeat(chr(0xC2) . chr(0xA0), 4);
            }

            return [
                'hover'      => $isDuplicate ? 'This query was called more than once.' : '',
                'class'      => $isDuplicate ? 'duplicate' : '',
                'duration'   => ((float) $query['query']->getDuration(5) * 1000) . ' ms',
                'sql'        => $query['query']->debugToolbarDisplay(),
                'trace'      => $query['trace'],
                'trace-file' => $firstNonSystemLine,
                'qid'        => md5($query['query'] . microtime()),
            ];
        }, static::$queries);

        return $data;
    }

    /**
     * Gets the "badge" value for the button.
     */
    public function getBadgeValue(): int
    {
        return count(static::$queries);
    }

    /**
     * Information to be displayed next to the title.
     *
     * @return string The number of queries (in parentheses) or an empty string.
     */
    public function getTitleDetails(): string
    {
        $this->getConnections();

        $queryCount  = count(static::$queries);
        $uniqueCount = count(array_filter(static::$queries, static fn ($query) => $query['duplicate'] === false));
        $connectionCount = count($this->connections);

        return sprintf(
            '(%d total Quer%s, %d %s unique across %d Connection%s)',
            $queryCount,
            $queryCount > 1 ? 'ies' : 'y',
            $uniqueCount,
            $uniqueCount > 1 ? 'of them' : '',
            $connectionCount,
            $connectionCount > 1 ? 's' : ''
        );
    }

    /**
     * Does this collector have any data collected?
     */
    public function isEmpty(): bool
    {
        return empty(static::$queries);
    }

    /**
     * Display the icon.
     *
     * Icon from https://icons8.com - 1em package
     */
    public function icon(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAADMSURBVEhLY6A3YExLSwsA4nIycQDIDIhRWEBqamo/UNF/SjDQjF6ocZgAKPkRiFeEhoYyQ4WIBiA9QAuWAPEHqBAmgLqgHcolGQD1V4DMgHIxwbCxYD+QBqcKINseKo6eWrBioPrtQBq/BcgY5ht0cUIYbBg2AJKkRxCNWkDQgtFUNJwtABr+F6igE8olGQD114HMgHIxAVDyAhA/AlpSA8RYUwoeXAPVex5qHCbIyMgwBCkAuQJIY00huDBUz/mUlBQDqHGjgBjAwAAACexpph6oHSQAAAAASUVORK5CYII=';
    }

    /**
     * Gets the connections from the database config
     */
    private function getConnections()
    {
        $this->connections = \Config\Database::getConnections();
    }
}
