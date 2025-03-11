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

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigAdapter extends AbstractAdapter
{
    /**
     * {@inheritDoc}
     */
    protected string $ext = 'twig';

    /**
     * Instance Twig
     *
     * @var Environment
     */
    private $engine;

    /**
     * {@inheritDoc}
     */
    public function __construct(protected array $config, $viewPath = null, protected bool $debug = BLITZ_DEBUG)
    {
        parent::__construct($config, $viewPath, $debug);

        $loader = new FilesystemLoader([
            $this->viewPath,
            VIEW_PATH . 'partials',
            LAYOUT_PATH,
        ]);
        $this->engine = new Environment($loader);

        $this->configure();
    }

    /**
     * Rend une vue en associant le layout si celui ci est defini
     *
     * @param string|null $template
     * @param mixed       $cache_id
     * @param mixed       $compile_id
     * @param mixed       $parent
     */
    public function render(string $view, ?array $options = null, ?bool $saveData = null): string
    {
        $view     = str_replace([$this->viewPath, ' '], '', $view);
        $pathinfo = pathinfo($view, PATHINFO_EXTENSION);
        if ($pathinfo === [] || $pathinfo === '' || $pathinfo === '0') {
            $view .= '.' . $this->ext;
        }

        $this->renderVars['start'] = microtime(true);

        $this->renderVars['view']    = $view;
        $this->renderVars['options'] = $options ?? [];

        $this->renderVars['file'] = $this->getRenderedFile($options, $this->renderVars['view'], $this->ext);

        $output = $this->engine->render($this->renderVars['view'], $this->data);

        $this->logPerformance($this->renderVars['start'], microtime(true), $this->renderVars['view']);

        return $this->decorate($output);
    }

    /**
     * Configure le moteur de template
     */
    private function configure(): void
    {
        if (isset($this->config['configure']) && is_callable($this->config['configure'])) {
            $newInstance = $this->config['configure']($this->engine);
            if ($newInstance instanceof Environment) {
                $this->engine = $newInstance;
            }
        }

        $autoReload = $this->config['auto_reload'] ?: 'auto';
        if ('auto' === $autoReload) {
            $autoReload = on_dev();
        }
        if (true === $autoReload) {
            $this->engine->enableAutoReload();
        } else {
            $this->engine->disableAutoReload();
        }

        $debug = $this->config['debug'] ?: 'auto';
        if ('auto' === $debug) {
            $debug = on_dev();
        }
        if (true === $debug) {
            $this->engine->enableDebug();
        } else {
            $this->engine->disableDebug();
        }

        $strictVariables = $this->config['strict_variables'] ?: 'auto';
        if ('auto' === $strictVariables) {
            $strictVariables = on_dev();
        }
        if (true === $strictVariables) {
            $this->engine->enableStrictVariables();
        } else {
            $this->engine->disableStrictVariables();
        }

        $this->engine->setCharset($this->config['charset'] ?: config('app.charset'));

        $this->engine->setCache($this->config['cache_dir'] ?: VIEW_CACHE_PATH . 'twig');

        // Ajout des variables globals
        $globals = (array) ($this->config['globals'] ?? []);

        foreach ($globals as $name => $global) {
            $this->engine->addGlobal($name, $global);
        }

        // Ajout des filtres
        $filters = (array) ($this->config['filters'] ?? []);

        foreach ($filters as $filter) {
            if ($filter instanceof TwigFilter) {
                $this->engine->addFilter($filter);
            }
        }

        // Ajout des fonctions
        $functions = (array) ($this->config['functions'] ?? []);

        foreach ($functions as $function) {
            if ($function instanceof TwigFunction) {
                $this->engine->addFunction($function);
            }
        }
    }
}
