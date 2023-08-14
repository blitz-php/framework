<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Http;

use BadMethodCallException;
use BlitzPHP\Container\Services;
use BlitzPHP\Exceptions\FrameworkException;
use BlitzPHP\Exceptions\HttpException;
use BlitzPHP\Filesystem\Files\UploadedFile;
use BlitzPHP\Session\Cookie\CookieCollection;
use BlitzPHP\Session\Store;
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\Utilities\Iterable\Arr;
use GuzzleHttp\Psr7\ServerRequest as Psr7ServerRequest;
use GuzzleHttp\Psr7\Stream;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 * Une classe qui aide à envelopper les informations de la requête et les détails d'une seule requête.
 * Fournit des méthodes couramment utilisées pour effectuer une introspection sur les en-têtes et le corps de la requête.
 */
class ServerRequest implements ServerRequestInterface
{
    /**
     * Tableau de paramètres analysés à partir de l'URL.
     *
     * @var array
     */
    protected $params = [
        'plugin'     => null,
        'controller' => null,
        'action'     => null,
        '_ext'       => null,
        'pass'       => [],
    ];

    /**
     * Tableau de données POST. Contiendra des données de formulaire ainsi que des fichiers téléchargés.
     * Dans les requêtes PUT/PATCH/DELETE, cette propriété contiendra les données encodées du formulaire.
     *
     * @var array|object|null
     */
    protected $data = [];

    /**
     * Tableau d'arguments de chaîne de requête
     *
     * @var array
     */
    protected $query = [];

    /**
     * Tableau de données de cookie.
     *
     * @var array
     */
    protected $cookies = [];

    /**
     * Tableau de données d'environnement.
     *
     * @var array
     */
    protected $_environment = [];

    /**
     * Chemin de l'URL de base.
     *
     * @var string
     */
    protected $base;

    /**
     * segment de chemin webroot pour la demande.
     *
     * @var string
     */
    protected $webroot = '/';

    /**
     * S'il faut faire confiance aux en-têtes HTTP_X définis par la plupart des équilibreurs de charge.
     * Défini sur vrai uniquement si votre application s'exécute derrière des équilibreurs de charge/proxies que vous contrôlez.
     *
     * @var bool
     */
    public $trustProxy = false;

    /**
     * Liste des proxys de confiance
     *
     * @var array<string>
     */
    protected $trustedProxies = [];

    /**
     * Les détecteurs intégrés utilisés avec `is()` peuvent être modifiés avec `addDetector()`.
     *
     * Il existe plusieurs façons de spécifier un détecteur, voir `addDetector()` pour
     * les différents formats et façons de définir des détecteurs.
     *
     * @var array<array|callable>
     */
    protected static $_detectors = [
        'get'     => ['env' => 'REQUEST_METHOD', 'value' => 'GET'],
        'post'    => ['env' => 'REQUEST_METHOD', 'value' => 'POST'],
        'put'     => ['env' => 'REQUEST_METHOD', 'value' => 'PUT'],
        'patch'   => ['env' => 'REQUEST_METHOD', 'value' => 'PATCH'],
        'delete'  => ['env' => 'REQUEST_METHOD', 'value' => 'DELETE'],
        'head'    => ['env' => 'REQUEST_METHOD', 'value' => 'HEAD'],
        'options' => ['env' => 'REQUEST_METHOD', 'value' => 'OPTIONS'],
        'ssl'     => ['env' => 'HTTPS', 'options' => [1, 'on']],
        'ajax'    => ['env' => 'HTTP_X_REQUESTED_WITH', 'value' => 'XMLHttpRequest'],
        'json'    => ['accept' => ['application/json'], 'param' => '_ext', 'value' => 'json'],
        'xml'     => ['accept' => ['application/xml', 'text/xml'], 'param' => '_ext', 'value' => 'xml'],
    ];

    /**
     * Cache d'instance pour les résultats des appels is(something)
     *
     * @var array
     */
    protected $_detectorCache = [];

    /**
     * Flux du corps de la requête. Contient php://input sauf si l'option constructeur `input` est utilisée.
     *
     * @var \Psr\Http\Message\StreamInterface
     */
    protected $stream;

    /**
     * instance Uri
     *
     * @var \Psr\Http\Message\UriInterface
     */
    protected $uri;

    /**
     * Instance d'un objet Session relative à cette requête
     *
     * @var Store
     */
    protected $session;

    /**
     * Stockez les attributs supplémentaires attachés à la requête.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Une liste de propriétés émulées par les méthodes d'attribut PSR7.
     *
     * @var array<string>
     */
    protected $emulatedAttributes = ['session', 'flash', 'webroot', 'base', 'params', 'here'];

    /**
     * Tableau de fichiers.
     *
     * @var UploadedFile[]
     */
    protected $uploadedFiles = [];

    /**
     * La version du protocole HTTP utilisée.
     *
     * @var string|null
     */
    protected $protocol;

    /**
     * La cible de la requête si elle est remplacée
     *
     * @var string|null
     */
    protected $requestTarget;

    /**
     * Negotiator
     *
     * @var Negotiator
     */
    protected $negotiator;

    /**
     * Créer un nouvel objet de requête.
     *
     * Vous pouvez fournir les données sous forme de tableau ou de chaîne. Si tu utilises
     * une chaîne, vous ne pouvez fournir que l'URL de la demande. L'utilisation d'un tableau
     * vous permettent de fournir les clés suivantes :
     *
     * - `post` Données POST ou données de chaîne sans requête
     * - `query` Données supplémentaires de la chaîne de requête.
     * - `files` Fichiers téléchargés dans une structure normalisée, avec chaque feuille une instance de UploadedFileInterface.
     * - `cookies` Cookies pour cette demande.
     * - `environment` $_SERVER et $_ENV données.
     * - `url` L'URL sans le chemin de base de la requête.
     * - `uri` L'objet PSR7 UriInterface. Si nul, un sera créé à partir de `url` ou `environment`.
     * - `base` L'URL de base de la requête.
     * - `webroot` Le répertoire webroot pour la requête.
     * - `input` Les données qui proviendraient de php://input ceci est utile pour simuler
     * requêtes avec mise, patch ou suppression de données.
     * - `session` Une instance d'un objet Session
     *
     * @param array<string, mixed> $config Un tableau de données de requête avec lequel créer une requête.
     */
    public function __construct(array $config = [])
    {
        $config += [
            'params'      => $this->params,
            'query'       => $_GET,
            'post'        => $_POST,
            'files'       => $_FILES,
            'cookies'     => $_COOKIE,
            'environment' => [],
            'url'         => '',
            'uri'         => null,
            'base'        => '',
            'webroot'     => '',
            'input'       => null,
        ];

        $this->_setConfig($config);
    }

    /**
     * Traitez les données de configuration/paramètres dans les propriétés.
     *
     * @param array<string, mixed> $config
     */
    protected function _setConfig(array $config): void
    {
        if (empty($config['session'])) {
            $config['session'] = Services::session(false);
        }

        if (empty($config['environment']['REQUEST_METHOD'])) {
            $config['environment']['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        }

        $this->cookies = $config['cookies'];

        if (isset($config['uri'])) {
            if (! $config['uri'] instanceof UriInterface) {
                throw new FrameworkException('The `uri` key must be an instance of ' . UriInterface::class);
            }
            $uri = $config['uri'];
        } else {
            if ($config['url'] !== '') {
                $config = $this->processUrlOption($config);
            }
            $uri = Psr7ServerRequest::getUriFromGlobals();
        }

        $this->_environment = $config['environment'];

        $this->uri     = $uri;
        $this->base    = $config['base'];
        $this->webroot = $config['webroot'];

        if (isset($config['input'])) {
            $stream = new Stream(\GuzzleHttp\Psr7\Utils::tryFopen('php://memory', 'rw'));
            $stream->write($config['input']);
            $stream->rewind();
        } else {
            $stream = new Stream(\GuzzleHttp\Psr7\Utils::tryFopen('php://input', 'r'));
        }
        $this->stream = $stream;

        $config['post'] = $this->_processPost($config['post']);
        $this->data     = $this->_processFiles($config['post'], $config['files']);
        $this->query    = $config['query'];
        $this->params   = $config['params'];
        $this->session  = $config['session'];
    }

    /**
     * Définissez les variables d'environnement en fonction de l'option `url` pour faciliter la génération d'instance UriInterface.
     *
     * L'option `query` est également mise à jour en fonction de la chaîne de requête de l'URL.
     */
    protected function processUrlOption(array $config): array
    {
        if ($config['url'][0] !== '/') {
            $config['url'] = '/' . $config['url'];
        }

        if (strpos($config['url'], '?') !== false) {
            [$config['url'], $config['environment']['QUERY_STRING']] = explode('?', $config['url']);

            parse_str($config['environment']['QUERY_STRING'], $queryArgs);
            $config['query'] += $queryArgs;
        }

        $config['environment']['REQUEST_URI'] = $config['url'];

        return $config;
    }

    /**
     * Obtenez le type de contenu utilisé dans cette requête.
     */
    public function contentType(): ?string
    {
        $type = $this->getEnv('CONTENT_TYPE');
        if ($type) {
            return $type;
        }

        return $this->getEnv('HTTP_CONTENT_TYPE');
    }

    /**
     * Renvoie l'instance de l'objet Session pour cette requête
     */
    public function session(): Store
    {
        return $this->session;
    }

    /**
     * Obtenez l'adresse IP que le client utilise ou dit qu'il utilise.
     */
    public function clientIp(): string
    {
        if ($this->trustProxy && $this->getEnv('HTTP_X_FORWARDED_FOR')) {
            $addresses = array_map('trim', explode(',', (string) $this->getEnv('HTTP_X_FORWARDED_FOR')));
            $trusted   = (count($this->trustedProxies) > 0);
            $n         = count($addresses);

            if ($trusted) {
                $trusted = array_diff($addresses, $this->trustedProxies);
                $trusted = (count($trusted) === 1);
            }

            if ($trusted) {
                return $addresses[0];
            }

            return $addresses[$n - 1];
        }

        if ($this->trustProxy && $this->getEnv('HTTP_X_REAL_IP')) {
            $ipaddr = $this->getEnv('HTTP_X_REAL_IP');
        } elseif ($this->trustProxy && $this->getEnv('HTTP_CLIENT_IP')) {
            $ipaddr = $this->getEnv('HTTP_CLIENT_IP');
        } else {
            $ipaddr = $this->getEnv('REMOTE_ADDR');
        }

        return trim((string) $ipaddr);
    }

    /**
     * Enregistrer des proxys de confiance
     *
     * @param string[] $proxies ips liste des proxys de confiance
     */
    public function setTrustedProxies(array $proxies): void
    {
        $this->trustedProxies = $proxies;
        $this->trustProxy     = true;
    }

    /**
     * Obtenez les proxys de confiance
     */
    public function getTrustedProxies(): array
    {
        return $this->trustedProxies;
    }

    /**
     * Renvoie le référent qui a référé cette requête.
     *
     * @param bool $local Tentative de renvoi d'une adresse locale.
     *                    Les adresses locales ne contiennent pas de noms d'hôtes..
     */
    public function referer(bool $local = true): ?string
    {
        $ref = $this->getEnv('HTTP_REFERER');

        $base = /* Configure::read('App.fullBaseUrl') . */ $this->webroot;
        if (! empty($ref) && ! empty($base)) {
            if ($local && strpos($ref, $base) === 0) {
                $ref = substr($ref, strlen($base));
                if ($ref === '' || strpos($ref, '//') === 0) {
                    $ref = '/';
                }
                if ($ref[0] !== '/') {
                    $ref = '/' . $ref;
                }

                return $ref;
            }
            if (! $local) {
                return $ref;
            }
        }

        return null;
    }

    /**
     * Gestionnaire de méthodes manquant, les poignées enveloppent les anciennes méthodes de type isAjax()
     *
     * @return bool
     *
     * @throws BadMethodCallException lorsqu'une méthode invalide est appelée.
     */
    public function __call(string $name, array $params)
    {
        if (strpos($name, 'is') === 0) {
            $type = strtolower(substr($name, 2));

            array_unshift($params, $type);

            return $this->is(...$params);
        }

        throw new BadMethodCallException(sprintf('Method "%s()" does not exist', $name));
    }

    /**
     * Vérifiez si une demande est d'un certain type.
     *
     * Utilise les règles de détection intégrées ainsi que des règles supplémentaires
     * défini avec {@link \BlitzPHP\Http\ServerRequest::addDetector()}. Tout détecteur peut être appelé
     * comme `is($type)` ou `is$Type()`.
     *
     * @param string|string[] $type Le type de requête que vous souhaitez vérifier. S'il s'agit d'un tableau, cette méthode renverra true si la requête correspond à n'importe quel type.
     *
     * @return bool Si la demande est du type que vous vérifiez.
     */
    public function is($type, ...$args): bool
    {
        if (is_array($type)) {
            foreach ($type as $_type) {
                if ($this->is($_type)) {
                    return true;
                }
            }

            return false;
        }

        $type = strtolower($type);
        if (! isset(static::$_detectors[$type])) {
            return false;
        }
        if ($args) {
            return $this->_is($type, $args);
        }

        return $this->_detectorCache[$type] = $this->_detectorCache[$type] ?? $this->_is($type, $args);
    }

    /**
     * Efface le cache du détecteur d'instance, utilisé par la fonction is()
     */
    public function clearDetectorCache(): void
    {
        $this->_detectorCache = [];
    }

    /**
     * Worker pour la fonction publique is()
     *
     * @param string $type Le type de requête que vous souhaitez vérifier.
     * @param array  $args Tableau d'arguments de détecteur personnalisés.
     *
     * @return bool Si la demande est du type que vous vérifiez.
     */
    protected function _is(string $type, array $args): bool
    {
        $detect = static::$_detectors[$type];
        if (is_callable($detect)) {
            array_unshift($args, $this);

            return $detect(...$args);
        }
        if (isset($detect['env']) && $this->_environmentDetector($detect)) {
            return true;
        }
        if (isset($detect['header']) && $this->_headerDetector($detect)) {
            return true;
        }
        if (isset($detect['accept']) && $this->_acceptHeaderDetector($detect)) {
            return true;
        }

        return (bool) (isset($detect['param']) && $this->_paramDetector($detect));
    }

    /**
     * Détecte si un en-tête d'acceptation spécifique est présent.
     *
     * @param array $detect Tableau d'options du détecteur.
     *
     * @return bool Si la demande est du type que vous vérifiez.
     */
    protected function _acceptHeaderDetector(array $detect): bool
    {
        $acceptHeaders = explode(',', (string) $this->getEnv('HTTP_ACCEPT'));

        foreach ($detect['accept'] as $header) {
            if (in_array($header, $acceptHeaders, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Détecte si un en-tête spécifique est présent.
     *
     * @param array $detect Tableau d'options du détecteur.
     *
     * @return bool Si la demande est du type que vous vérifiez.
     */
    protected function _headerDetector(array $detect): bool
    {
        foreach ($detect['header'] as $header => $value) {
            $header = $this->getEnv('http_' . $header);
            if ($header !== null) {
                if (! is_string($value) && ! is_bool($value) && is_callable($value)) {
                    return $value($header);
                }

                return $header === $value;
            }
        }

        return false;
    }

    /**
     * Détecte si un paramètre de requête spécifique est présent.
     *
     * @param array $detect Tableau d'options du détecteur.
     *
     * @return bool Si la demande est du type que vous vérifiez.
     */
    protected function _paramDetector(array $detect): bool
    {
        $key = $detect['param'];
        if (isset($detect['value'])) {
            $value = $detect['value'];

            return isset($this->params[$key]) ? $this->params[$key] === $value : false;
        }
        if (isset($detect['options'])) {
            return isset($this->params[$key]) ? in_array($this->params[$key], $detect['options'], true) : false;
        }

        return false;
    }

    /**
     * Détecte si une variable d'environnement spécifique est présente.
     *
     * @param array $detect Tableau d'options du détecteur.
     *
     * @return bool Si la demande est du type que vous vérifiez.
     */
    protected function _environmentDetector(array $detect): bool
    {
        if (isset($detect['env'])) {
            if (isset($detect['value'])) {
                return $this->getEnv($detect['env']) === $detect['value'];
            }
            if (isset($detect['pattern'])) {
                return (bool) preg_match($detect['pattern'], (string) $this->getEnv($detect['env']));
            }
            if (isset($detect['options'])) {
                $pattern = '/' . implode('|', $detect['options']) . '/i';

                return (bool) preg_match($pattern, (string) $this->getEnv($detect['env']));
            }
        }

        return false;
    }

    /**
     * Vérifier qu'une requête correspond à tous les types donnés.
     *
     * Vous permet de tester plusieurs types et d'unir les résultats.
     * Voir Request::is() pour savoir comment ajouter des types supplémentaires et le
     * types intégrés.
     *
     * @param string[] $types Les types à vérifier.
     *
     * @see \BlitzPHP\Http\ServerRequest::is()
     */
    public function isAll(array $types): bool
    {
        foreach ($types as $type) {
            if (! $this->is($type)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ajouter un nouveau détecteur à la liste des détecteurs qu'une requête peut utiliser.
     * Il existe plusieurs types de détecteurs différents qui peuvent être réglés.
     *
     * ### Comparaison des rappels
     *
     * Les détecteurs de rappel vous permettent de fournir un callable pour gérer le chèque.
     * Le rappel recevra l'objet de requête comme seul paramètre.
     *
     * ```
     * addDetector('custom', function ($request) { //Renvoyer un booléen });
     * ```
     *
     * ### Comparaison des valeurs d'environnement
     *
     * Une comparaison de valeur d'environnement, compare une valeur extraite de `env()` à une valeur connue
     * la valeur d'environnement est l'égalité vérifiée par rapport à la valeur fournie.
     *
     * ```
     * addDetector('post', ['env' => 'REQUEST_METHOD', 'value' => 'POST']);
     * ```
     *
     * ### Comparaison des paramètres de demande
     *
     * Permet des détecteurs personnalisés sur les paramètres de demande.
     *
     * ```
     * addDetector('admin', ['param' => 'prefix', 'value' => 'admin']);
     * ```
     *
     * ### Accepter la comparaison
     *
     * Permet au détecteur de comparer avec la valeur d'en-tête Accepter.
     *
     * ```
     * addDetector('csv', ['accept' => 'text/csv']);
     * ```
     *
     * ### Comparaison d'en-tête
     *
     * Permet de comparer un ou plusieurs en-têtes.
     *
     * ```
     * addDetector('fancy', ['header' => ['X-Fancy' => 1]);
     * ```
     *
     * Les types `param`, `env` et de comparaison permettent ce qui suit
     * options de comparaison de valeur :
     *
     * ### Comparaison des valeurs de modèle
     *
     * La comparaison de valeurs de modèles vous permet de comparer une valeur extraite de `env()` à une expression régulière.
     *
     * ```
     * addDetector('iphone', ['env' => 'HTTP_USER_AGENT', 'pattern' => '/iPhone/i']);
     * ```
     *
     * ### Comparaison basée sur les options
     *
     * Les comparaisons basées sur des options utilisent une liste d'options pour créer une expression régulière. Appels ultérieurs
     * ajouter un détecteur d'options déjà défini fusionnera les options.
     *
     * ```
     * addDetector('mobile', ['env' => 'HTTP_USER_AGENT', 'options' => ['Fennec']]);
     * ```
     *
     * Vous pouvez également comparer plusieurs valeurs
     * en utilisant la touche `options`. Ceci est utile lorsque vous souhaitez vérifier
     * si une valeur de requête se trouve dans une liste d'options.
     *
     * `addDetector('extension', ['param' => '_ext', 'options' => ['pdf', 'csv']]`
     *
     * @param array|callable $detector Un callback ou tableau d'options pour la définition du détecteur.
     */
    public static function addDetector(string $name, $detector): void
    {
        $name = strtolower($name);
        if (is_callable($detector)) {
            static::$_detectors[$name] = $detector;

            return;
        }
        if (isset(static::$_detectors[$name], $detector['options'])) {
            /** @psalm-suppress PossiblyInvalidArgument */
            $detector = Arr::merge(static::$_detectors[$name], $detector);
        }
        static::$_detectors[$name] = $detector;
    }

    /**
     * Normaliser un nom d'en-tête dans la version SERVER.
     */
    protected function normalizeHeaderName(string $name): string
    {
        $name = str_replace('-', '_', strtoupper($name));
        if (! in_array($name, ['CONTENT_LENGTH', 'CONTENT_TYPE'], true)) {
            $name = 'HTTP_' . $name;
        }

        return $name;
    }

    /**
     * Obtenez tous les en-têtes de la requête.
     *
     * Renvoie un tableau associatif où les noms d'en-tête sont
     * les clés et les valeurs sont une liste de valeurs d'en-tête.
     *
     * Bien que les noms d'en-tête ne soient pas sensibles à la casse, getHeaders() normalisera
     * les en-têtes.
     *
     * @return array<string[]> Un tableau associatif d'en-têtes et leurs valeurs.
     *
     * @see http://www.php-fig.org/psr/psr-7/ Cette méthode fait partie de l'interface de requête du serveur PSR-7.
     */
    public function getHeaders(): array
    {
        $headers = [];

        foreach ($this->_environment as $key => $value) {
            $name = null;
            if (strpos($key, 'HTTP_') === 0) {
                $name = substr($key, 5);
            }
            if (strpos($key, 'CONTENT_') === 0) {
                $name = $key;
            }
            if ($name !== null) {
                $name           = str_replace('_', ' ', strtolower($name));
                $name           = str_replace(' ', '-', ucwords($name));
                $headers[$name] = (array) $value;
            }
        }

        return $headers;
    }

    /**
     * Vérifiez si un en-tête est défini dans la requête.
     *
     * @param string $name L'en-tête que vous souhaitez obtenir (insensible à la casse)
     *
     * @see http://www.php-fig.org/psr/psr-7/ Cette méthode fait partie de l'interface de requête du serveur PSR-7.
     */
    public function hasHeader($name): bool
    {
        $name = $this->normalizeHeaderName($name);

        return isset($this->_environment[$name]);
    }

    /**
     * Obtenez un seul en-tête de la requête.
     *
     * Renvoie la valeur de l'en-tête sous forme de tableau. Si l'en-tête
     * n'est pas présent, un tableau vide sera retourné.
     *
     * @param string $name L'en-tête que vous souhaitez obtenir (insensible à la casse)
     *
     * @return string[] Un tableau associatif d'en-têtes et leurs valeurs.
     *                  Si l'en-tête n'existe pas, un tableau vide sera retourné.
     *
     * @see http://www.php-fig.org/psr/psr-7/ Cette méthode fait partie de l'interface de requête du serveur PSR-7.
     */
    public function getHeader($name): array
    {
        $name = $this->normalizeHeaderName($name);
        if (isset($this->_environment[$name])) {
            return (array) $this->_environment[$name];
        }

        return (array) $this->getEnv($name);
    }

    /**
     * Obtenez un seul en-tête sous forme de chaîne à partir de la requête.
     *
     * @param string $name L'en-tête que vous souhaitez obtenir (insensible à la casse)
     *
     * @return string Les valeurs d'en-tête sont réduites à une chaîne séparée par des virgules.
     *
     * @see http://www.php-fig.org/psr/psr-7/ Cette méthode fait partie de l'interface de requête du serveur PSR-7.
     */
    public function getHeaderLine($name): string
    {
        $value = $this->getHeader($name);

        return implode(', ', $value);
    }

    /**
     * Obtenez une demande modifiée avec l'en-tête fourni.
     *
     * @param array|string $value
     * @param mixed        $name
     *
     * @see http://www.php-fig.org/psr/psr-7/ Cette méthode fait partie de l'interface de requête du serveur PSR-7.
     */
    public function withHeader($name, $value): self
    {
        $new                      = clone $this;
        $name                     = $this->normalizeHeaderName($name);
        $new->_environment[$name] = $value;

        return $new;
    }

    /**
     * Obtenez une demande modifiée avec l'en-tête fourni.
     *
     * Les valeurs d'en-tête existantes seront conservées. La valeur fournie
     * sera ajouté aux valeurs existantes.
     *
     * @param string       $name
     * @param array|string $value
     *
     * @see http://www.php-fig.org/psr/psr-7/ Cette méthode fait partie de l'interface de requête du serveur PSR-7.
     */
    public function withAddedHeader($name, $value): self
    {
        $new      = clone $this;
        $name     = $this->normalizeHeaderName($name);
        $existing = [];
        if (isset($new->_environment[$name])) {
            $existing = (array) $new->_environment[$name];
        }
        $existing                 = array_merge($existing, (array) $value);
        $new->_environment[$name] = $existing;

        return $new;
    }

    /**
     * Obtenez une demande modifiée sans en-tête fourni.
     *
     * @see http://www.php-fig.org/psr/psr-7/ Cette méthode fait partie de l'interface de requête du serveur PSR-7.
     *
     * @param mixed $name
     */
    public function withoutHeader($name): self
    {
        $new  = clone $this;
        $name = $this->normalizeHeaderName($name);
        unset($new->_environment[$name]);

        return $new;
    }

    /**
     * Obtenez la méthode HTTP utilisée pour cette requête.
     * Il existe plusieurs manières de spécifier une méthode.
     *
     * - Si votre client le prend en charge, vous pouvez utiliser des méthodes HTTP natives.
     * - Vous pouvez définir l'en-tête HTTP-X-Method-Override.
     * - Vous pouvez soumettre une entrée avec le nom `_method`
     *
     * Chacune de ces 3 approches peut être utilisée pour définir la méthode HTTP utilisée
     * par BlitzPHP en interne, et affectera le résultat de cette méthode.
     *
     * @see http://www.php-fig.org/psr/psr-7/ Cette méthode fait partie de l'interface de requête du serveur PSR-7.
     */
    public function getMethod(): string
    {
        return (string) $this->getEnv('REQUEST_METHOD');
    }

    /**
     * Mettez à jour la méthode de requête et obtenez une nouvelle instance.
     *
     * @see http://www.php-fig.org/psr/psr-7/ Cette méthode fait partie de l'interface de requête du serveur PSR-7.
     *
     * @param mixed $method
     */
    public function withMethod($method): self
    {
        $new = clone $this;

        if (
            ! is_string($method)
            || ! preg_match('/^[!#$%&\'*+.^_`\|~0-9a-z-]+$/i', $method)
        ) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method "%s" provided',
                $method
            ));
        }
        $new->_environment['REQUEST_METHOD'] = $method;

        return $new;
    }

    /**
     * Obtenez tous les paramètres de l'environnement du serveur.
     *
     * Lire toutes les données 'environnement' ou 'serveur' qui ont été
     * utilisé pour créer cette requête.
     *
     * @see http://www.php-fig.org/psr/psr-7/ Cette méthode fait partie de l'interface de requête du serveur PSR-7.
     */
    public function getServerParams(): array
    {
        return $this->_environment;
    }

    /**
     * Obtenez tous les paramètres de requête conformément aux spécifications PSR-7. Pour lire des valeurs de requête spécifiques
     * utilisez la méthode alternative getQuery().
     *
     * @see http://www.php-fig.org/psr/psr-7/ Cette méthode fait partie de l'interface de requête du serveur PSR-7.
     */
    public function getQueryParams(): array
    {
        return $this->query;
    }

    /**
     * Mettez à jour les données de la chaîne de requête et obtenez une nouvelle instance.
     *
     * @param array $query Les données de la chaîne de requête à utiliser
     *
     * @see http://www.php-fig.org/psr/psr-7/ Cette méthode fait partie de l'interface de requête du serveur PSR-7.
     */
    public function withQueryParams(array $query): self
    {
        $new        = clone $this;
        $new->query = $query;

        return $new;
    }

    /**
     * Obtenez l'hôte sur lequel la demande a été traitée.
     */
    public function host(): ?string
    {
        if ($this->trustProxy && $this->getEnv('HTTP_X_FORWARDED_HOST')) {
            return $this->getEnv('HTTP_X_FORWARDED_HOST');
        }

        return $this->getEnv('HTTP_HOST');
    }

    /**
     * Obtenez le port sur lequel la demande a été traitée.
     */
    public function port(): ?string
    {
        if ($this->trustProxy && $this->getEnv('HTTP_X_FORWARDED_PORT')) {
            return $this->getEnv('HTTP_X_FORWARDED_PORT');
        }

        return $this->getEnv('SERVER_PORT');
    }

    /**
     * Obtenez le schéma d'URL actuel utilisé pour la demande.
     *
     * par exemple. 'http' ou 'https'
     */
    public function scheme(): ?string
    {
        if ($this->trustProxy && $this->getEnv('HTTP_X_FORWARDED_PROTO')) {
            return $this->getEnv('HTTP_X_FORWARDED_PROTO');
        }

        return $this->getEnv('HTTPS') ? 'https' : 'http';
    }

    /**
     * Obtenez le nom de domaine et incluez les segments $tldLength du tld.
     *
     * @param int $tldLength Nombre de segments que contient votre tld. Par exemple : `example.com` contient 1 tld.
     *                       Alors que `example.co.uk` contient 2.
     *
     * @return string Nom de domaine sans sous-domaines.
     */
    public function domain(int $tldLength = 1): string
    {
        $host = $this->host();
        if (empty($host)) {
            return '';
        }

        $segments = explode('.', $host);
        $domain   = array_slice($segments, -1 * ($tldLength + 1));

        return implode('.', $domain);
    }

    /**
     * Obtenez les sous-domaines d'un hôte.
     *
     * @param int $tldLength Nombre de segments que contient votre tld. Par exemple : `example.com` contient 1 tld.
     *                       Alors que `example.co.uk` contient 2.
     *
     * @return string[] Un tableau de sous-domaines.
     */
    public function subdomains(int $tldLength = 1): array
    {
        $host = $this->host();
        if (empty($host)) {
            return [];
        }

        $segments = explode('.', $host);

        return array_slice($segments, 0, -1 * ($tldLength + 1));
    }

    /**
     * Découvrez quels types de contenu le client accepte ou vérifiez s'il accepte un
     * type particulier de contenu.
     *
     * #### Obtenir tous les types :
     *
     * ```
     * $this->request->accepts();
     * ```
     *
     * #### Vérifier un seul type :
     *
     * ```
     * $this->request->accepts('application/json');
     * ```
     *
     * Cette méthode ordonnera les types de contenu renvoyés par les valeurs de préférence indiquées
     * par le client.
     *
     * @param string|null $type Le type de contenu à vérifier. Laissez null pour obtenir tous les types qu'un client accepte.
     *
     * @return bool|string[] Soit un tableau de tous les types acceptés par le client, soit un booléen s'il accepte le type fourni.
     */
    public function accepts(?string $type = null)
    {
        $raw    = $this->parseAccept();
        $accept = [];

        foreach ($raw as $types) {
            $accept = array_merge($accept, $types);
        }
        if ($type === null) {
            return $accept;
        }

        return in_array($type, $accept, true);
    }

    /**
     * Analyser l'en-tête HTTP_ACCEPT et renvoyer un tableau trié avec les types de contenu
     * comme clés et valeurs pref comme valeurs.
     *
     * Généralement, vous souhaitez utiliser {@link \BlitzPHP\Http\ServerRequest::accepts()} pour obtenir une liste simple
     * des types de contenu acceptés.
     *
     * @return array Un tableau de `prefValue => [contenu/types]`
     */
    public function parseAccept(): array
    {
        return $this->_parseAcceptWithQualifier($this->getHeaderLine('Accept'));
    }

    /**
     * Obtenez les langues acceptées par le client ou vérifiez si une langue spécifique est acceptée.
     *
     * Obtenez la liste des langues acceptées :
     *
     * ``` \BlitzPHP\Http\ServerRequest::acceptLanguage(); ```
     *
     * Vérifiez si une langue spécifique est acceptée :
     *
     * ``` \BlitzPHP\Http\ServerRequest::acceptLanguage('es-es'); ```
     *
     * @return array|bool Si un $language est fourni, un booléen. Sinon, le tableau des langues acceptées.
     */
    public function acceptLanguage(?string $language = null)
    {
        $raw    = $this->_parseAcceptWithQualifier($this->getHeaderLine('Accept-Language'));
        $accept = [];

        foreach ($raw as $languages) {
            foreach ($languages as &$lang) {
                if (strpos($lang, '_')) {
                    $lang = str_replace('_', '-', $lang);
                }
                $lang = strtolower($lang);
            }
            $accept = array_merge($accept, $languages);
        }
        if ($language === null) {
            return $accept;
        }

        return in_array(strtolower($language), $accept, true);
    }

    /**
     * Analysez les en-têtes Accept* avec les options de qualificateur.
     *
     * Seuls les qualificatifs seront extraits, toutes les autres extensions acceptées seront
     * jetés car ils ne sont pas fréquemment utilisés.
     */
    protected function _parseAcceptWithQualifier(string $header): array
    {
        $accept  = [];
        $headers = explode(',', $header);

        foreach (array_filter($headers) as $value) {
            $prefValue = '1.0';
            $value     = trim($value);

            $semiPos = strpos($value, ';');
            if ($semiPos !== false) {
                $params = explode(';', $value);
                $value  = trim($params[0]);

                foreach ($params as $param) {
                    $qPos = strpos($param, 'q=');
                    if ($qPos !== false) {
                        $prefValue = substr($param, $qPos + 2);
                    }
                }
            }

            if (! isset($accept[$prefValue])) {
                $accept[$prefValue] = [];
            }
            if ($prefValue) {
                $accept[$prefValue][] = $value;
            }
        }
        krsort($accept);

        return $accept;
    }

    /**
     * Lire une valeur de requête spécifique ou un chemin en pointillés.
     *
     * Les développeurs sont encouragés à utiliser getQueryParams() s'ils ont besoin de tout le tableau de requête,
     * car il est compatible PSR-7, et cette méthode ne l'est pas. En utilisant Hash::get(), vous pouvez également obtenir des paramètres uniques.
     *
     * ### Alternative PSR-7
     *
     * ```
     * $value = Arr::get($request->getQueryParams(), 'Post.id');
     * ```
     *
     * @param string|null $name    Le nom ou le chemin en pointillé vers le paramètre de requête ou null pour tout lire.
     * @param mixed       $default La valeur par défaut si le paramètre nommé n'est pas défini et que $name n'est pas nul.
     *
     * @return array|string|null Requête de données.
     *
     * @see ServerRequest::getQueryParams()
     */
    public function getQuery(?string $name = null, $default = null)
    {
        if ($name === null) {
            return $this->query;
        }

        return Arr::get($this->query, $name, $default);
    }

    /**
     * Fournit un accesseur sécurisé pour les données de requête. Permet
     * vous permet d'utiliser des chemins compatibles Arr::get().
     *
     * ### Lecture des valeurs.
     *
     * ```
     * // récupère toutes les données
     * $request->getData();
     *
     * // Lire un champ spécifique.
     * $request->getData('Post.title');
     *
     * // Avec une valeur par défaut.
     * $request->getData('Post.not there', 'default value');
     * ```
     *
     * Lors de la lecture des valeurs, vous obtiendrez `null` pour les clés/valeurs qui n'existent pas.
     *
     * Les développeurs sont encouragés à utiliser getParsedBody() s'ils ont besoin de tout le tableau de données,
     * car il est compatible PSR-7, et cette méthode ne l'est pas. En utilisant Hash::get(), vous pouvez également obtenir des paramètres uniques.
     *
     * ### Alternative PSR-7
     *
     * ```
     * $value = Arr::get($request->getParsedBody(), 'Post.id');
     * ```
     *
     * @param string|null $name    Nom séparé par un point de la valeur à lire. Ou null pour lire toutes les données.
     * @param mixed       $default Les données par défaut.
     *
     * @return mixed La valeur en cours de lecture.
     */
    public function getData(?string $name = null, $default = null)
    {
        if ($name === null) {
            return $this->data;
        }
        if (! is_array($this->data) && $name) {
            return $default;
        }

        /** @psalm-suppress PossiblyNullArgument */
        return Arr::get($this->data, $name, $default);
    }

    /**
     * Lire les données de cookie à partir des données de cookie de la demande.
     *
     * @param string            $key     La clé ou le chemin en pointillés que vous voulez lire.
     * @param array|string|null $default La valeur par défaut si le cookie n'est pas défini.
     *
     * @return array|string|null Soit la valeur du cookie, soit null si la valeur n'existe pas.
     */
    public function getCookie(string $key, $default = null)
    {
        return Arr::get($this->cookies, $key, $default);
    }

    /**
     * Obtenir une collection de cookies basée sur les cookies de la requête
     *
     * La CookieCollection vous permet d'interagir avec les cookies de demande en utilisant
     * Objets `\BlitzPHP\Http\Cookie\Cookie` et peut faire des cookies de demande de conversion
     * dans les cookies de réponse plus facile.
     *
     * Cette méthode créera une nouvelle collection de cookies à chaque appel.
     * Il s'agit d'une optimisation qui permet d'allouer moins d'objets jusqu'à
     * plus la CookieCollection est nécessaire. En général, vous devriez préférer
     * `getCookie()` et `getCookieParams()` sur cette méthode. Utilisation d'une collection de cookies
     * est idéal si vos cookies contiennent des données complexes encodées en JSON.
     */
    public function getCookieCollection(): CookieCollection
    {
        return CookieCollection::createFromServerRequest($this);
    }

    /**
     * Remplacez les cookies de la requête par ceux contenus dans
     * la CookieCollection fournie.
     */
    public function withCookieCollection(CookieCollection $cookies): self
    {
        $new    = clone $this;
        $values = [];

        foreach ($cookies as $cookie) {
            $values[$cookie->getName()] = $cookie->getValue();
        }
        $new->cookies = $values;

        return $new;
    }

    /**
     * Obtenez toutes les données de cookie de la requête.
     *
     * @return array Un tableau de données de cookie.
     */
    public function getCookieParams(): array
    {
        return $this->cookies;
    }

    /**
     * Remplacez les cookies et obtenez une nouvelle instance de requête.
     *
     * @param array $cookies Les nouvelles données de cookie à utiliser.
     */
    public function withCookieParams(array $cookies): self
    {
        $new          = clone $this;
        $new->cookies = $cookies;

        return $new;
    }

    /**
     * Obtenez les données de corps de requête analysées.
     *
     * Si la requête Content-Type est soit application/x-www-form-urlencoded
     * ou multipart/form-data, et la méthode de requête est POST, ce sera le
     * publier des données. Pour les autres types de contenu, il peut s'agir de la requête désérialisée
     * corps.
     *
     * @return array|object|null Les paramètres de corps désérialisés, le cas échéant.
     *                           Il s'agira généralement d'un tableau.
     */
    public function getParsedBody()
    {
        return $this->data;
    }

    /**
     * Mettez à jour le corps analysé et obtenez une nouvelle instance.
     *
     * @param array|object|null $data Les données de corps désérialisées. Cette volonté
     *                                être généralement dans un tableau ou un objet.
     */
    public function withParsedBody($data): self
    {
        $new       = clone $this;
        $new->data = $data;

        return $new;
    }

    /**
     * Récupère la version du protocole HTTP sous forme de chaîne.
     *
     * @return string Version du protocole HTTP.
     */
    public function getProtocolVersion(): string
    {
        if ($this->protocol) {
            return $this->protocol;
        }

        // Remplissez paresseusement ces données car elles ne sont généralement pas utilisées.
        preg_match('/^HTTP\/([\d.]+)$/', (string) $this->getEnv('SERVER_PROTOCOL'), $match);
        $protocol = '1.1';
        if (isset($match[1])) {
            $protocol = $match[1];
        }
        $this->protocol = $protocol;

        return $this->protocol;
    }

    /**
     * Renvoie une instance avec la version de protocole HTTP spécifiée.
     *
     * La chaîne de version DOIT contenir uniquement le numéro de version HTTP (par exemple,
     * "1.1", "1.0").
     *
     * @param string $version Version du protocole HTTP
     */
    public function withProtocolVersion($version): self
    {
        if (! preg_match('/^(1\.[01]|2(\.[0])?)$/', $version)) {
            throw new InvalidArgumentException("Unsupported protocol version '{$version}' provided");
        }
        $new           = clone $this;
        $new->protocol = $version;

        return $new;
    }

    /**
     * Obtenez une valeur à partir des données d'environnement de la demande.
     * Se replier sur env() si la clé n'est pas définie dans la propriété $environment.
     *
     * @param string      $key     La clé à partir de laquelle vous voulez lire.
     * @param string|null $default Valeur par défaut lors de la tentative de récupération d'un environnement
     *                             valeur de la variable qui n'existe pas.
     *
     * @return string|null Soit la valeur de l'environnement, soit null si la valeur n'existe pas.
     */
    public function getEnv(string $key, ?string $default = null): ?string
    {
        $key = strtoupper($key);
        if (! array_key_exists($key, $this->_environment)) {
            $this->_environment[$key] = env($key);
        }

        return $this->_environment[$key] !== null ? (string) $this->_environment[$key] : $default;
    }

    /**
     * Mettez à jour la demande avec un nouvel élément de données d'environnement.
     *
     * Renvoie un objet de requête mis à jour. Cette méthode retourne
     * un *nouvel* objet de requête et ne mute pas la requête sur place.
     */
    public function withEnv(string $key, string $value): self
    {
        $new                     = clone $this;
        $new->_environment[$key] = $value;
        $new->clearDetectorCache();

        return $new;
    }

    /**
     * Autoriser uniquement certaines méthodes de requête HTTP, si la méthode de requête ne correspond pas
     * une erreur 405 s'affichera et l'en-tête de réponse "Autoriser" requis sera défini.
     *
     * Exemple:
     *
     * $this->request->allowMethod('post');
     * ou alors
     * $this->request->allowMethod(['post', 'delete']);
     *
     * Si la requête est GET, l'en-tête de réponse "Autoriser : POST, SUPPRIMER" sera défini
     * et une erreur 405 sera renvoyée.
     *
     * @param string|string[] $methods Méthodes de requête HTTP autorisées.
     *
     * @throws HttpException
     */
    public function allowMethod($methods): bool
    {
        $methods = (array) $methods;

        foreach ($methods as $method) {
            if ($this->is($method)) {
                return true;
            }
        }
        $allowed = strtoupper(implode(', ', $methods));

        throw HttpException::methodNotAllowed($allowed);
    }

    /**
     * Mettez à jour la demande avec un nouvel élément de données de demande.
     *
     * Renvoie un objet de requête mis à jour. Cette méthode retourne
     * un *nouvel* objet de requête et ne mute pas la requête sur place.
     *
     * Utilisez `withParsedBody()` si vous devez remplacer toutes les données de la requête.
     *
     * @param string $name  Le chemin séparé par des points où insérer $value.
     * @param mixed  $value
     */
    public function withData(string $name, $value): self
    {
        $copy = clone $this;

        if (is_array($copy->data)) {
            $copy->data = Arr::insert($copy->data, $name, $value);
        }

        return $copy;
    }

    /**
     * Mettre à jour la demande en supprimant un élément de données.
     *
     * Renvoie un objet de requête mis à jour. Cette méthode retourne
     * un *nouvel* objet de requête et ne mute pas la requête sur place.
     *
     * @param string $name Le chemin séparé par des points à supprimer.
     */
    public function withoutData(string $name): self
    {
        $copy = clone $this;

        if (is_array($copy->data)) {
            $copy->data = Arr::remove($copy->data, $name);
        }

        return $copy;
    }

    /**
     * Mettre à jour la requête avec un nouveau paramètre de routage
     *
     * Renvoie un objet de requête mis à jour. Cette méthode retourne
     * un *nouvel* objet de requête et ne mute pas la requête sur place.
     *
     * @param string $name  Le chemin séparé par des points où insérer $value.
     * @param mixed  $value
     */
    public function withParam(string $name, $value): self
    {
        $copy         = clone $this;
        $copy->params = Arr::insert($copy->params, $name, $value);

        return $copy;
    }

    /**
     * Accédez en toute sécurité aux valeurs dans $this->params.
     *
     * @param mixed|null $default
     */
    public function getParam(string $name, $default = null)
    {
        return Arr::get($this->params, $name, $default);
    }

    /**
     * Renvoie une instance avec l'attribut de requête spécifié.
     *
     * @param string $name  Le nom de l'attribut.
     * @param mixed  $value La valeur de l'attribut.
     */
    public function withAttribute($name, $value): self
    {
        $new = clone $this;
        if (in_array($name, $this->emulatedAttributes, true)) {
            $new->{$name} = $value;
        } else {
            $new->attributes[$name] = $value;
        }

        return $new;
    }

    /**
     * Renvoie une instance sans l'attribut de requête spécifié.
     *
     * @param string $name Le nom de l'attribut.
     *
     * @throws InvalidArgumentException
     */
    public function withoutAttribute($name): self
    {
        $new = clone $this;
        if (in_array($name, $this->emulatedAttributes, true)) {
            throw new InvalidArgumentException(
                "You cannot unset '{$name}'. It is a required BlitzPHP attribute."
            );
        }
        unset($new->attributes[$name]);

        return $new;
    }

    /**
     * Tentatives d'obtenir de vieilles données d'entrée qui a été flashé à la session avec redirect_with_input().
     * Il vérifie d'abord les données dans les anciennes données POST, puis les anciennes données GET et enfin vérifier les tableaux de points
     *
     * @return array|string|null
     */
    public function getOldInput(string $key)
    {
        return $this->session()->getOldInput($key);
    }

    /**
     * Lire un attribut de la requête ou obtenir la valeur par défaut
     *
     * @param string     $name    Le nom de l'attribut.
     * @param mixed|null $default La valeur par défaut si l'attribut n'a pas été défini.
     *
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        if (in_array($name, $this->emulatedAttributes, true)) {
            if ($name === 'here') {
                return $this->base . $this->uri->getPath();
            }

            return $this->{$name};
        }
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        return $default;
    }

    /**
     * Obtenez tous les attributs de la requête.
     *
     * Cela inclura les attributs params, webroot, base et here fournis par BlitzPHP.
     */
    public function getAttributes(): array
    {
        $emulated = [
            'params'  => $this->params,
            'webroot' => $this->webroot,
            'base'    => $this->base,
            'here'    => $this->base . $this->uri->getPath(),
        ];

        return $this->attributes + $emulated;
    }

    /**
     * Obtenez le fichier téléchargé à partir d'un chemin en pointillés.
     *
     * @param string $path Le chemin séparé par des points vers le fichier que vous voulez.
     *
     * @return UploadedFileInterface|UploadedFileInterface[]|null
     */
    public function getUploadedFile(string $path)
    {
        $file = Arr::get($this->uploadedFiles, $path);
        if (is_array($file)) {
            foreach ($file as $f) {
                if (! ($f instanceof UploadedFile)) {
                    return null;
                }
            }

            return $file;
        }

        if (! ($file instanceof UploadedFileInterface)) {
            return null;
        }

        return $file;
    }

    /**
     * Obtenez le tableau des fichiers téléchargés à partir de la requête.
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * Mettez à jour la demande en remplaçant les fichiers et en créant une nouvelle instance.
     *
     * @param array $uploadedFiles Un tableau d'objets de fichiers téléchargés.
     *
     * @throws InvalidArgumentException lorsque $files contient un objet invalide.
     */
    public function withUploadedFiles(array $uploadedFiles): self
    {
        $this->validateUploadedFiles($uploadedFiles, '');
        $new                = clone $this;
        $new->uploadedFiles = $uploadedFiles;

        return $new;
    }

    /**
     * Validez de manière récursive les données de fichier téléchargées.
     *
     * @param array  $uploadedFiles Le nouveau tableau de fichiers à valider.
     * @param string $path          Le chemin jusqu'ici.
     *
     * @throws InvalidArgumentException Si des éléments feuilles ne sont pas des fichiers valides.
     */
    protected function validateUploadedFiles(array $uploadedFiles, string $path): void
    {
        foreach ($uploadedFiles as $key => $file) {
            if (is_array($file)) {
                $this->validateUploadedFiles($file, $key . '.');

                continue;
            }

            if (! $file instanceof UploadedFileInterface) {
                throw new InvalidArgumentException("Invalid file at '{$path}{$key}'");
            }
        }
    }

    /**
     * Obtient le corps du message.
     */
    public function getBody(): StreamInterface
    {
        return $this->stream;
    }

    /**
     * Renvoie une instance avec le corps de message spécifié.
     */
    public function withBody(StreamInterface $body): self
    {
        $new         = clone $this;
        $new->stream = $body;

        return $new;
    }

    /**
     * Récupère l'instance d'URI.
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * Renvoie une instance avec l'uri spécifié
     *
     * *Attention* Remplacer l'Uri ne mettra pas à jour la `base`, `webroot`,
     * et les attributs `url`.
     *
     * @param bool $preserveHost Indique si l'hôte doit être conservé.
     */
    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        $new      = clone $this;
        $new->uri = $uri;

        if ($preserveHost && $this->hasHeader('Host')) {
            return $new;
        }

        $host = $uri->getHost();
        if (! $host) {
            return $new;
        }
        $port = $uri->getPort();
        if ($port) {
            $host .= ':' . $port;
        }
        $new->_environment['HTTP_HOST'] = $host;

        return $new;
    }

    /**
     * Créez une nouvelle instance avec une cible de demande spécifique.
     *
     * Vous pouvez utiliser cette méthode pour écraser la cible de la demande qui est
     * déduit de l'Uri de la requête. Cela vous permet également de modifier la demande
     * la forme de la cible en une forme absolue, une forme d'autorité ou une forme d'astérisque
     *
     * @see https://tools.ietf.org/html/rfc7230#section-2.7 (pour les différentes formes de demande-cible autorisées dans les messages de demande)
     *
     * @param string $requestTarget La cible de la requête.
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function withRequestTarget($requestTarget): self
    {
        $new                = clone $this;
        $new->requestTarget = $requestTarget;

        return $new;
    }

    /**
     * Récupère la cible de la requête.
     *
     * Récupère la cible de la demande du message soit telle qu'elle a été demandée,
     * ou comme défini avec `withRequestTarget()`. Par défaut, cela renverra le
     * chemin relatif de l'application sans répertoire de base et la chaîne de requête
     * défini dans l'environnement SERVER.
     */
    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ($this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }

        if (empty($target)) {
            $target = '/';
        }

        return $target;
    }

    /**
     * Récupère le chemin de la requête en cours.
     */
    public function getPath(): string
    {
        if ($this->requestTarget === null) {
            return $this->uri->getPath();
        }

        [$path] = explode('?', $this->requestTarget);

        return $path;
    }

    /**
     * Fournit un moyen pratique de travailler avec la classe Negotiate
     * pour la négociation de contenu.
     */
    public function negotiate(string $type, array $supported, bool $strictMatch = false): string
    {
        if (null === $this->negotiator) {
            $this->negotiator = Services::negotiator($this, true);
        }

        switch (strtolower($type)) {
            case 'media':
                return $this->negotiator->media($supported, $strictMatch);

            case 'charset':
                return $this->negotiator->charset($supported);

            case 'encoding':
                return $this->negotiator->encoding($supported);

            case 'language':
                return $this->negotiator->language($supported);
        }

        throw new HttpException($type . ' is not a valid negotiation type. Must be one of: media, charset, encoding, language.');
    }

    /**
     * Définit la chaîne locale pour cette requête.
     */
    public function withLocale(string $locale): self
    {
        $validLocales = config('app.supported_locales');
        // S'il ne s'agit pas d'un paramètre régional valide, définissez-le
        // aux paramètres régionaux par défaut du site.
        if (! in_array($locale, $validLocales, true)) {
            $locale = config('app.language');
        }

        Services::language()->setLocale($locale);

        return $this->withAttribute('locale', $locale);
    }

    /**
     * Obtient les paramètres régionaux actuels, avec un retour à la valeur par défaut
     * locale si aucune n'est définie.
     */
    public function getLocale(): string
    {
        $locale = $this->getAttribute('locale');
        if (empty($locale)) {
            $locale = $this->getAttribute('lang');
        }

        return $locale ?? Services::language()->getLocale();
    }

    /**
     * Read data from `php://input`. Useful when interacting with XML or JSON
     * request body content.
     *
     * Getting input with a decoding function:
     *
     * ```
     * $this->request->input('json_decode');
     * ```
     *
     * Getting input using a decoding function, and additional params:
     *
     * ```
     * $this->request->input('Xml::build', ['return' => 'DOMDocument']);
     * ```
     *
     * Any additional parameters are applied to the callback in the order they are given.
     *
     * @param string|null $callback A decoding callback that will convert the string data to another
     *                              representation. Leave empty to access the raw input data. You can also
     *                              supply additional parameters for the decoding callback using var args, see above.
     * @param array       ...$args  The additional arguments
     *
     * @return string The decoded/processed request data.
     */
    public function input($callback = null, ...$args): string
    {
        $this->stream->rewind();
        $input = $this->stream->getContents();
        if ($callback) {
            array_unshift($args, $input);

            return $callback(...$args);
        }

        return $input;
    }

    /**
     * Sets the REQUEST_METHOD environment variable based on the simulated _method
     * HTTP override value. The 'ORIGINAL_REQUEST_METHOD' is also preserved, if you
     * want the read the non-simulated HTTP method the client used.
     *
     * @param array $data Array of post data.
     *
     * @return array
     */
    protected function _processPost(array $data)
    {
        $method   = $this->getEnv('REQUEST_METHOD');
        $override = false;

        if ($_POST) {
            $data = $_POST;
        } elseif (
            in_array($method, ['PUT', 'DELETE', 'PATCH'], true)
            && strpos($this->contentType() ?? '', 'application/x-www-form-urlencoded') === 0
        ) {
            $data = $this->input();
            parse_str($data, $data);
        }
        if (ini_get('magic_quotes_gpc') === '1') {
            $data = Helpers::stripslashesDeep((array) $this->data);
        }

        if ($this->hasHeader('X-Http-Method-Override')) {
            $data['_method'] = $this->getHeaderLine('X-Http-Method-Override');
            $override        = true;
        }
        $this->_environment['ORIGINAL_REQUEST_METHOD'] = $method;

        if (isset($data['_method'])) {
            $this->_environment['REQUEST_METHOD'] = $data['_method'];
            unset($data['_method']);
            $override = true;
        }

        if ($override && ! in_array($this->_environment['REQUEST_METHOD'], ['PUT', 'POST', 'DELETE', 'PATCH'], true)) {
            $data = [];
        }

        return $data;
    }

    /**
     * Process the GET parameters and move things into the object.
     *
     * @param array  $query       The array to which the parsed keys/values are being added.
     * @param string $queryString A query string from the URL if provided
     *
     * @return array An array containing the parsed query string as keys/values.
     */
    protected function _processGet($query, $queryString = '')
    {
        if (ini_get('magic_quotes_gpc') === '1') {
            $q = Helpers::stripslashesDeep($_GET);
        } else {
            $q = $_GET;
        }
        $query = array_merge($q, $query);

        $unsetUrl = '/' . str_replace(['.', ' '], '_', urldecode($this->url));
        unset($query[$unsetUrl], $query[$this->base . $unsetUrl]);

        if (strpos($this->url, '?') !== false) {
            [, $querystr] = explode('?', $this->url);
            parse_str($querystr, $queryArgs);
            $query += $queryArgs;
        }
        if (isset($this->params['url'])) {
            $query = array_merge($this->params['url'], $query);
        }

        return $query;
    }

    /**
     * Process uploaded files and move things onto the post data.
     *
     * @param array $post  Post data to merge files onto.
     * @param array $files Uploaded files to merge in.
     *
     * @return array merged post + file data.
     */
    protected function _processFiles(array $post, array $files): array
    {
        if (! is_array($files)) {
            return $post;
        }

        $fileData = [];

        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $fileData[$key] = $value;

                continue;
            }

            if (is_array($value) && isset($value['tmp_name'])) {
                $fileData[$key] = $this->_createUploadedFile($value);

                continue;
            }

            throw new InvalidArgumentException(sprintf(
                'Invalid value in FILES "%s"',
                json_encode($value)
            ));
        }

        $this->uploadedFiles = $fileData;

        // Make a flat map that can be inserted into $post for BC.
        $fileMap = Arr::flatten($fileData);

        foreach ($fileMap as $key => $file) {
            $error   = $file->getError();
            $tmpName = '';

            if ($error === UPLOAD_ERR_OK) {
                $tmpName = $file->getStream()->getMetadata('uri');
            }

            $post = Arr::insert($post, $key, [
                'tmp_name' => $tmpName,
                'error'    => $error,
                'name'     => $file->getClientFilename(),
                'type'     => $file->getClientMediaType(),
                'size'     => $file->getSize(),
            ]);
        }

        return $post;
    }

    /**
     * Create an UploadedFile instance from a $_FILES array.
     *
     * If the value represents an array of values, this method will
     * recursively process the data.
     *
     * @param array $value $_FILES struct
     *
     * @return UploadedFile|UploadedFile[]
     */
    protected function _createUploadedFile(array $value)
    {
        if (is_array($value['tmp_name'])) {
            return $this->_normalizeNestedFiles($value);
        }

        return new UploadedFile(
            $value['tmp_name'],
            $value['size'],
            $value['error'],
            $value['name'],
            $value['type']
        );
    }

    /**
     * Normalize an array of file specifications.
     *
     * Loops through all nested files and returns a normalized array of
     * UploadedFileInterface instances.
     *
     * @param array $files The file data to normalize & convert.
     *
     * @return UploadedFile[]
     */
    protected function _normalizeNestedFiles(array $files = []): array
    {
        $normalizedFiles = [];

        foreach (array_keys($files['tmp_name']) as $key) {
            $spec = [
                'tmp_name' => $files['tmp_name'][$key],
                'size'     => $files['size'][$key],
                'error'    => $files['error'][$key],
                'name'     => $files['name'][$key],
                'type'     => $files['type'][$key],
            ];

            $normalizedFiles[$key] = $this->_createUploadedFile($spec);
        }

        return $normalizedFiles;
    }
}
