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

use BlitzPHP\Debug\Toolbar\Collectors\ViewsCollector;
use BlitzPHP\Exceptions\ViewException;
use BlitzPHP\Utilities\Helpers;
use RuntimeException;

/**
 * Class View
 */
class NativeAdapter extends AbstractAdapter
{
    /**
     * {@inheritDoc}
     */
    protected string $ext = 'php';

    /**
     * Fusionner les données enregistrées et les données utilisateur
     */
    protected $tempData;

    /**
     * Indique si les données doivent être enregistrées entre les rendus.
     *
     * @var bool
     */
    protected $saveData;

    /**
     * Nombre de vues chargées
     */
    protected int $viewsCount = 0;

    /**
     * Contient les sections et leurs données.
     */
    protected array $sections = [];

    /**
     * Le nom de la section actuelle en cours de rendu, le cas échéant.
     *
     * @var string[]
     */
    protected array $sectionStack = [];

    /**
     * Contient les css charges a partie de la section actuelle en cours de rendu
     */
    protected array $_styles = [];

    /**
     * Contient les librairies css charges a partie de la section actuelle en cours de rendu
     */
    protected array $_lib_styles = [];

    /**
     * Contient les scripts js charges a partie de la section actuelle en cours de rendu
     */
    protected array $_scripts = [];

    /**
     * Contient les scripts js des librairies charges a partie de la section actuelle en cours de rendu
     */
    protected array $_lib_scripts = [];

    /**
     * {@inheritDoc}
     */
    public function __construct(protected array $config, $viewPathLocator = null, protected bool $debug = BLITZ_DEBUG)
    {
        parent::__construct($config, $viewPathLocator, $debug);

        $this->saveData = (bool) ($config['save_data'] ?? true);
    }

    /**
     * {@inheritDoc}
     */
    public function render(string $view, ?array $options = null, ?bool $saveData = null): string
    {
        $this->renderVars['start']   = microtime(true);
        $this->renderVars['view']    = $view;
        $this->renderVars['options'] = $options ?? [];

        // Stocke les résultats ici donc même si
        // plusieurs vues sont appelées dans une vue, ce ne sera pas le cas
        // nettoyez-le sauf si nous le voulons.
        $saveData ??= $this->saveData;

        // A-t-il été mis en cache ?
        if (isset($this->renderVars['options']['cache'])) {
            $cacheName = $this->renderVars['options']['cache_name'] ?? str_replace('.' . $this->ext, '', $this->renderVars['view']);
            $cacheName = str_replace(['\\', '/'], '', $cacheName);

            $this->renderVars['cacheName'] = $cacheName;

            if ($output = cache($this->renderVars['cacheName'])) {
                $this->logPerformance($this->renderVars['start'], microtime(true), $this->renderVars['view']);

                return $output;
            }
        }

        $this->renderVars['file'] = $this->getRenderedFile($this->renderVars['options'], $this->renderVars['view'], $this->ext);

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

        // Lors de l'utilisation de mises en page, les données ont déjà été stockées
        // dans $this->sections, et aucune autre sortie valide
        // est autorisé dans $output donc nous allons l'écraser.
        if ($this->layout !== null && $this->sectionStack === []) {
            $layoutView   = $this->layout;
            $this->layout = null;
            // Enregistrer les variables actuelles
            $renderVars = $this->renderVars;
            $output     = $this->render($layoutView, array_merge($options ?? [], ['viewPath' => LAYOUT_PATH]), $saveData);
        }

        $this->logPerformance($this->renderVars['start'], microtime(true), $this->renderVars['view']);

        if (($this->debug && (! isset($options['debug']) || $options['debug'] === true))) {
            if (in_array(ViewsCollector::class, config('toolbar.collectors'), true)) {
                // Nettoyer nos noms de chemins pour les rendre un peu plus propres
                $this->renderVars['file'] = clean_path($this->renderVars['file']);
                $this->renderVars['file'] = ++$this->viewsCount . ' ' . $this->renderVars['file'];

                $output = '<!-- DEBUG-VIEW START ' . $this->renderVars['file'] . ' -->' . PHP_EOL
                    . $output . PHP_EOL
                    . '<!-- DEBUG-VIEW ENDED ' . $this->renderVars['file'] . ' -->' . PHP_EOL;
            }
        }

        $output = $this->decorate($output);

        // Faut-il mettre en cache ?
        if (isset($this->renderVars['options']['cache'])) {
            cache()->write($this->renderVars['cacheName'], $output, (int) $this->renderVars['options']['cache']);
        }

        $this->tempData = null;

        // Récupère les variables actuelles
        $this->renderVars = $renderVars;

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
            $data = esc($data, $context);
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
            $data = esc($data, $context);
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
            $value = esc($value, $context);
        }

        $this->tempData ??= $this->data;
        $this->tempData[$name] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(): array
    {
        return $this->tempData ?? $this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function resetData(): self
    {
        $this->data     = [];
        $this->tempData = [];

        return $this;
    }

    /**
     * Spécifie que la vue actuelle doit étendre une mise en page existante.
     */
    public function extend(string $layout)
    {
        $this->setLayout($layout);
    }

    /**
     * Commence le contenu d'une section dans la mise en page.
     */
    public function start(string $name, ?string $content = null)
    {
        if (null === $content) {
            $this->sectionStack[] = $name;

            ob_start();
        } else {
            $this->sections[$name] = [$content];
        }
    }

    /**
     * Commence le contenu d'une section dans la mise en page.
     *
     * @alias self::start()
     */
    public function section(string $name, ?string $content = null): void
    {
        $this->start($name, $content);
    }

    /**
     * Commence le contenu d'une section dans la mise en page.
     *
     * @alias self::start()
     */
    public function begin(string $name, ?string $content = null): void
    {
        $this->start($name, $content);
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
     *
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
     *
     * @alias self::stop()
     */
    public function end(): void
    {
        $this->stop();
    }

    /**
     * Restitue le contenu d'une section.
     */
    public function show(string $sectionName, bool $preserve = false)
    {
        if (! isset($this->sections[$sectionName])) {
            echo '';

            return;
        }

        foreach ($this->sections[$sectionName] as $key => $contents) {
            echo $contents;

            if (false === $preserve) {
                unset($this->sections[$sectionName][$key]);
            }
        }
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
     */
    public function insert(string $view, ?array $data = [], ?array $options = null, ?bool $saveData = null): string
    {
        $view = Helpers::ensureExt($view, $this->ext);
        $view = str_replace(' ', '', $view);

        if ($view[0] !== '/') {
            $view = $this->retrievePartialPath($view);
        }

        return $this->addData($data)->render($view, $options, $saveData);
    }

    /**
     * Utilisé dans les vues de mise en page pour inclure des vues supplémentaires.
     *
     * @alias self::insert()
     */
    public function include(string $view, ?array $data = [], ?array $options = null, ?bool $saveData = null): string
    {
        return $this->insert($view, $data, $options, $saveData);
    }

    /**
     * Utilisé dans les vues de mise en page pour inclure des vues supplémentaires lorsqu'une condition est remplie.
     */
    public function insertWhen(bool|callable $condition, string $view, ?array $data = [], ?array $options = null, ?bool $saveData = null): string
    {
        if (is_callable($condition)) {
            $condition = $condition();
        }

        if (true === $condition) {
            return $this->insert($view, $data, $options, $saveData);
        }

        return '';
    }

    /**
     * Utilisé dans les vues de mise en page pour inclure des vues supplémentaires lorsqu'une condition est remplie.
     *
     * @alias self::insertWhen()
     */
    public function includeWhen(bool|callable $condition, string $view, ?array $data = [], ?array $options = null, ?bool $saveData = null): string
    {
        return $this->insertWhen($condition, $view, $data, $options, $saveData);
    }

    /**
     * Utilisé dans les vues de mise en page pour inclure des vues supplémentaires lorsqu'une condition n'est pas remplie.
     */
    public function insertUnless(bool|callable $condition, string $view, ?array $data = [], ?array $options = null, ?bool $saveData = null): string
    {
        if (is_callable($condition)) {
            $condition = $condition();
        }

        return $this->insertWhen(false === $condition, $view, $data, $options, $saveData);
    }

    /**
     * Utilisé dans les vues de mise en page pour inclure des vues supplémentaires lorsqu'une condition n'est pas remplie.
     *
     * @alias self::insertUnless()
     */
    public function includeUnless(bool|callable $condition, string $view, ?array $data = [], ?array $options = null, ?bool $saveData = null): string
    {
        return $this->insertUnless($condition, $view, $data, $options, $saveData);
    }

    /**
     * Utilisé dans les vues de mise en page pour inclure des vues supplémentaires si elle existe.
     *
     * @alias self::insertIf()
     */
    public function includeIf(string $view, ?array $data = [], ?array $options = null, ?bool $saveData = null): string
    {
        return $this->insertIf($view, $data, $options, $saveData);
    }

    /**
     * Utilisé dans les vues de mise en page pour inclure des vues supplémentaires si elle existe.
     */
    public function insertIf(string $view, ?array $data = [], ?array $options = null, ?bool $saveData = null): string
    {
        $view = Helpers::ensureExt($view, $this->ext);
        $view = str_replace(' ', '', $view);

        if ($view[0] !== '/') {
            $view = $this->retrievePartialPath($view);
        }

        if (is_file($view)) {
            return $this->addData($data)->render($view, $options, $saveData);
        }

        return '';
    }

    /**
     * Utilisé dans les vues de mise en page pour inclure des vues supplémentaires si elle existe.
     */
    public function insertFirst(array $views, ?array $data = [], ?array $options = null, ?bool $saveData = null): string
    {
        foreach ($views as $view) {
            $view = Helpers::ensureExt($view, $this->ext);
            $view = str_replace(' ', '', $view);

            if ($view[0] !== '/') {
                $view = $this->retrievePartialPath($view);
            }

            if (is_file($view)) {
                return $this->addData($data)->render($view, $options, $saveData);
            }
        }

        throw ViewException::invalidFile(implode(' OR ', $views));
    }

    /**
     * Compile de manière conditionnelle une chaîne de classe CSS.
     */
    public function class(array $classes): string
    {
        if ($classes === []) {
            return '';
        }

        $class = [];

        foreach ($classes as $key => $value) {
            if (is_int($key)) {
                $class[] = $value;
            } elseif (true === $value) {
                $class[] = $key;
            }
        }

        return 'class="' . implode(' ', $class) . '"';
    }

    /**
     * Ajoute conditionnellement des styles CSS en ligne à un élément HTML
     */
    public function style(array $styles): string
    {
        if ($styles === []) {
            return '';
        }

        $style = [];

        foreach ($styles as $key => $value) {
            if (is_int($key)) {
                $style[] = $value . ';';
            } elseif (true === $value) {
                $style[] = $key . ';';
            }
        }

        return 'style="' . implode(' ', $style) . '"';
    }

    /**
     * Utiliser pour indiquer facilement si une case à cocher HTML donnée est "cochée".
     * Indiquera que la case est cochée si la condition fournie est évaluée à true.
     */
    public function checked(bool|string $condition): string
    {
        return true === filter_var($condition, FILTER_VALIDATE_BOOLEAN) ? 'checked="checked"' : '';
    }

    /**
     * Utiliser pour indiquer si une option de sélection donnée doit être "sélectionnée".
     */
    public function selected(bool|string $condition): string
    {
        return true === filter_var($condition, FILTER_VALIDATE_BOOLEAN) ? 'selected="selected"' : '';
    }

    /**
     * Utiliser pour indiquer si un élément donné doit être "désactivé".
     */
    public function disabled(bool|string $condition): string
    {
        return true === filter_var($condition, FILTER_VALIDATE_BOOLEAN) ? 'disabled' : '';
    }

    /**
     * Utiliser pour indiquer si un élément donné doit être "readonly".
     */
    public function readonly(bool|string $condition): string
    {
        return true === filter_var($condition, FILTER_VALIDATE_BOOLEAN) ? 'readonly' : '';
    }

    /**
     * Utiliser pour indiquer si un élément donné doit être "obligatoire".
     */
    public function required(bool|string $condition): string
    {
        return true === filter_var($condition, FILTER_VALIDATE_BOOLEAN) ? 'required' : '';
    }

    /**
     * Génère un champ input caché à utiliser dans les formulaires générés manuellement.
     */
    public function csrf(?string $id): string
    {
        return csrf_field($id);
    }

    /**
     * Générer un champ de formulaire pour usurper le verbe HTTP utilisé par les formulaires.
     */
    public function method(string $method): string
    {
        return method_field($method);
    }

    /**
     * Ajoute un fichier css de librairie a la vue
     */
    public function addLibCss(string ...$src): self
    {
        $this->_lib_styles = array_merge($this->_lib_styles, $src);

        return $this;
    }

    /**
     * Ajoute un fichier css a la vue
     */
    public function addCss(string ...$src): self
    {
        $this->_styles = array_merge($this->_styles, $src);

        return $this;
    }

    /**
     * Compile les fichiers de style de l'instance et genere les link:href vers ceux-ci
     */
    public function stylesBundle(): void
    {
        if (! empty($this->_lib_styles)) {
            lib_styles(array_unique($this->_lib_styles));
        }

        if (! empty($this->_styles)) {
            styles(array_unique($this->_styles));
        }

        $this->show('css');
    }

    /**
     * Ajoute un fichier js de librairie a la vue
     */
    public function addLibJs(string ...$src): self
    {
        $this->_lib_scripts = array_merge($this->_lib_scripts, $src);

        return $this;
    }

    /**
     * Ajoute un fichier js a la vue
     */
    public function addJs(string ...$src): self
    {
        $this->_scripts = array_merge($this->_scripts, $src);

        return $this;
    }

    /**
     * Compile les fichiers de script de l'instance et genere les link:href vers ceux-ci
     */
    public function scriptsBundle(): void
    {
        if (! empty($this->_lib_scripts)) {
            lib_scripts(array_unique($this->_lib_scripts));
        }

        if (! empty($this->_scripts)) {
            scripts(array_unique($this->_scripts));
        }

        $this->show('js');
    }

    protected function prepareTemplateData(bool $saveData): void
    {
        $this->tempData ??= $this->data;

        if ($saveData) {
            $this->data = $this->tempData;
        }
    }

    private function retrievePartialPath(string $view): string
    {
        $current_dir = pathinfo($this->renderVars['file'] ?? '', PATHINFO_DIRNAME);
        if (file_exists(rtrim($current_dir, DS) . DS . $view)) {
            $view = rtrim($current_dir, DS) . DS . $view;
        } elseif (file_exists(rtrim($current_dir, DS) . DS . 'partials' . DS . $view)) {
            $view = rtrim($current_dir, DS) . DS . 'partials' . DS . $view;
        } elseif (file_exists($this->viewPath . 'partials' . DS . $view)) {
            $view = $this->viewPath . 'partials' . DS . $view;
        } elseif (file_exists($this->viewPath . trim(dirname($current_dir), '/\\') . DS . $view)) {
            $view = $this->viewPath . trim(dirname($current_dir), '/\\') . DS . $view;
        } elseif (file_exists(VIEW_PATH . 'partials' . DS . $view)) {
            $view = VIEW_PATH . 'partials' . DS . $view;
        } elseif (file_exists(VIEW_PATH . trim(dirname($current_dir), '/\\') . DS . $view)) {
            $view = VIEW_PATH . trim(dirname($current_dir), '/\\') . DS . $view;
        }

        return $view;
    }
}
