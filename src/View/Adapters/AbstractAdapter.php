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

use BlitzPHP\Contracts\Autoloader\LocatorInterface;
use BlitzPHP\Contracts\View\RendererInterface;
use BlitzPHP\Exceptions\ViewException;
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\View\ViewDecoratorTrait;

abstract class AbstractAdapter implements RendererInterface
{
    use ViewDecoratorTrait;

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
    protected $viewPath = '';

    /**
     * Extension des fichiers de vue
     */
    protected string $ext = '';

    /**
     * Instance de Locator lorsque nous devons tenter de trouver une vue qui n'est pas à l'emplacement standard.
     */
    protected LocatorInterface $locator;

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
     * {@inheritDoc}
     *
     * @param array       $config   Configuration actuelle de l'adapter
     * @param string|null $viewPath Dossier principal dans lequel les vues doivent être cherchées
     * @param bool        $debug    Devrions-nous stocker des informations sur les performances ?
     */
    public function __construct(protected array $config, $viewPath = null, protected bool $debug = BLITZ_DEBUG)
    {
        helper('assets');

        if (is_string($viewPath) && is_dir($viewPath = rtrim($viewPath, '\\/ ') . DS)) {
            $this->viewPath = $viewPath;
        }

        $this->locator = service('locator');

        $this->ext = preg_replace('#^\.#', '', $config['extension'] ?? $this->ext);
    }

    /**
     * {@inheritDoc}
     */
    public function setData(array $data = [], ?string $context = null): self
    {
        if ($context !== null && $context !== '' && $context !== '0') {
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
        if ($context !== null && $context !== '' && $context !== '0') {
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
        if ($context !== null && $context !== '' && $context !== '0') {
            $value = esc($value, $context);
        }

        $this->data[$name] = $value;

        return $this;
    }

    /**
     * Définit plusieurs éléments de données de vue à la fois.
     */
    public function with(array|string $key, mixed $value = null, ?string $context = null): self
    {
        if (is_array($key)) {
            $context = $value;
        } else {
            $key = [$key => $value];
        }

        return $this->addData($key, $context);
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
     * Recupère ou modifie le titre de la page
     *
     * @return self|string
     */
    public function title(?string $title = null)
    {
        if ($title === null || $title === '' || $title === '0') {
            return $this->getData()['title'] ?? '';
        }

        return $this->setVar('title', $title);
    }

    /**
     * Recupère ou modifie les elements de balises "meta"
     *
     * @return self|string
     */
    public function meta(string $key, ?string $value = null)
    {
        $meta = $this->getData()['meta'] ?? [];

        if ($value === null || $value === '' || $value === '0') {
            return $meta[$key] ?? '';
        }

        $meta[$key] = esc($value);

        return $this->setVar('meta', $meta);
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $view, ?string $ext = null, array $options = []): bool
    {
        try {
            return str_contains($this->getRenderedFile($options, $view, $ext), 'Views');
        } catch (ViewException) {
            return false;
        }
    }

    /**
     * Recupere le chemin absolue du fichier de vue a rendre
     */
    protected function getRenderedFile(?array $options, string $view, ?string $ext = null): string
    {
        $options = (array) $options;
        $ext ??= $this->ext;

        $viewPath = $options['viewPath'] ?? $this->viewPath;
        $file     = ! empty($viewPath) ? str_replace('/', DS, rtrim($viewPath, '/\\') . DS . ltrim($view, '/\\')) : $view;

        $file = Helpers::ensureExt($file, $ext);

        if (! is_file($file)) {
            $file = $this->locator->locateFile($view, 'Views', $ext);
        }

        $file = realpath($file);

        // locateFile renverra une chaîne vide si le fichier est introuvable.
        if (! is_file($file)) {
            throw ViewException::invalidFile($view);
        }

        return $file;
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
