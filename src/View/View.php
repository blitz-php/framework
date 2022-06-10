<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\View;

use BlitzPHP\Exceptions\ConfigException;
use BlitzPHP\View\Adapters\BladeAdapter;
use BlitzPHP\View\Adapters\LatteAdapter;
use BlitzPHP\View\Adapters\NativeAdapter;
use BlitzPHP\View\Adapters\PlatesAdapter;
use BlitzPHP\View\Adapters\SmartyAdapter;
use BlitzPHP\View\Adapters\TwigAdapter;

class View
{
    /**
     * Views configuration
     *
     * @var array
     */
    private $config;

    /**
     * @var RendererInterface
     */
    private $adapter;

    /**
     * Liste des adapters pris en comptes
     *
     * @var array
     */
    public static $validAdapters = [
        'native' => NativeAdapter::class,
        'blade'  => BladeAdapter::class,
        'latte'  => LatteAdapter::class,
        'plates' => PlatesAdapter::class,
        'smarty' => SmartyAdapter::class,
        'twig'   => TwigAdapter::class,
    ];

    /**
     * Options de la vue
     *
     * @var array
     */
    private $options = [];

    /**
     * La vue à rendre
     *
     * @var string
     */
    private $view;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->config = config('view');

        $this->setAdapter($this->config['active_adapter'] ?? 'native');
    }

    public function __toString()
    {
        return $this->get();
    }

    /**
     * Recupere et retourne le code html de la vue créée
     *
     * @param bool|string $compress
     */
    public function get($compress = 'auto'): string
    {
        $output = $this->adapter->render($this->view, $this->options);

        return $this->compressView($output, $compress);
    }

    /**
     * Affiche la vue generee au navigateur
     */
    public function render(): void
    {
        $compress = $this->config['compress_output'] ?? 'auto';

        echo $this->get($compress);
    }

    /**
     * Modifier les options d'affichage
     */
    public function setOptions(?array $options = []): self
    {
        $this->options = (array) $options;

        return $this;
    }

    /**
     * Définir la vue à afficher
     */
    public function display(string $view): self
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Définit plusieurs éléments de données de vue à la fois.
     */
    public function addData(array $data = [], ?string $context = null): self
    {
        $this->adapter->addData($data, $context);

        return $this;
    }

    /**
     * Définit une seule donnée de vue.
     *
     * @param mixed|null $value
     */
    public function setVar(string $name, $value = null, ?string $context = null): self
    {
        $this->adapter->setVar($name, $value, $context);

        return $this;
    }

    /**
     * Remplacer toutes les données de vue par de nouvelles données
     */
    public function setData(array $data, ?string $context = null): self
    {
        $this->adapter->setData($data, $context);

        return $this;
    }

    /**
     * Supprime toutes les données de vue du système.
     */
    public function resetData(): self
    {
        $this->adapter->resetData();

        return $this;
    }

    /**
     * Defini l'adapteur à utiliser
     */
    public function setAdapter(string $adapter, array $config = []): self
    {
        if (! array_key_exists($adapter, self::$validAdapters)) {
            $adapter = 'native';
        }
        if (empty($this->config['adapters']) || ! is_array($this->config['adapters'])) {
            $this->config['adapters'] = [];
        }

        $config = array_merge($this->config['adapters'][$adapter] ?? [], $config);
        if (empty($config)) {
            throw ConfigException::viewAdapterConfigNotFound($adapter);
        }

        $this->adapter       = new self::$validAdapters[$adapter]($config, $this->config['view_base'] ?? VIEW_PATH);

        return $this;
    }

    /**
     * Compresse le code html d'une vue
     *
     * @param bool|string $compress
     */
    private function compressView(string $output, $compress = 'auto'): string
    {
        if ($compress === 'auto') {
            $compress = is_online();
        }

        return true === $compress ? trim(preg_replace('/\s+/', ' ', $output)) : $output;
    }
}
