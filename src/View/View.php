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

use BlitzPHP\Session\Store;
use BlitzPHP\Container\Services;
use BlitzPHP\Contracts\View\RendererInterface;
use BlitzPHP\Exceptions\ConfigException;
use BlitzPHP\Exceptions\ViewException;
use BlitzPHP\Validation\ErrorBag;
use BlitzPHP\View\Adapters\AbstractAdapter;
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
     *
     * @var array<string, class-string<AbstractAdapter>>
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
        if ($value instanceof Closure) {
            $value = Services::container()->call($value);
        }
        if (is_string($key)) {
            $key = [$key => $value];
        }

        self::$shared = array_merge(self::$shared, $key);
    }

    /**
     * Recupere et retourne le code html de la vue créée
     */
    public function get(bool|string $compress = 'auto', ?bool $saveData = null): string
    {
        $saveData ??= ($this->options['save_data'] ?? null);

        $output = $this->adapter->render($this->view, $this->options, $saveData);

        return $this->compressView($output, $compress);
    }

    /**
     * Affiche la vue generee au navigateur
     */
    public function render(?bool $saveData = null): void
    {
        $compress = $this->config['compress_output'] ?? 'auto';

        echo $this->get($compress, $saveData);
    }

    /**
     * Construit la sortie en fonction d'une chaîne et de tout données déjà définies.
     */
    public function renderString(string $view, ?array $options = null, bool $saveData = false): string
    {
        return $this->adapter->renderString($view, $options, $saveData);
    }

    /**
     * Verifie qu'un fichier de vue existe
     */
    public function exists(string $view, ?string $ext = null, array $options = []): bool
    {
        return $this->adapter->exists($view, $ext, $options);
    }

    /**
     * Utilise le premier fichier de vue trouvé pour le rendu
     *
     * @param string[] $views
     */
    public function first(array $views, array $data = [], array $options = []): static
    {
        foreach ($views as $view) {
            if ($this->exists($view, null, $options)) {
                return $this->make($view, $data, $options);
            }
        }

        throw ViewException::invalidFile(implode(' OR ', $views));
    }

    /**
     * Crée une instance de vue prêt à être utilisé
     */
    public function make(string $view, array $data = [], array $options = []): static
    {
        return $this->addData($data)->options($options)->display($view);
    }

    /**
     * Modifier les options d'affichage
     *
     * @deprecated since 1.0 use options() instead
     */
    public function setOptions(?array $options = []): static
    {
        return $this->options($options ?: []);
    }

    /**
     * Modifier les options d'affichage
     *
     * {@internal}
     */
    public function options(array $options = []): static
    {
        if (isset($options['layout'])) {
            $this->layout($options['layout']);
            unset($options['layout']);
        }

        $this->options = $options;

        return $this;
    }

    /**
     * Définir la vue à afficher
     *
     * {@internal}
     */
    public function display(string $view, ?array $options = null): static
    {
        $this->view = $view;

        if ($options !== null) {
            $this->options($options);
        }

        return $this;
    }

    /**
     * Définit plusieurs éléments de données de vue à la fois.
     *
     * {@internal}
     */
    public function addData(array $data = [], ?string $context = null): static
    {
        unset($data['errors']);

        $data = array_merge(self::$shared, $data);

        if (! on_test() && ! isset($data['errors'])) {
            $data['errors'] = $this->setValidationErrors();
        }

        $this->adapter->addData($data, $context);

        return $this;
    }

    /**
     * Définit plusieurs éléments de données de vue à la fois.
     */
    public function with(array|string $key, mixed $value = null, ?string $context = null): static
    {
        if (is_array($key)) {
            $context = $value;
        } else {
            $key = [$key => $value];
        }

        if (isset($key['errors'])) {
            return $this->withErrors($key['errors']);
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

        $this->adapter->setVar('errors', $errors);

        return $this;
    }

    /**
     * Définit une seule donnée de vue.
     *
     * @param mixed|null $value
     */
    public function setVar(string $name, $value = null, ?string $context = null): static
    {
        $this->adapter->setVar($name, $value, $context);

        return $this;
    }

    /**
     * Remplacer toutes les données de vue par de nouvelles données
     */
    public function setData(array $data, ?string $context = null): static
    {
        unset($data['errors']);

        $data = array_merge(self::$shared, $data);

        if (! on_test() && ! isset($data['errors'])) {
            $data['errors'] = $this->setValidationErrors();
        }

        $this->adapter->setData($data, $context);

        return $this;
    }

    /**
     * Renvoie les données actuelles qui seront affichées dans la vue.
     */
    public function getData(): array
    {
        $data = $this->adapter->getData();

        if (on_test() && isset($data['errors'])) {
            unset($data['errors']);
        }

        return $data;
    }

    /**
     * Supprime toutes les données de vue du système.
     */
    public function resetData(): static
    {
        $this->adapter->resetData();

        return $this;
    }

    /**
     * Definit le layout a utiliser par les vues
     */
    public function layout(string $layout): static
    {
        $this->adapter->setLayout($layout);

        return $this;
    }

    /**
     * @deprecated 1.0 Please use layout method instead
     */
    public function setLayout(string $layout): static
    {
        return $this->layout($layout);
    }

    /**
     * Defini l'adapteur à utiliser
     *
     * {@internal}
     */
    public function setAdapter(string $adapter, array $config = []): static
    {
        if (! array_key_exists($adapter, self::$validAdapters)) {
            $adapter = 'native';
        }
        if (empty($this->config['adapters']) || ! is_array($this->config['adapters'])) {
            $this->config['adapters'] = [];
        }

        $config = array_merge($this->config['adapters'][$adapter] ?? [], $config);
        if ($config === []) {
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
     * Recupere l'adapter utilisé pour générer les vues
     */
    public function getAdapter(): RendererInterface
    {
        return $this->adapter;
    }

    /**
     * Renvoie les données de performances qui ont pu être collectées lors de l'exécution.
     * Utilisé principalement dans la barre d'outils de débogage.
     *
     * {@internal}
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
     * Defini les erreurs de validation pour la vue
     */
    private function setValidationErrors(): ErrorBag
    {
        $errors = [];

        /** @var Store $session */
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

        return new ErrorBag($errors);
    }
}
