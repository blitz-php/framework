<?php

namespace BlitzPHP\View\Adapters;

use Smarty;

class SmartyAdapter extends AbstractAdapter
{
    /**
     * Instance Smarty
     *
     * @var Smarty
     */
    private $engine;
    
    /**
     * {@inheritDoc}
     */
    public function __construct(array $config, string $viewPath = VIEW_PATH)
    {
        parent::__construct($config, $viewPath);

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
            $view .= '.tpl';
        }

		$layout = $this->layout;
		if (!empty($layout)) {
			if (empty(pathinfo($layout, PATHINFO_EXTENSION))) {
				$layout .= '.tpl';
			}
			$view = 'extends:[layouts]'.$layout.'|'.$view;
		}

        $this->renderVars['options'] = $options ?? [];

        $this->engine->assign($this->data);
		
        // Doit-on mettre en cache?
		if (!empty($this->renderVars['options']['cache_name']) OR !empty($this->renderVars['options']['cache'])) {
            $this->enableCache();
            $this->engine->setCacheLifetime(60 * $this->renderVars['options']['cache'] ?? 60);
			$this->engine->setCompileId($this->renderVars['options']['cache_name'] ?? null);
		}

		return $this->engine->fetch(
            $view, 
            $this->renderVars['options']['cache_id'] ?? null,
            $this->renderVars['options']['cache_name'] ?? ($this->renderVars['options']['compile_id'] ?? null),
            $this->renderVars['options']['parent'] ?? null,
        );
	}

    /**
     * {@inheritDoc}
     */
    public function renderString(string $view, ?array $options = null, bool $saveData = false): string
    {
        return $this->render($view, $options, $saveData);
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
            'layouts' => LAYOUT_PATH
        ]);
        
        $this->engine->addPluginsDir([
			SYST_PATH . 'Helpers',
            HELPER_PATH,
		]);

        $config = array_merge([
            'config_dir'    => CONFIG_PATH,
            'cache_dir'     => VIEW_CACHE_PATH.'smarty'.DIRECTORY_SEPARATOR.'cache',
            'compile_dir'   => VIEW_CACHE_PATH.'smarty'.DIRECTORY_SEPARATOR.'compile',
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
