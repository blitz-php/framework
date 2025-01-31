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

use BlitzPHP\Validation\ErrorBag;
use BlitzPHP\View\View;

/**
 * Collecteur de vues pour la barre d'outils de débogage
 *
 * @credit	<a href="https://codeigniter.com">CodeIgniter 4.2 - CodeIgniter\Debug\Toolbar\Collectors\Views</a>
 */
class ViewsCollector extends BaseCollector
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
    protected bool $hasLabel = true;

    /**
     * {@inheritDoc}
     */
    protected bool $hasVarData = true;

    /**
     * {@inheritDoc}
     */
    protected string $title = 'Vues';

    /**
     * Instance du service de rendu
     *
     * @var View
     */
    protected $viewer;

    /**
     * Compteur de vues
     */
    protected array $views = [];

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->viewer = service('viewer');
    }

    /**
     * {@inheritDoc}
     */
    protected function formatTimelineData(): array
    {
        $data = [];

        $rows = $this->viewer->getPerformanceData();

        foreach ($rows as $info) {
            $data[] = [
                'name'      => 'Vue: ' . $info['view'],
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
    public function getVarData(): array
    {
        return [
            'Données de la vues' => array_filter($this->viewer->getData(), static fn ($data) => ! ($data instanceof ErrorBag)),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getBadgeValue(): int
    {
        return count($this->viewer->getPerformanceData());
    }

    /**
     * {@inheritDoc}
     *
     * Icon from https://icons8.com - 1em package
     */
    public function icon(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAADeSURBVEhL7ZSxDcIwEEWNYA0YgGmgyAaJLTcUaaBzQQEVjMEabBQxAdw53zTHiThEovGTfnE/9rsoRUxhKLOmaa6Uh7X2+UvguLCzVxN1XW9x4EYHzik033Hp3X0LO+DaQG8MDQcuq6qao4qkHuMgQggLvkPLjqh00ZgFDBacMJYFkuwFlH1mshdkZ5JPJERA9JpI6xNCBESvibQ+IURA9JpI6xNCBESvibQ+IURA9DTsuHTOrVFFxixgB/eUFlU8uKJ0eDBFOu/9EvoeKnlJS2/08Tc8NOwQ8sIfMeYFjqKDjdU2sp4AAAAASUVORK5CYII=';
    }
}
