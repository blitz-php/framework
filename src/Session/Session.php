<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Session;

use BlitzPHP\Contracts\Database\ConnectionInterface;
use BlitzPHP\Contracts\Session\SessionInterface;
use BlitzPHP\Session\Cookie\Cookie;
use BlitzPHP\Session\Handlers\BaseHandler;
use BlitzPHP\Session\Handlers\Database;
use BlitzPHP\Utilities\Date;
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\Utilities\Iterable\Arr;
use InvalidArgumentException;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;

class Session implements SessionInterface
{
    use LoggerAwareTrait;

    /**
     * Un tableau mappant les schémas d'URL aux noms de classe de moteur de session complets.
     *
     * @var array<string, string>
     * @psalm-var array<string, class-string>
     */
    protected static array $validHandlers = [
        'array'     => Handlers\ArrayHandler::class,
        'file'      => Handlers\File::class,
        'memcached' => Handlers\Memcached::class,
        'redis'     => Handlers\Redis::class,
        'database'  => Handlers\Database::class,
        'postgre'   => Handlers\Database\Postgre::class,
        'mysql'     => Handlers\Database\MySQL::class,
    ];

    /**
     * La configuration de session par défaut est remplacée dans la plupart des adaptateurs. Ceux-ci sont
     * les clés communes à tous les adaptateurs. Si elle est remplacée, cette propriété n'est pas utilisée.
     *
     * - `cookie_prefix` @var string
     * 			Préfixe ajouté à toutes les entrées. Bon pour quand vous avez besoin de partager un keyspace
     * 			avec une autre configuration de session ou une autre application.
     * - `cookie_domain` @var string Domaine des Cookies.
     * - `cookie_name` @var string Nom du cookie à utiliser.
     * - `cookie_path` @var string Chemin des Cookies.
     * - `cookie_secure` @var bool Cookie sécurisé ?
     * - `matchIP` @var bool Faire correspondre les adresses IP pour les cookies ?
     * - `keyPrefix` @var string prefixe de la cle de session (memcached, redis, database)
     * - `savePath` @var array|string Le "chemin d'enregistrement" de la session varie entre
     * - `expiration` @var int Nombre de secondes jusqu'à la fin de la session.
     *
     * @var array<string, mixed>
     */
    protected array $config = [
        'savePath'           => [],
        'cookie_prefix'      => 'blitz_',
        'cookie_path'        => '/',
        'cookie_domain'      => '',
        'cookie_name'        => '',
        'secure'             => false,
        'matchIP'            => false,
        'expiration'         => 7200,
        'handler'            => 'file',
        'time_to_update'     => 300,
        'regenerate_destroy' => false,
    ];

    /**
     * Adapter a utiliser pour la session
     */
    private BaseHandler $adapter;

    /**
     * Instance de la connexion a la bd (uniquement pour les gestionnaires de session de type base de donnees)
     */
    private ?ConnectionInterface $db = null;

    /**
     * L'instance de cookie de session.
     */
    protected Cookie $cookie;

    /**
     * sid regex
     *
     * @var string
     */
    protected $sidRegexp;

    /**
     * Constructeur
     */
    public function __construct(array $config, array $cookie, protected string $ipAddress)
    {
        $this->config = array_merge($this->config, $config);

        $this->cookie = Cookie::create(
            $this->config['cookie_name'],
            '',
            array_merge($cookie, [
                'expires'  => $this->config['expiration'] === 0 ? 0 : Date::now()->getTimestamp() + $this->config['expiration'],
                'httponly' => true, // pour raison de securite
            ])
        ); // ->withPrefix(''); // Le prefix du cookie peut etre ignorer
    }

    /**
     * Tente de créer le gestionnaire de session souhaité
     */
    protected function factory(): BaseHandler
    {
        if (! empty($this->adapter)) {
            return $this->adapter;
        }

        $validHandlers = $this->config['valid_handlers'] ?? self::$validHandlers;

        if (empty($validHandlers) || ! is_array($validHandlers)) {
            throw new InvalidArgumentException('La configuration de la session doit avoir un tableau de $valid_handlers.');
        }

        $handler = $this->config['handler'] ?? null;

        if (empty($handler)) {
            throw new InvalidArgumentException('La configuration de la session doit avoir un ensemble de gestionnaires.');
        }

        if (in_array($handler, $validHandlers, true)) {
            $handler = array_search($handler, $validHandlers, true);
        }
        if (! array_key_exists($handler, $validHandlers)) {
            throw new InvalidArgumentException('La configuration de la session a un gestionnaire non valide spécifié.');
        }

        $adapter = new $validHandlers[$handler]();
        if (! ($adapter instanceof BaseHandler)) {
            throw new InvalidArgumentException('Le gestionnaire de cache doit utiliser BlitzPHP\Session\Handlers\BaseHandler comme classe de base.');
        }

        if (! $adapter->init($this->config, $this->ipAddress)) {
            throw new RuntimeException(
                sprintf(
                    'Le gestionnaire de session %s n\'est pas correctement configuré. Consultez le journal des erreurs pour plus d\'informations.',
                    get_class($adapter)
                )
            );
        }

        $adapter->setLogger($this->logger);

        if ($adapter instanceof Database) {
            $adapter->setDatabase($this->db);
        }

        return $this->adapter = $adapter;
    }

    /**
     * Defini l'instance de la database a utiliser (pour les gestionnaire de session de type base de donnees)
     */
    public function setDatabase(?ConnectionInterface $db): self
    {
        $this->db = $db;

        return $this;
    }

    /**
     * Initialize the session container and starts up the session.
     *
     * @return self|void
     */
    public function start()
    {
        if (Helpers::isCli() && ! $this->onTest()) {
            // @codeCoverageIgnoreStart
            $this->logger->debug('Session: Initialization under CLI aborted.');

            return;
            // @codeCoverageIgnoreEnd
        }

        if ((bool) ini_get('session.auto_start')) {
            $this->logger->error('Session: session.auto_start est activé dans php.ini. Abandon.');

            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->logger->warning('Session: Les sessions sont activées et il en existe une.Veuillez ne pas $session->start();');

            return;
        }

        $this->configure();
        $this->setSaveHandler();

        // Désinfectez le cookie, car apparemment PHP ne le fait pas pour les gestionnaires d'espace utilisateur
        if (isset($_COOKIE[$this->config['cookie_name']])
            && (! is_string($_COOKIE[$this->config['cookie_name']]) || ! preg_match('#\A' . $this->sidRegexp . '\z#', $_COOKIE[$this->config['cookie_name']]))
        ) {
            unset($_COOKIE[$this->config['cookie_name']]);
        }

        $this->startSession();

        // La régénération automatique de l'ID de session est-elle configurée ? (en ignorant les requêtes ajax)
        if (! Helpers::isAjaxRequest() && ($regenerateTime = $this->config['time_to_update']) > 0) {
            if (! isset($_SESSION['__blitz_last_regenerate'])) {
                $_SESSION['__blitz_last_regenerate'] = Date::now()->getTimestamp();
            } elseif ($_SESSION['__blitz_last_regenerate'] < (Date::now()->getTimestamp() - $regenerateTime)) {
                $this->regenerate((bool) $this->config['regenerate_destroy']);
            }
        }
        // Une autre solution de contournement ... PHP ne semble pas envoyer le cookie de session à moins qu'il ne soit en cours de création ou de régénération
        elseif (isset($_COOKIE[$this->config['cookie_name']]) && $_COOKIE[$this->config['cookie_name']] === session_id()) {
            $this->setCookie();
        }

        $this->initVars();
        $this->logger->info("Session: Classe initialisée à l'aide de '" . Helpers::classBasename($this->factory()) . "'");

        return $this;
    }

    /**
     * Fait un arrêt complet de la session :
     *
     * - détruit la session
     * - annule l'identifiant de session
     * - détruit le cookie de session
     */
    public function stop()
    {
        setcookie(
            $this->config['cookie_name'],
            session_id(),
            ['expires' => 1, 'path' => $this->cookie->getPath(), 'domain' => $this->cookie->getDomain(), 'secure' => $this->cookie->isSecure(), 'httponly' => true]
        );

        session_regenerate_id(true);
    }

    /**
     * {@inheritDoc}
     */
    public function regenerate(bool $destroy = false): void
    {
        $_SESSION['__blitz_last_regenerate'] = Date::now()->getTimestamp();
        session_regenerate_id($destroy);

        // $this->removeOldSessionCookie();
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(): bool
    {
        if ($this->onTest()) {
            return true;
        }

        return session_destroy();
    }

    /**
     * {@inheritDoc}
     */
    public function set(array|string $data, mixed $value = null): void
    {
        if (is_array($data)) {
            foreach ($data as $key => &$value) {
                if (is_int($key)) {
                    $_SESSION[$value] = null;
                } else {
                    $_SESSION[$key] = $value;
                }
            }

            return;
        }

        $_SESSION[$data] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function get(?string $key = null): mixed
    {
        if (! empty($key) && (null !== ($value = $_SESSION[$key] ?? null) || null !== ($value = Arr::getRecursive($_SESSION ?? [], $key)))) {
            return $value;
        }

        if (empty($_SESSION)) {
            return $key === null ? [] : null;
        }

        if (! empty($key)) {
            return null;
        }

        return Arr::except($_SESSION, array_merge(['__blitz_vars'], $this->getFlashKeys(), $this->getTempKeys()));
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Poussez la nouvelle valeur sur la valeur de session qui est un tableau.
     *
     * @param string $key  Identifiant de la propriété de session qui nous intéresse.
     * @param array  $data valeur à pousser vers la clé de session existante.
     */
    public function push(string $key, array $data)
    {
        if ($this->has($key) && is_array($value = $this->get($key))) {
            $this->set($key, array_merge($value, $data));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function remove(array|string $key): void
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                unset($_SESSION[$k]);
            }

            return;
        }

        unset($_SESSION[$key]);
    }

    /**
     * Méthode magique pour définir des variables dans la session en appelant simplement
     *  $session->foo = bar;
     */
    public function __set(string $key, mixed $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Méthode magique pour obtenir des variables de session en appelant simplement
     * $foo = $session->foo ;
     */
    public function __get(string $key): mixed
    {
        // Remarque : Gardez cet ordre identique, juste au cas où quelqu'un voudrait utiliser 'session_id'
        // comme clé de données de session, pour quelque raison que ce soit
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }

        if ($key === 'session_id') {
            return session_id();
        }

        return null;
    }

    /**
     * Méthode magique pour vérifier les variables de session.
     * Différent de has() en ce sens qu'il validera également 'session_id'.
     * Principalement utilisé par les fonctions PHP internes, les utilisateurs doivent s'en tenir à has()
     */
    public function __isset(string $key): bool
    {
        return isset($_SESSION[$key]) || ($key === 'session_id');
    }

    /**
     * {@inheritDoc}
     */
    public function setFlashdata(array|string $data, array|bool|float|int|object|string|null $value = null): void
    {
        $this->set($data, $value);
        $this->markAsFlashdata(is_array($data) ? array_keys($data) : $data);
    }

    /**
     * {@inheritDoc}
     */
    public function getFlashdata(?string $key = null): ?array
    {
        if (isset($key)) {
            return (isset($_SESSION['__blitz_vars'], $_SESSION['__blitz_vars'][$key], $_SESSION[$key])
                && ! is_int($_SESSION['__blitz_vars'][$key])) ? $_SESSION[$key] : null;
        }

        $flashdata = [];

        if (! empty($_SESSION['__blitz_vars'])) {
            foreach ($_SESSION['__blitz_vars'] as $key => &$value) {
                if (! is_int($value)) {
                    $flashdata[$key] = $_SESSION[$key];
                }
            }
        }

        return $flashdata;
    }

    /**
     * {@inheritDoc}
     */
    public function keepFlashdata(array|string $key): void
    {
        $this->markAsFlashdata($key);
    }

    /**
     * {@inheritDoc}
     */
    public function markAsFlashdata(array|string $key): bool
    {
        if (is_array($key)) {
            foreach ($key as $sessionKey) {
                if (! isset($_SESSION[$sessionKey])) {
                    return false;
                }
            }

            $new = array_fill_keys($key, 'new');

            $_SESSION['__blitz_vars'] = isset($_SESSION['__blitz_vars']) ? array_merge($_SESSION['__blitz_vars'], $new) : $new;

            return true;
        }

        if (! isset($_SESSION[$key])) {
            return false;
        }

        $_SESSION['__blitz_vars'][$key] = 'new';

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function unmarkFlashdata(array|string $key): void
    {
        if (empty($_SESSION['__blitz_vars'])) {
            return;
        }

        if (! is_array($key)) {
            $key = [$key];
        }

        foreach ($key as $k) {
            if (isset($_SESSION['__blitz_vars'][$k]) && ! is_int($_SESSION['__blitz_vars'][$k])) {
                unset($_SESSION['__blitz_vars'][$k]);
            }
        }

        if (empty($_SESSION['__blitz_vars'])) {
            unset($_SESSION['__blitz_vars']);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getFlashKeys(): array
    {
        if (! isset($_SESSION['__blitz_vars'])) {
            return [];
        }

        $keys = [];

        foreach (array_keys($_SESSION['__blitz_vars']) as $key) {
            if (! is_int($_SESSION['__blitz_vars'][$key])) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * {@inheritDoc}
     */
    public function setTempdata(array|string $data, array|bool|float|int|object|string|null $value = null, int $ttl = 300): void
    {
        $this->set($data, $value);
        $this->markAsTempdata($data, $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function getTempdata(?string $key = null)
    {
        if (isset($key)) {
            return (isset($_SESSION['__blitz_vars'], $_SESSION['__blitz_vars'][$key], $_SESSION[$key])
                    && is_int($_SESSION['__blitz_vars'][$key])) ? $_SESSION[$key] : null;
        }

        $tempdata = [];

        if (! empty($_SESSION['__blitz_vars'])) {
            foreach ($_SESSION['__blitz_vars'] as $key => &$value) {
                if (is_int($value)) {
                    $tempdata[$key] = $_SESSION[$key];
                }
            }
        }

        return $tempdata;
    }

    /**
     * {@inheritDoc}
     */
    public function removeTempdata(string $key): void
    {
        $this->unmarkTempdata($key);
        unset($_SESSION[$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function markAsTempdata(array|string $key, int $ttl = 300): bool
    {
        $ttl += Date::now()->getTimestamp();

        if (is_array($key)) {
            $temp = [];

            foreach ($key as $k => $v) {
                // Avons-nous une paire clé => ttl, ou juste une clé ?
                if (is_int($k)) {
                    $k = $v;
                    $v = $ttl;
                } elseif (is_string($v)) {
                    $v = Date::now()->getTimestamp() + $ttl;
                } else {
                    $v += Date::now()->getTimestamp();
                }

                if (! array_key_exists($k, $_SESSION)) {
                    return false;
                }

                $temp[$k] = $v;
            }

            $_SESSION['__blitz_vars'] = isset($_SESSION['__blitz_vars']) ? array_merge($_SESSION['__blitz_vars'], $temp) : $temp;

            return true;
        }

        if (! isset($_SESSION[$key])) {
            return false;
        }

        $_SESSION['__blitz_vars'][$key] = $ttl;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function unmarkTempdata(array|string $key): void
    {
        if (empty($_SESSION['__blitz_vars'])) {
            return;
        }

        if (! is_array($key)) {
            $key = [$key];
        }

        foreach ($key as $k) {
            if (isset($_SESSION['__blitz_vars'][$k]) && is_int($_SESSION['__blitz_vars'][$k])) {
                unset($_SESSION['__blitz_vars'][$k]);
            }
        }

        if (empty($_SESSION['__blitz_vars'])) {
            unset($_SESSION['__blitz_vars']);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getTempKeys(): array
    {
        if (! isset($_SESSION['__blitz_vars'])) {
            return [];
        }

        $keys = [];

        foreach (array_keys($_SESSION['__blitz_vars']) as $key) {
            if (is_int($_SESSION['__blitz_vars'][$key])) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * Définit le pilote comme gestionnaire de session en PHP.
     * Extrait pour faciliter les tests.
     */
    protected function setSaveHandler()
    {
        session_set_save_handler($this->factory(), true);
    }

    /**
     * Demarre la session
     * Extrait pour faciliter les tests.
     */
    protected function startSession()
    {
        if ($this->onTest()) {
            $_SESSION = [];

            return;
        }

        session_start(); // @codeCoverageIgnore
    }

    /**
     * Se charge de paramétrer le cookie côté client.
     *
     * @codeCoverageIgnore
     */
    protected function setCookie()
    {
        $expiration   = $this->config['expiration'] === 0 ? 0 : Date::now()->getTimestamp() + $this->config['expiration'];
        $this->cookie = $this->cookie->withValue(session_id())->withExpiry($expiration);
    }

    /**
     * Configuration.
     *
     * Gérer les liaisons d'entrée et les valeurs par défaut de configuration.
     */
    protected function configure()
    {
        if (empty($this->config['cookie_name'])) {
            $this->config['cookie_name'] = ini_get('session.name');
        } else {
            ini_set('session.name', $this->config['cookie_name']);
        }

        $sameSite = $this->cookie->getSameSite() ?: ucfirst(Cookie::SAMESITE_LAX);

        $params = [
            'lifetime' => $this->config['expiration'],
            'path'     => $this->cookie->getPath(),
            'domain'   => $this->cookie->getDomain(),
            'secure'   => $this->cookie->isSecure(),
            'httponly' => true, // HTTP uniquement ; Oui, c'est intentionnel et non configurable pour des raisons de sécurité.
            'samesite' => $sameSite,
        ];

        ini_set('session.cookie_samesite', $sameSite);
        session_set_cookie_params($params);

        if (! isset($this->config['expiration'])) {
            $this->config['expiration'] = (int) ini_get('session.gc_maxlifetime');
        } elseif ($this->config['expiration'] > 0) {
            ini_set('session.gc_maxlifetime', (string) $this->config['expiration']);
        }

        if (! empty($this->config['savePath'])) {
            ini_set('session.save_path', $this->config['savePath']);
        }

        // La securite est le roi
        ini_set('session.use_trans_sid', '0');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_cookies', '1');
        ini_set('session.use_only_cookies', '1');

        $this->configureSidLength();
    }

    /**
     * Configurer la longueur de l'ID de session
     *
     * Pour faciliter la vie, nous avions l'habitude de forcer SHA-1 et 4 bits par personnage sur tout le monde. Et bien sûr, quelqu'un était mécontent.
     *
     * Ensuite, PHP 7.1 a rompu la rétrocompatibilité car ext/session est un tel gâchis que personne ne veut y toucher avec un bâton de poteau, et le seul gars qui le fait, personne n'a l'énergie de discuter avec.
     *
     * Nous avons donc été obligés de faire des changements, et BIEN SUR quelque chose allait casser et maintenant nous avons ce tas de merde. --Narf
     */
    protected function configureSidLength()
    {
        $bitsPerCharacter = (int) (ini_get('session.sid_bits_per_character') !== false
            ? ini_get('session.sid_bits_per_character')
            : 4);

        $sidLength = (int) (ini_get('session.sid_length') !== false
            ? ini_get('session.sid_length')
            : 40);

        if (($sidLength * $bitsPerCharacter) < 160) {
            $bits = ($sidLength * $bitsPerCharacter);
            // Ajoutez autant de caractères que nécessaire pour atteindre au moins 160 bits
            $sidLength += (int) ceil((160 % $bits) / $bitsPerCharacter);
            ini_set('session.sid_length', (string) $sidLength);
        }

        // Oui, 4,5,6 sont les seules valeurs possibles connues au 17-10-2016
        switch ($bitsPerCharacter) {
            case 4:
                $this->sidRegexp = '[0-9a-f]';
                break;

            case 5:
                $this->sidRegexp = '[0-9a-v]';
                break;

            case 6:
                $this->sidRegexp = '[0-9a-zA-Z,-]';
                break;
        }

        $this->sidRegexp .= '{' . $sidLength . '}';
    }

    /**
     * Gérer les variables temporaires
     *
     * Efface les anciennes données "flash", marque la nouvelle pour la suppression et gère la suppression des données "temp".
     */
    protected function initVars(): void
    {
        if (empty($_SESSION['__blitz_vars'])) {
            return;
        }

        $currentTime = Date::now()->getTimestamp();

        foreach ($_SESSION['__blitz_vars'] as $key => &$value) {
            if ($value === 'new') {
                $_SESSION['__blitz_vars'][$key] = 'old';
            }
            // NE le déplacez PAS au-dessus du "nouveau" contrôle !
            elseif ($value === 'old' || $value < $currentTime) {
                unset($_SESSION[$key], $_SESSION['__blitz_vars'][$key]);
            }
        }

        if (empty($_SESSION['__blitz_vars'])) {
            unset($_SESSION['__blitz_vars']);
        }
    }

    /**
     * Verifie si on est en test
     */
    private function onTest(): bool
    {
        return function_exists('on_test') && on_test();
    }
}
