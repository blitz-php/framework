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

use BlitzPHP\Container\Services;

/**
 * Collecteur de temporisateurs pour la barre d'outils de débogage
 *
 * @credit	<a href="https://codeigniter.com">CodeIgniter 4.2 - CodeIgniter\Debug\Toolbar\Collectors\Timers</a>
 */
class TimersCollector extends BaseCollector
{
    /**
     * {@inheritDoc}
     */
    protected bool $hasTimeline = true;

    /**
     * {@inheritDoc}
     */
    protected bool $hasTabContent = false;

    /**
     * {@inheritDoc}
     */
    protected string $title = 'Timers';

    /**
     * {@inheritDoc}
     */
    protected function formatTimelineData(): array
    {
        $data = [];

        $benchmark = Services::timer(true);
        $rows      = $benchmark->getTimers(6);

        foreach ($rows as $name => $info) {
            if ($name === 'total_execution') {
                continue;
            }

            $data[] = [
                'name'      => ucwords(str_replace('_', ' ', $name)),
                'component' => 'Timer',
                'start'     => $info['start'],
                'duration'  => $info['end'] - $info['start'],
            ];
        }

        return $data;
    }
}
