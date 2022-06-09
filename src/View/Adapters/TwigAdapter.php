<?php

namespace BlitzPHP\View\Adapters;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigAdapter extends AbstractAdapter
{
    /**
     * Instance Twig
     *
     * @var Environment
     */
    private $engine;
    
    /**
     * {@inheritDoc}
     */
    public function __construct(array $config, string $viewPath = VIEW_PATH)
    {
        parent::__construct($config, $viewPath);
   
        $loader = new FilesystemLoader([
            $this->viewPath,
            VIEW_PATH . 'partials',
            LAYOUT_PATH
        ]);
        $this->engine = new Environment($loader);

        $this->configure();
    }

    /**
	 * Rend une vue en associant le layout si celui ci est defini
	 *
	 * @param string|null $template
	 * @param mixed $cache_id
	 * @param mixed $compile_id
	 * @param mixed $parent
	 * @return string
	 */
	public function render(string $view, ?array $options = null, ?bool $saveData = null): string
	{
        if (empty(pathinfo($view, PATHINFO_EXTENSION))) {
            $view .= '.twig';
        }

        $this->renderVars['options'] = $options ?? [];

		return $this->engine->render($view, $this->data);
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
        }
        else {
            $this->engine->disableAutoReload();
        }
        
        $debug = $this->config['debug'] ?: 'auto';
        if ('auto' === $debug) {
            $debug = on_dev();
        }
        if (true === $debug) {
            $this->engine->enableDebug();
        }
        else {
            $this->engine->disableDebug();
        }

        $strictVariables = $this->config['strict_variables'] ?: 'auto';
        if ('auto' === $strictVariables) {
            $strictVariables = on_dev();
        }
        if (true === $strictVariables) {
            $this->engine->enableStrictVariables();
        }
        else {
            $this->engine->disableStrictVariables();
        }

        $this->engine->setCharset($this->config['charset'] ?: config('app.charset'));

        $this->engine->setCache($this->config['cache_dir'] ?: VIEW_CACHE_PATH.'twig');

        // Ajout des variables globals
        $globals = (array) ($this->config['globals'] ?? []);
		foreach ($globals as $name => $global) {
			$this->engine->addGlobal($name, $global);
		}

		// Ajout des filtres
        $filters = (array) ($this->config['filters'] ?? []);
		foreach ($filters as  $filter) {
            if ($filter instanceof TwigFilter) {
                $this->engine->addFilter($filter);
            }
		}

        // Ajout des fonctions
        $functions = (array) ($this->config['functions'] ?? []);
		foreach ($functions as  $function) {
            if ($function instanceof TwigFunction) {
                $this->engine->addFunction($function);
            }
		}
    }
}
