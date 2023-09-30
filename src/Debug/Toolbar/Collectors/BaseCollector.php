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

/**
 * Collecteur de la barre d'outils de base
 *
 * @credit	<a href="https://codeigniter.com">CodeIgniter 4.2 - CodeIgniter\Debug\Toolbar\Collectors\BaseCollector</a>
 */
abstract class BaseCollector
{
    /**
     * Si ce collecteur possède des données pouvant
     * être affichées dans la chronologie.
     */
    protected bool $hasTimeline = false;

    /**
     * Indique si ce collecteur doit afficher
     * du contenu dans un onglet ou non.
     */
    protected bool $hasTabContent = false;

    /**
     * Si ce collecteur doit afficher
     * une étiquette ou non.
     */
    protected bool $hasLabel = false;

    /**
     * Indique si ce collecteur contient des données qui
     * doivent être affichées dans l'onglet Vars.
     */
    protected bool $hasVarData = false;

    /**
     * Le 'titre' de ce Collector.
     * Utilisé pour nommer les choses dans la barre d'outils HTML.
     */
    protected string $title = '';
 
    /**
     * La 'cle' de ce Collector.
     * Utilisé comme id.
     */
    protected string $key = '';

    /**
     * Obtient le titre du collecteur
     */
    public function getTitle(bool $safe = false): string
    {
        if ($safe) {
            return str_replace(' ', '-', strtolower($this->title));
        }

        return $this->title;
    }

    /**
     * Obtient la cle du collecteur
     */
    public function getKey(): string
    {
        if (empty($this->key)) {
            $this->key = str_replace('Collector', '', basename(static::class));
        }

        return str_replace(' ', '-', strtolower($this->key));
    }

    /**
     * Renvoie toute information devant être affichée à côté du titre.
     */
    public function getTitleDetails(): string
    {
        return '';
    }

    /**
     * Ce collecteur a-t-il besoin de son propre onglet ?
     */
    public function hasTabContent(): bool
    {
        return $this->hasTabContent;
    }

    /**
     * Ce collecteur a-t-il une étiquette ?
     */
    public function hasLabel(): bool
    {
        return $this->hasLabel;
    }

    /**
     * Ce collecteur a-t-il des informations pour la chronologie ?
     */
    public function hasTimelineData(): bool
    {
        return $this->hasTimeline;
    }

    /**
     * Saisit les données pour la chronologie, correctement formatées,
     * ou renvoie un tableau vide.
     */
    public function timelineData(): array
    {
        if (! $this->hasTimeline) {
            return [];
        }

        return $this->formatTimelineData();
    }

    /**
     * Ce collecteur contient-il des données
     * qui doivent être affichées dans l'onglet "Vars" ?
     */
    public function hasVarData(): bool
    {
        return $this->hasVarData;
    }

    /**
     * Obtient une collection de données qui doivent être affichées dans l'onglet "Vars".
     * Le format est un tableau de sections, chacune avec son propre tableau de paires clé/valeur :
     *
     *  $data = [
     *      'section 1' => [
     *          'foo' => 'bar,
     *          'bar' => 'baz'
     *      ],
     *      'section 2' => [
     *          'foo' => 'bar,
     *          'bar' => 'baz'
     *      ],
     *  ];
     */
    public function getVarData()
    {
        return null;
    }

    /**
     * Les classes enfants doivent l'implémenter
     * pour renvoyer les données de la chronologie formatées pour une utilisation correcte.
     *
     * Les données de la chronologie doivent être formatées dans des tableaux qui ressemblent à :
     *
     *  [
     *      'name'      => 'Database::Query',
     *      'component' => 'Database',
     *      'start'     => 10       // milliseconds
     *      'duration'  => 15       // milliseconds
     *  ]
     */
    protected function formatTimelineData(): array
    {
        return [];
    }

    /**
     * Renvoie les données de ce collecteur à formater dans la barre d'outils
     *
     * @return array|string
     */
    public function display()
    {
        return [];
    }

    /**
     * Obtient la valeur "badge" pour le bouton.
     */
    public function getBadgeValue()
    {
        return null;
    }

    /**
     * Ce collecteur a-t-il collecté des données ?
     *
     * Si ce n'est pas le cas, le bouton de la barre d'outils ne s'affichera pas.
     */
    public function isEmpty(): bool
    {
        return false;
    }

    /**
     * Renvoie le code HTML pour afficher l'icône. Devrait soit
     * être SVG, ou encodé en base 64.
     *
     * Les dimensions recommandées sont 24px x 24px
     */
    public function icon(): string
    {
        return '';
    }

    /**
     * Renvoie les paramètres sous forme de tableau.
     */
    public function getAsArray(): array
    {
        return [
            'title'           => $this->getTitle(),
            'titleSafe'       => $this->getTitle(true),
            'key'             => $this->getKey(),
            'titleDetails'    => $this->getTitleDetails(),
            'display'         => $this->display(),
            'badgeValue'      => $this->getBadgeValue(),
            'isEmpty'         => $this->isEmpty(),
            'hasTabContent'   => $this->hasTabContent(),
            'hasLabel'        => $this->hasLabel(),
            'icon'            => $this->icon(),
            'hasTimelineData' => $this->hasTimelineData(),
            'timelineData'    => $this->timelineData(),
        ];
    }
}
