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

use BlitzPHP\Event\EventManager;
use BlitzPHP\Loader\Services;
use BlitzPHP\View\View;

/**
 * Collecteur pour l'onglet "Evenements" de la barre d'outils de débogage.
 *
 * @credit	<a href="https://codeigniter.com">CodeIgniter 4.2 - CodeIgniter\Debug\Toolbar\Collectors\Events</a>
 */
class EventsCollector extends BaseCollector
{
    /**
     * {@inheritDoc}
     */
    protected $hasTimeline = false;

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
    protected $title = 'Events';

    /**
     * Instance du service de rendu
     *
     * @var View
     */
    protected $viewer;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->viewer = Services::viewer();
    }

    /**
     * {@inheritDoc}
     */
    protected function formatTimelineData(): array
    {
        $data = [];

        $rows = $this->viewer->getPerformanceData();

        foreach ($rows as $name => $info) {
            $data[] = [
                'name'      => 'View: ' . $info['view'],
                'component' => 'Views',
                'start'     => $info['start'],
                'duration'  => $info['end'] - $info['start'],
            ];
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function display(): array
    {
        $data = [
            'events' => [],
        ];

        foreach (EventManager::getPerformanceLogs() as $row) {
            $key = $row['event'];

            if (! array_key_exists($key, $data['events'])) {
                $data['events'][$key] = [
                    'event'    => $key,
                    'duration' => number_format(($row['end'] - $row['start']) * 1000, 2),
                    'count'    => 1,
                ];

                continue;
            }

            $data['events'][$key]['duration'] += number_format(($row['end'] - $row['start']) * 1000, 2);
            $data['events'][$key]['count']++;
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function getBadgeValue(): int
    {
        return count(EventManager::getPerformanceLogs());
    }

    /**
     * {@inheritDoc}
     *
     * Icon from https://icons8.com - 1em package
     */
    public function icon(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAEASURBVEhL7ZXNDcIwDIVTsRBH1uDQDdquUA6IM1xgCA6MwJUN2hk6AQzAz0vl0ETUxC5VT3zSU5w81/mRMGZysixbFEVR0jSKNt8geQU9aRpFmp/keX6AbjZ5oB74vsaN5lSzA4tLSjpBFxsjeSuRy4d2mDdQTWU7YLbXTNN05mKyovj5KL6B7q3hoy3KwdZxBlT+Ipz+jPHrBqOIynZgcZonoukb/0ckiTHqNvDXtXEAaygRbaB9FvUTjRUHsIYS0QaSp+Dw6wT4hiTmYHOcYZsdLQ2CbXa4ftuuYR4x9vYZgdb4vsFYUdmABMYeukK9/SUme3KMFQ77+Yfzh8eYF8+orDuDWU5LAAAAAElFTkSuQmCC';
    }
}
