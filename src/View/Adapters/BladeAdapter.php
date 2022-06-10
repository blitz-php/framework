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
     * Instance Blade
     *
     * @var Blade
     */
    private $engine;

    /**
     * {@inheritDoc}
     */
    public function __construct(array $config, string $viewPath = VIEW_PATH)
    {
        parent::__construct($config, $viewPath);

        $this->engine = new Blade(
            $this->viewPath,
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

        $this->renderVars['file'] = str_replace('/', DS, rtrim($this->viewPath, '/\\') . DS . ltrim($this->renderVars['view'], '/\\'));

        return $this->engine->render($this->renderVars['view'], $this->data);
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
