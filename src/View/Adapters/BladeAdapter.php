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

use Jenssegers\Blade\Blade;

class BladeAdapter extends AbstractAdapter
{
	/**
	 * {@inheritDoc}
	 */
	protected string $ext = 'blade.php';

    /**
     * Instance Blade
     *
     * @var Blade
     */
    private $engine;

    /**
     * {@inheritDoc}
     */
    public function __construct(protected array $config, $viewPathLocator = null, protected bool $debug = BLITZ_DEBUG)
    {
        parent::__construct($config, $viewPathLocator, $debug);

        $this->engine = new Blade(
            $this->viewPath ?: VIEW_PATH,
            $this->config['cache_path'] ?? VIEW_CACHE_PATH . 'blade' . DIRECTORY_SEPARATOR . 'cache'
        );

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
        $directives = (array) ($this->config['directives'] ?? []);

        foreach ($directives as $name => $callable) {
            if (is_callable($callable)) {
                $this->engine->directive($name, $callable);
            }
        }

        $if = (array) ($this->config['if'] ?? []);

        foreach ($if as $name => $callable) {
            if (is_callable($callable)) {
                $this->engine->if($name, $callable);
            }
        }
    }
}
