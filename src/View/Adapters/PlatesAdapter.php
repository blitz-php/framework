<?php

namespace BlitzPHP\View\Adapters;

use League\Plates\Engine;
use League\Plates\Extension\Asset;

class PlatesAdapter extends AbstractAdapter
{
    /**
     * Instance Plate
     *
     * @var Engine
     */
    private $engine;

    /**
     * Extension de fichier à utiliser
     *
     * @var string
     */
    private $extension;
    
    /**
     * {@inheritDoc}
     */
    public function __construct(array $config, string $viewPath = VIEW_PATH)
    {
        parent::__construct($config, $viewPath);

        $this->extension = str_replace('.', '', $this->config['extension'] ?? 'tpl');
        $this->engine = new Engine(rtrim($this->viewPath, '/\\'), $this->extension);

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
