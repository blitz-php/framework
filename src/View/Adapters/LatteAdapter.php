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

use Latte\Engine;
use Latte\Loaders\FileLoader;

class LatteAdapter extends AbstractAdapter
{
    /**
     * Instance Latte
     *
     * @var \Latte\Engine
     */
    private $latte;

    /**
     * {@inheritDoc}
     */
    public function __construct(array $config, string $viewPath = VIEW_PATH)
    {
        parent::__construct($config, $viewPath);

        $this->latte = new Engine();

        $this->configure();
    }

    /**
     * {@inheritDoc}
     */
    public function render(string $view, ?array $options = null, ?bool $saveData = null): string
    {
        $view = str_replace([$this->viewPath, ' '], '', $view);
        if (empty(pathinfo($view, PATHINFO_EXTENSION))) {
            $view .= '.' . str_replace('.', '', $this->config['extension'] ?? 'latte');
        }

        $this->renderVars['start'] = microtime(true);

        $this->renderVars['view']    = $view;
        $this->renderVars['options'] = $options ?? [];

        $this->renderVars['file'] = str_replace('/', DS, rtrim($this->viewPath, '/\\') . DS . ltrim($this->renderVars['view'], '/\\'));

        $output = $this->latte->renderToString($this->renderVars['view'], $this->data);

        $this->logPerformance($this->renderVars['start'], microtime(true), $this->renderVars['view']);

        return $output;
    }

    /**
     * Configure le moteur de template
     */
    private function configure()
    {
        if (isset($this->config['configure']) && is_callable($this->config['configure'])) {
            $newInstance = $this->config['configure']($this->latte);
            if ($newInstance instanceof Engine) {
                $this->latte = $newInstance;
            }
        }

        $auto_refresh = $this->config['auto_refresh'] ?? 'auto';
        if ($auto_refresh === 'auto') {
            $auto_refresh = ! is_online();
        }
        $this->latte->setAutoRefresh($auto_refresh);

        $this->latte->setTempDirectory($this->config['temp_path'] ?? TEMP_PATH . 'views');
        $this->latte->setLoader(new FileLoader(rtrim($this->viewPath, '/\\')));
    }
}
