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
     * {@inheritDoc}
     */
    protected string $ext = 'latte';

    /**
     * Instance Latte
     *
     * @var Engine
     */
    private $latte;

    /**
     * {@inheritDoc}
     */
    public function __construct(protected array $config, $viewPathLocator = null, protected bool $debug = BLITZ_DEBUG)
    {
        parent::__construct($config, $viewPathLocator, $debug);

        $this->latte = new Engine();

        $this->configure();
    }

    /**
     * {@inheritDoc}
     */
    public function render(string $view, ?array $options = null, ?bool $saveData = null): string
    {
        $view     = str_replace([$this->viewPath, ' '], '', $view);
        $pathinfo = pathinfo($view, PATHINFO_EXTENSION);
        if ($pathinfo === '' || $pathinfo === '0' || $pathinfo === []) {
            $view .= '.' . $this->ext;
        }

        $this->renderVars['start'] = microtime(true);

        $this->renderVars['view']    = $view;
        $this->renderVars['options'] = $options ?? [];

        $this->renderVars['file'] = $this->getRenderedFile($options, $this->renderVars['view'], $this->ext);

        $output = $this->latte->renderToString($this->renderVars['view'], $this->data);

        $this->logPerformance($this->renderVars['start'], microtime(true), $this->renderVars['view']);

        return $this->decorate($output);
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
