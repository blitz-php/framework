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

use Smarty;

class SmartyAdapter extends AbstractAdapter
{
	/**
	 * {@inheritDoc}
	 */
	protected string $ext = 'tpl';

    /**
     * Instance Smarty
     *
     * @var Smarty
     */
    private $engine;

    /**
     * {@inheritDoc}
     */
    public function __construct(protected array $config, $viewPathLocator = null, protected bool $debug = BLITZ_DEBUG)
    {
        parent::__construct($config, $viewPathLocator, $debug);

        $this->engine = new Smarty();

        $this->configure();
    }

    /**
     * Active la mise en cache des pages
     */
    public function enableCache(): self
    {
        $this->engine->setCaching(Smarty::CACHING_LIFETIME_SAVED);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function render(string $view, ?array $options = null, ?bool $saveData = null): string
    {
        $view = str_replace([$this->viewPath, ' '], '', $view);
        if (empty(pathinfo($view, PATHINFO_EXTENSION))) {
            $view .= '.' . $this->ext;
        }

        $this->renderVars['start']   = microtime(true);
        $this->renderVars['view']    = $view;
        $this->renderVars['options'] = $options ?? [];

        $this->renderVars['file'] = $this->getRenderedFile($options, $this->renderVars['view'], $this->ext);

        if (! empty($layout = $this->layout)) {
            if (empty(pathinfo($layout, PATHINFO_EXTENSION))) {
                $layout .= '.' . $this->ext;
            }
            $view = 'extends:[layouts]' . $layout . '|' . $view;
        }

        $this->engine->assign($this->data);

        // Doit-on mettre en cache?
        if (! empty($this->renderVars['options']['cache_name']) || ! empty($this->renderVars['options']['cache'])) {
            $this->enableCache();
            $this->engine->setCacheLifetime(60 * ($this->renderVars['options']['cache'] ?? 60));
            $this->engine->setCompileId($this->renderVars['options']['cache_name'] ?? null);
        }

        $output = $this->engine->fetch(
            $view,
            $this->renderVars['options']['cache_id'] ?? null,
            $this->renderVars['options']['cache_name'] ?? ($this->renderVars['options']['compile_id'] ?? null),
            $this->renderVars['options']['parent'] ?? null,
        );

        $this->logPerformance($this->renderVars['start'], microtime(true), $this->renderVars['view']);

        return $output;
    }

    /**
     * Configure le moteur de template
     */
    private function configure(): void
    {
        if (isset($this->config['configure']) && is_callable($this->config['configure'])) {
            $newInstance = $this->config['configure']($this->engine);
            if ($newInstance instanceof Smarty) {
                $this->engine = $newInstance;
            }
        }

        $this->engine->setTemplateDir([
            $this->viewPath,
            'partials' => VIEW_PATH . 'partials',
            'layouts'  => LAYOUT_PATH,
        ]);

        $this->engine->addPluginsDir([
            SYST_PATH . 'Helpers',
            HELPER_PATH,
        ]);

        $config = array_merge([
            'config_dir'    => CONFIG_PATH,
            'cache_dir'     => VIEW_CACHE_PATH . 'smarty' . DIRECTORY_SEPARATOR . 'cache',
            'compile_dir'   => VIEW_CACHE_PATH . 'smarty' . DIRECTORY_SEPARATOR . 'compile',
            'caching'       => Smarty::CACHING_OFF,
            'compile_check' => on_dev(),
        ], $this->config);

        foreach ($config as $key => $value) {
            if (property_exists($this->engine, $key)) {
                $this->engine->{$key} = $value;
            }
        }
    }
}
