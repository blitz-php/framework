<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\View\Adapters;

use BlitzPHP\View\RendererInterface;

abstract class AbstractAdapter implements RendererInterface
{
    /**
     * Données mises à la disposition des vues.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Les variables de rendu
     *
     * @var array
     */
    protected $renderVars = [];

    /**
     * Le répertoire de base dans lequel rechercher nos vues.
     *
     * @var string
     */
    protected $viewPath;

    /**
     * Configuration actuelle de l'adapter
     *
     * @var array
     */
    protected $config;

    /**
     * Le nom de la mise en page utilisée, le cas échéant.
     * Défini par la méthode "extend" utilisée dans les vues.
     *
     * @var string|null
     */
    protected $layout;

    /**
     * Les statistiques sur nos performances ici
     *
     * @var array
     */
    protected $performanceData = [];

    /**
     * Devrions-nous stocker des informations sur les performances ?
     *
     * @var bool
     */
    protected $debug = false;


    /**
     * {@inheritDoc}
     */
    public function __construct(array $config, string $viewPath = VIEW_PATH, ?bool $debug = null)
    {
        $this->config   = $config;
        $this->debug = $debug ?? BLITZ_DEBUG;
        $this->viewPath = rtrim($viewPath, '\\/ ') . DS;
    }

    /**
     * {@inheritDoc}
     */
    public function setData(array $data = [], ?string $context = null): self
    {
        if ($context) {
            $data = esc($data, $context);
        }

        $this->data = $data;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(): array
    {
        return $this->data;
    }


    /**
     * {@inheritDoc}
     */
    public function addData(array $data = [], ?string $context = null): self
    {
        if ($context) {
            $data = esc($data, $context);
        }

        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setVar(string $name, $value = null, ?string $context = null): self
    {
        if ($context) {
            $value = esc($value, $context);
        }

        $this->data[$name] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function resetData(): self
    {
        $this->data = [];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setLayout(?string $layout): self
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function renderString(string $view, ?array $options = null, bool $saveData = false): string
    {
        return $this->render($view, $options, $saveData);
    }

    /**
     * {@inheritDoc}
     */
    public function getPerformanceData(): array
    {
        return $this->performanceData;
    }

    /**
     * Consigne les données de performances pour le rendu d'une vue.
     */
    protected function logPerformance(float $start, float $end, string $view)
    {
        if ($this->debug) {
            $this->performanceData[] = [
                'start' => $start,
                'end'   => $end,
                'view'  => $view,
            ];
        }
    }

    /**
     * Construit la sortie en fonction d'un nom de fichier et de tout données déjà définies.
     *
     * Options valides :
     * - cache Nombre de secondes à mettre en cache pour
     * - cache_name Nom à utiliser pour le cache
     *
     * @param string     $view     Nom de fichier de la source de la vue
     * @param array|null $options  Réservé à des utilisations tierces car
     *                             il peut être nécessaire de transmettre des
     *                             informations supplémentaires à d'autres moteurs de modèles.
     * @param bool|null  $saveData Si vrai, enregistre les données pour les appels suivants,
     *                             si faux, nettoie les données après affichage,
     *                             si null, utilise le paramètre de configuration.
     */
    abstract public function render(string $view, ?array $options = null, ?bool $saveData = null): string;
}
