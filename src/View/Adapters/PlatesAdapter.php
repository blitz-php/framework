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

use League\Plates\Engine;
use League\Plates\Extension\Asset;

class PlatesAdapter extends AbstractAdapter
{
	/**
	 * {@inheritDoc}
	 */
	protected string $ext = 'tpl';

    /**
     * Instance Plate
     *
     * @var Engine
     */
    private $engine;

    /**
     * {@inheritDoc}
     */
    public function __construct(protected array $config, $viewPathLocator = null, protected bool $debug = BLITZ_DEBUG)
    {
        parent::__construct($config, $viewPathLocator, $debug);

        $this->engine = new Engine(rtrim($this->viewPath, '/\\'), $this->ext);

        $this->configure();
    }

    /**
     * {@inheritDoc}
     */
    public function render(string $view, ?array $options = null, ?bool $saveData = null): string
    {
        $view = str_replace([$this->viewPath, ' '], '', $view);

        $this->renderVars['start'] = microtime(true);

        $this->renderVars['view']    = $view;
        $this->renderVars['options'] = $options ?? [];

        $this->renderVars['file'] = $this->getRenderedFile($options, $this->renderVars['view'], $this->ext);

        $output = $this->engine->render($this->renderVars['view'], $this->data);

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
            if ($newInstance instanceof Engine) {
                $this->engine = $newInstance;
            }
        }

        $this->engine->addFolder('partials', VIEW_PATH . 'partials', true);
        $this->engine->addFolder('layouts', LAYOUT_PATH, true);

        $this->engine->loadExtension(new Asset(WEBROOT));

        $functions = (array) ($this->config['functions'] ?? []);

        foreach ($functions as $name => $callable) {
            if (is_callable($callable)) {
                $this->engine->registerFunction($name, $callable);
            }
        }
    }
}
