<?php

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
     *
     * @var bool
     */
    protected $hasTimeline = false;

    /**
     * Indique si ce collecteur doit afficher 
	 * du contenu dans un onglet ou non.
     *
     * @var bool
     */
    protected $hasTabContent = false;

    /**
     * Si ce collecteur doit afficher
     * une étiquette ou non.
     *
     * @var bool
     */
    protected $hasLabel = false;

    /**
     * Indique si ce collecteur contient des données qui 
	 * doivent être affichées dans l'onglet Vars.
     *
     * @var bool
     */
    protected $hasVarData = false;

    /**
     * Le 'titre' de ce Collector.
     * Utilisé pour nommer les choses dans la barre d'outils HTML.
     *
     * @var string
     */
    protected $title = '';

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
     * Renvoie toute information devant être affichée à côté du titre.
     */
    public function getTitleDetails(): string
    {
        return '';
    }

    /**
     * Ce collecteur a-t-il besoin de son propre onglet ?
     */
    public function hasTabContent(): bool
    {
        return (bool) $this->hasTabContent;
    }

    /**
     * Ce collecteur a-t-il une étiquette ?
     */
    public function hasLabel(): bool
    {
        return (bool) $this->hasLabel;
    }

    /**
     * Ce collecteur a-t-il des informations pour la chronologie ?
     */
    public function hasTimelineData(): bool
    {
        return (bool) $this->hasTimeline;
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
	 * qui doivent être affichées dans l'onglet "Vars" ?
     */
    public function hasVarData(): bool
    {
        return (bool) $this->hasVarData;
    }

    /**
     * Obtient une collection de données qui doivent être affichées dans l'onglet "Vars".
     * Le format est un tableau de sections, chacune avec son propre tableau de paires clé/valeur :
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
     * Les données de la chronologie doivent être formatées dans des tableaux qui ressemblent à :
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
     * Ce collecteur a-t-il collecté des données ?
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
