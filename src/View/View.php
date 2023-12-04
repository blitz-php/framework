<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\View;

use BlitzPHP\Container\Services;
use BlitzPHP\Exceptions\ConfigException;
use BlitzPHP\Exceptions\ViewException;
use BlitzPHP\Validation\ErrorBag;
use BlitzPHP\View\Adapters\BladeAdapter;
use BlitzPHP\View\Adapters\LatteAdapter;
use BlitzPHP\View\Adapters\NativeAdapter;
use BlitzPHP\View\Adapters\PlatesAdapter;
use BlitzPHP\View\Adapters\SmartyAdapter;
use BlitzPHP\View\Adapters\TwigAdapter;
use Closure;
use Stringable;

class View implements Stringable
{
    /**
     * Views configuration
     */
    protected array $config = [];

    /**
     * @var RendererInterface
     */
    private $adapter;

    /**
     * Liste des adapters pris en comptes
     */
    public static array $validAdapters = [
        'native' => NativeAdapter::class,
        'blade'  => BladeAdapter::class,
        'latte'  => LatteAdapter::class,
        'plates' => PlatesAdapter::class,
        'smarty' => SmartyAdapter::class,
        'twig'   => TwigAdapter::class,
    ];

    /**
     * Options de la vue
     */
    private array $options = [];

    /**
     * La vue à rendre
     *
     * @var string
     */
    private $view;

    /**
     * Données partagées à toutes les vues
     */
    private static array $shared = [];

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->config = config('view');
        static::share($this->config['shared']);
        $this->setAdapter($this->config['active_adapter'] ?? 'native');
    }

	/**
	 * {@inheritDoc}
	 */
    public function __toString(): string
    {
        return $this->get();
    }

    /**
     * Defini les données partagées entre plusieurs vues
     */
    public static function share(array|Closure|string $key, mixed $value = null): void
    {
        if ($key instanceof Closure) {
            $key = Services::container()->call($key);
        }
        if (is_string($key)) {
            $key = [$key => $value];
        }

        self::$shared = array_merge(self::$shared, $key);
    }

    /**
     * Recupere et retourne le code html de la vue créée
     *
     * @param bool|string $compress
     */
    public function get($compress = 'auto'): string
    {
        $output = $this->adapter->render($this->view, $this->options);
        $output = $this->decorate($output);

        return $this->compressView($output, $compress);
    }

    /**
     * Affiche la vue generee au navigateur
     */
    public function render(): void
    {
        $compress = $this->config['compress_output'] ?? 'auto';

        echo $this->get($compress);
    }

	/**
	 * Verifie qu'un fichier de vue existe
	 */
	public function exist(string $view, ?string $ext = null, array $options = []): bool
	{
		return $this->adapter->exist($view, $ext, $options);
	}

	/**
	 * Utilise le premier fichier de vue trouvé pour le rendu
	 *
	 * @param string[] $views
	 */
	public function first(array $views, array $data = [], array $options = []): self
	{
		foreach ($views as $view) {
			if ($this->exist($view, null, $options)) {
				return $this->make($view, $data, $options);
			}
		}

		throw ViewException::invalidFile(implode(' OR ', $views));
	}

	/**
	 * Crée une instance de vue prêt à être utilisé
	 */
	public function make(string $view, array $data = [], array $options = []): self
	{
		return $this->addData($data)->setOptions($options)->display($view);
	}

    /**
     * Modifier les options d'affichage
     */
    public function setOptions(?array $options = []): self
    {
        $this->options = (array) $options;

        return $this;
    }

    /**
     * Définir la vue à afficher
     */
    public function display(string $view): self
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Définit plusieurs éléments de données de vue à la fois.
     */
    public function addData(array $data = [], ?string $context = null): self
    {
        unset($data['errors']);

        $data = array_merge(self::$shared, $data);

        $this->adapter->addData($data, $context);

        if (! array_key_exists('errors', $this->getData())) {
            $this->setValidationErrors();
        }

        return $this;
    }

    /**
     * Définit plusieurs éléments de données de vue à la fois.
     */
    public function with(array|string $key, mixed $value = null, ?string $context = null): self
    {
        if (is_array($key)) {
            $context = $value;
        } else {
            $key = [$key => $value];
        }

        return $this->addData($key, $context);
    }

    /**
     * Ajoute des erreurs à la session en tant que Flashdata.
     */
    public function withErrors(array|ErrorBag|string $errors): static
    {
        if (is_string($errors)) {
            $errors = ['default' => $errors];
        }
        if (! ($errors instanceof ErrorBag)) {
            $errors = new ErrorBag($errors);
        }

        if (isset(self::$shared['errors']) && self::$shared['errors'] instanceof ErrorBag) {
            $messages = array_merge(
                self::$shared['errors']->toArray(),
                $errors->toArray()
            );
            $errors = new ErrorBag($messages);
        }

        $this->share('errors', $errors);

        return $this;
    }

    /**
     * Définit une seule donnée de vue.
     *
     * @param mixed|null $value
     */
    public function setVar(string $name, $value = null, ?string $context = null): self
    {
        $this->adapter->setVar($name, $value, $context);

        return $this;
    }

    /**
     * Remplacer toutes les données de vue par de nouvelles données
     */
    public function setData(array $data, ?string $context = null): self
    {
        unset($data['errors']);

        $data = array_merge(self::$shared, $data);

        $this->adapter->setData($data, $context);

        if (! array_key_exists('errors', $this->getData())) {
            $this->setValidationErrors();
        }

        return $this;
    }

    /**
     * Remplacer toutes les données de vue par de nouvelles données
     */
    public function getData(): array
    {
        return $this->adapter->getData();
    }

    /**
     * Supprime toutes les données de vue du système.
     */
    public function resetData(): self
    {
        $this->adapter->resetData();

        return $this;
    }

    /**
     * Definit le layout a utiliser par les vues
     */
    public function setLayout(string $layout): self
    {
        $this->adapter->setLayout($layout);

        return $this;
    }

    /**
     * Defini l'adapteur à utiliser
     */
    public function setAdapter(string $adapter, array $config = []): self
    {
        if (! array_key_exists($adapter, self::$validAdapters)) {
            $adapter = 'native';
        }
        if (empty($this->config['adapters']) || ! is_array($this->config['adapters'])) {
            $this->config['adapters'] = [];
        }

        $config = array_merge($this->config['adapters'][$adapter] ?? [], $config);
        if (empty($config)) {
            throw ConfigException::viewAdapterConfigNotFound($adapter);
        }

        $debug = $this->config['debug'] ?? 'auto';
        if ($debug === 'auto') {
            $debug = on_dev();
        }

        $this->adapter = new self::$validAdapters[$adapter](
            $config,
            $config['view_path_locator'] ?? Services::locator(),
            $debug
        );

        return $this;
    }

    /**
     * Renvoie les données de performances qui ont pu être collectées
     * lors de l'exécution. Utilisé principalement dans la barre d'outils de débogage.
     */
    public function getPerformanceData(): array
    {
        return $this->adapter->getPerformanceData();
    }

    /**
     * Compresse le code html d'une vue
     */
    protected function compressView(string $output, bool|callable|string $compress = 'auto'): string
    {
        $compress = $compress === 'auto' ? ($this->options['compress_output'] ?? 'auto') : $compress;
        $compress = $compress === 'auto' ? ($this->config['compress_output'] ?? 'auto') : $compress;

        if (is_callable($compress)) {
            $compress = Services::container()->call($compress);
        }

        if ($compress === 'auto') {
            $compress = is_online();
        }

        return true === $compress ? trim(preg_replace('/\s+/', ' ', $output)) : $output;
    }

    /**
     * Exécute la sortie générée via tous les décorateurs de vue déclarés.
     */
    protected function decorate(string $output): string
    {
        foreach ($this->config['decorators'] as $decorator) {
            if (! is_subclass_of($decorator, ViewDecoratorInterface::class)) {
                throw ViewException::invalidDecorator($decorator);
            }

            $output = $decorator::decorate($output);
        }

        return $output;
    }

    /**
     * Defini les erreurs de validation pour la vue
     */
    private function setValidationErrors()
    {
        $errors = [];

        /** @var \BlitzPHP\Session\Store $session */
        $session = session();

        if (null !== $e = $session->getFlashdata('errors')) {
            if (is_array($e)) {
                $errors = array_merge($errors, $e);
            } else {
                $errors['error'] = $e;
            }

            $session->unmarkFlashdata('errors');
        }

        if (null !== $e = $session->getFlashdata('error')) {
            if (is_array($e)) {
                $errors = array_merge($errors, $e);
            } else {
                $errors['error'] = $e;
            }

            $session->unmarkFlashdata('error');
        }

        $this->adapter->addData(['errors' => new ErrorBag($errors)]);
    }
}
