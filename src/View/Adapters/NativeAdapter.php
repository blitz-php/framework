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

use BlitzPHP\Exceptions\ViewException;
use RuntimeException;

/**
 * Class View
 */
class NativeAdapter extends AbstractAdapter
{
    /**
     * Fusionner les données enregistrées et les données utilisateur
     */
    protected $tempData;

    /**
     * Devrions-nous stocker des informations sur les performances ?
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * Cachez les statistiques sur nos performances ici
     *
     * @var array
     */
    protected $performanceData = [];

    /**
     * Indique si les données doivent être enregistrées entre les rendus.
     *
     * @var bool
     */
    protected $saveData;

    /**
     * Nombre de vues chargées
     *
     * @var int
     */
    protected $viewsCount = 0;

    /**
     * Contient les sections et leurs données.
     *
     * @var array
     */
    protected $sections = [];

    /**
     * Le nom de la section actuelle en cours de rendu, le cas échéant.
     *
     * @var string[]
     */
    protected $sectionStack = [];

    /**
     * Constructor.
     */
    public function __construct(array $config, string $viewPath = VIEW_PATH)
    {
        parent::__construct($config, $viewPath);

        $this->saveData = (bool) ($config['save_data'] ?? true);
    }

    /**
     * {@inheritDoc}
     */
    public function render(string $view, ?array $options = null, ?bool $saveData = null): string
    {
        $view = str_replace([$this->viewPath, ' '], '', $view);

        $this->renderVars['start'] = microtime(true);

        // Stocke les résultats ici donc même si
        // plusieurs vues sont appelées dans une vue, ce ne sera pas le cas
        // nettoyez-le sauf si nous le voulons.
        $saveData ??= $this->saveData;
        $fileExt                     = pathinfo($view, PATHINFO_EXTENSION);
        $realPath                    = empty($fileExt) ? $view . '.php' : $view; // autoriser les vues en .html, .tpl, etc.
        $this->renderVars['view']    = $realPath;
        $this->renderVars['options'] = $options ?? [];

        // A-t-il été mis en cache ?
        if (isset($this->renderVars['options']['cache'])) {
            $cacheName = $this->renderVars['options']['cache_name'] ?? str_replace('.php', '', $this->renderVars['view']);
            $cacheName = str_replace(['\\', '/'], '', $cacheName);

            $this->renderVars['cacheName'] = $cacheName;

            if ($output = cache($this->renderVars['cacheName'])) {
                $this->logPerformance($this->renderVars['start'], microtime(true), $this->renderVars['view']);

                return $output;
            }
        }

        $this->renderVars['file'] = str_replace('/', DS, rtrim($this->viewPath, '/\\') . DS . ltrim($this->renderVars['view'], '/\\'));

        if (! is_file($this->renderVars['file'])) {
            throw ViewException::invalidFile($this->renderVars['view']);
        }

        // Rendre nos données de vue disponibles pour la vue.
        $this->prepareTemplateData($saveData);

        // Enregistrer les variables actuelles
        $renderVars = $this->renderVars;

        $output = (function (): string {
            extract($this->tempData);
            ob_start();
            include $this->renderVars['file'];

            return ob_get_clean() ?: '';
        })();

        // Récupère les variables actuelles
        $this->renderVars = $renderVars;

        // Lors de l'utilisation de mises en page, les données ont déjà été stockées
        // dans $this->sections, et aucune autre sortie valide
        // est autorisé dans $output donc nous allons l'écraser.
        if ($this->layout !== null && $this->sectionStack === []) {
            $layoutView   = $this->layout;
            $this->layout = null;
            // Enregistrer les variables actuelles
            $renderVars = $this->renderVars;
            $output     = $this->render($layoutView, $options, $saveData);
            // Récupère les variables actuelles
            $this->renderVars = $renderVars;
        }

        $this->logPerformance($this->renderVars['start'], microtime(true), $this->renderVars['view']);

        if (($this->debug && (! isset($options['debug']) || $options['debug'] === true))) {
            // Nettoyer nos noms de chemins pour les rendre un peu plus propres
            $this->renderVars['file'] = clean_path($this->renderVars['file']);
            $this->renderVars['file'] = ++$this->viewsCount . ' ' . $this->renderVars['file'];

            $output = '<!-- DEBUG-VIEW START ' . $this->renderVars['file'] . ' -->' . PHP_EOL
                . $output . PHP_EOL
                . '<!-- DEBUG-VIEW ENDED ' . $this->renderVars['file'] . ' -->' . PHP_EOL;
        }

        // Faut-il mettre en cache ?
        if (isset($this->renderVars['options']['cache'])) {
            cache()->write($this->renderVars['cacheName'], $output, (int) $this->renderVars['options']['cache']);
        }

        $this->tempData = null;

        return $output;
    }

    /**
     * {@inheritDoc}
     */
    public function renderString(string $view, ?array $options = null, ?bool $saveData = null): string
    {
        $start = microtime(true);
        $saveData ??= $this->saveData;
        $this->prepareTemplateData($saveData);

        $output = (function (string $view): string {
            extract($this->tempData);
            ob_start();
            eval('?>' . $view);

            return ob_get_clean() ?: '';
        })($view);

        $this->logPerformance($start, microtime(true), $this->excerpt($view));
        $this->tempData = null;

        return $output;
    }

    /**
     * Extraire le premier bit d'une longue chaîne et ajouter des points de suspension
     */
    public function excerpt(string $string, int $length = 20): string
    {
        return (strlen($string) > $length) ? substr($string, 0, $length - 3) . '...' : $string;
    }

    /**
     * {@inheritDoc}
     */
    public function setData(array $data = [], ?string $context = null): self
    {
        if ($context) {
            // $data = \esc($data, $context);
        }

        $this->tempData = $data;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addData(array $data = [], ?string $context = null): self
    {
        if ($context) {
            // $data = \esc($data, $context);
        }

        $this->tempData ??= $this->data;
        $this->tempData = array_merge($this->tempData, $data);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setVar(string $name, $value = null, ?string $context = null): self
    {
        if ($context) {
            // $value = esc($value, $context);
        }

        $this->tempData ??= $this->data;
        $this->tempData[$name] = $value;

        return $this;
    }

    /**
     * Renvoie les données actuelles qui seront affichées dans la vue.
     */
    public function getData(): array
    {
        return $this->tempData ?? $this->data;
    }

    /**
     * Spécifie que la vue actuelle doit étendre une mise en page existante.
     */
    public function extend(string $layout)
    {
        $this->layout = $layout;
    }

    /**
     * Commence contient le contenu d'une section dans la mise en page.
     */
    public function start(string $name)
    {
        $this->sectionStack[] = $name;

        ob_start();
    }

    /**
     * Commence contient le contenu d'une section dans la mise en page.
     *
     * @alias self::start()
     */
    public function section(string $name): void
    {
        $this->start($name);
    }

    /**
     * Commence contient le contenu d'une section dans la mise en page.
     *
     * @alias self::start()
     */
    public function begin(string $name): void
    {
        $this->start($name);
    }

    /**
     * Capture la dernière section
     *
     * @throws RuntimeException
     */
    public function stop()
    {
        $contents = ob_get_clean();

        if ($this->sectionStack === []) {
            throw new RuntimeException('View themes, no current section.');
        }

        $section = array_pop($this->sectionStack);

        // Assurez-vous qu'un tableau existe afin que nous puissions stocker plusieurs entrées pour cela.
        if (! array_key_exists($section, $this->sections)) {
            $this->sections[$section] = [];
        }

        $this->sections[$section][] = $contents;
    }

    /**
     * Capture la dernière section
     *
     * @throws RuntimeException
     * @alias self::stop()
     */
    public function endSection(): void
    {
        $this->stop();
    }

    /**
     * Capture la dernière section
     *
     * @throws RuntimeException
     * @alias self::stop()
     */
    public function end(): void
    {
        $this->stop();
    }

    /**
     * Restitue le contenu d'une section.
     */
    public function show(string $sectionName)
    {
        if (! isset($this->sections[$sectionName])) {
            echo '';

            return;
        }

        $start = $end = '';
        if ($sectionName === 'css') {
            $start = "<style type=\"text/css\">\n";
            $end   = "</style>\n";
        }
        if ($sectionName === 'js') {
            $start = "<script type=\"text/javascript\">\n";
            $end   = "</script>\n";
        }

        echo $start;

        foreach ($this->sections[$sectionName] as $key => $contents) {
            echo $contents;
            unset($this->sections[$sectionName][$key]);
        }
        echo $end;
    }

    /**
     * Affichage rapide du contenu principal
     */
    public function renderView(): void
    {
        $this->show('content');
    }

    /**
     * Utilisé dans les vues de mise en page pour inclure des vues supplémentaires.
     *
     * @param mixed $saveData
     */
    public function insert(string $view, ?array $data = [], ?array $options = null, $saveData = true): string
    {
        $view = preg_replace('#\.php$#i', '', $view) . '.php';
        $view = str_replace(' ', '', $view);

        if ($view[0] !== '/') {
            $current_dir = pathinfo($this->renderVars['file'] ?? '', PATHINFO_DIRNAME);
            if (file_exists(rtrim($current_dir, DS) . DS . $view)) {
                $view = rtrim($current_dir, DS) . DS . $view;
            } elseif (file_exists($this->viewPath . 'partials' . DS . $view)) {
                $view = $this->viewPath . 'partials' . DS . $view;
            } elseif (file_exists($this->viewPath . trim(dirname($current_dir), '/\\') . DS . $view)) {
                $view = $this->viewPath . trim(dirname($current_dir), '/\\') . DS . $view;
            } elseif (file_exists(VIEW_PATH . 'partials' . DS . $view)) {
                $view = VIEW_PATH . 'partials' . DS . $view;
            } elseif (file_exists(VIEW_PATH . trim(dirname($current_dir), '/\\') . DS . $view)) {
                $view = VIEW_PATH . trim(dirname($current_dir), '/\\') . DS . $view;
            }
        }

        return $this->addData($data)->render($view, $options, $saveData);
    }

    /**
     * Utilisé dans les vues de mise en page pour inclure des vues supplémentaires.
     *
     * @alias self::insert()
     *
     * @param mixed $saveData
     */
    public function include(string $view, ?array $data = [], ?array $options = null, $saveData = true): string
    {
        return $this->insert($view, $data, $options, $saveData);
    }

    /**
     * Renvoie les données de performances qui ont pu être collectées
     * lors de l'exécution. Utilisé principalement dans la barre d'outils de débogage.
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

    protected function prepareTemplateData(bool $saveData): void
    {
        $this->tempData ??= $this->data;

        if ($saveData) {
            $this->data = $this->tempData;
        }
    }
}
