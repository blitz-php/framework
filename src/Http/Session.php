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

use BlitzPHP\Core\App;
use BlitzPHP\Utilities\Arr;
use InvalidArgumentException;
use RuntimeException;
use SessionHandlerInterface;

/**
 * Cette classe est un wrapper pour les fonctions de session PHP natives. Il offre
 * plusieurs valeurs par défaut pour la configuration de session la plus courante
 * via des gestionnaires externes et aide à utiliser la session dans la CLI sans aucun avertissement.
 *
 * Les sessions peuvent être créées à partir des valeurs par défaut en utilisant `Session ::create()` ou vous pouvez obtenir
 * une instance d'une nouvelle session en instanciant simplement cette classe et en passant le
 * options que vous souhaitez utiliser.
 *
 * Lorsque des options spécifiques sont omises, cette classe prendra ses valeurs par défaut de la configuration
 * valeurs des directives `session.*` dans php.ini. Cette classe modifiera également ces
 * directives lorsque des valeurs de configuration sont fournies.
 *
 * @credit <a href="http://https://api.cakephp.org/4.3/namespace-Cake.Http.Session.html">CakePHP - Http\Session</a>
 */
/** @phpstan-consistent-constructor */
class Session
{
    /**
     * L'instance du gestionnaire de session utilisée comme moteur pour conserver les données de session.
     *
     * @var SessionHandlerInterface
     */
    protected $_engine;

    /**
     * Indique si les sessions ont déjà commencé
     *
     * @var bool
     */
    protected $_started;

    /**
     * La durée en secondes pendant laquelle la session sera valide
     *
     * @var int
     */
    protected $_lifetime;

    /**
     * Si cette session s'exécute dans un environnement CLI
     *
     * @var bool
     */
    protected $_isCLI = false;

    /**
     * Renvoie une nouvelle instance d'une session après avoir créé un bundle de configuration pour celle-ci.
     * Cette fonction permet d'avoir un tableau d'options qui sera utilisé pour configurer la session
     * et le gestionnaire à utiliser. La clé la plus importante dans le tableau de configuration est
     * `defaults`, qui indique l'ensemble des configurations dont hériter, les
     * les valeurs par défaut sont :
     *
     * - php : utilisez simplement la session telle que configurée dans php.ini
     * - cache : utilisez le système de mise en cache de BlitzPHP comme stockage pour la session, vous aurez besoin
     * pour passer la clé `config` avec le nom d'un moteur de cache déjà configuré.
     * - base de données : Utilisez l'ORM BlitzPHP pour persister et gérer les sessions. Par défaut, cela nécessite
     * une table dans votre base de données nommée `sessions` ou une clé `model` dans la configuration
     * pour indiquer quel objet Table utiliser.
     * - blitz : Utilisez des fichiers pour stocker les sessions, mais laissez BlitzPHP les gérer et décider
     * où les stocker.
     *
     * La liste complète des options suit :
     *
     * - default : soit 'php', 'database', 'cache' ou 'blitz' comme expliqué ci-dessus.
     * - handler : un tableau contenant la configuration du gestionnaire
     * - ini : une liste de directives php.ini à définir avant le démarrage de la session.
     * - timeout : Le temps en minutes pendant lequel la session doit rester active
     */
    public static function create(array $sessionConfig = [])
    {
        if (isset($sessionConfig['defaults'])) {
            $defaults = static::_defaultConfig($sessionConfig['defaults']);
            if ($defaults) {
                $sessionConfig = Arr::merge($defaults, $sessionConfig);
            }
        }

        if (
            ! isset($sessionConfig['ini']['session.cookie_secure'])
            && env('HTTPS')
            && ini_get('session.cookie_secure') !== 1
        ) {
            $sessionConfig['ini']['session.cookie_secure'] = 1;
        }

        if (
            ! isset($sessionConfig['ini']['session.name'])
            && isset($sessionConfig['cookie'])
        ) {
            $sessionConfig['ini']['session.name'] = $sessionConfig['cookie'];
        }

        if (! isset($sessionConfig['ini']['session.use_strict_mode']) && ini_get('session.use_strict_mode') !== 1) {
            $sessionConfig['ini']['session.use_strict_mode'] = 1;
        }

        if (! isset($sessionConfig['ini']['session.cookie_httponly']) && ini_get('session.cookie_httponly') !== 1) {
            $sessionConfig['ini']['session.cookie_httponly'] = 1;
        }

        return new static($sessionConfig);
    }

    /**
     * Obtenez l'une des configurations de session par défaut prédéfinies.
     *
     * @return array|false
     */
    protected static function _defaultConfig(string $name)
    {
        $tmp      = defined('TEMP_PATH') ? TEMP_PATH : sys_get_temp_dir() . DIRECTORY_SEPARATOR;
        $defaults = [
            'php' => [
                'ini' => [
                    'session.use_trans_sid' => 0,
                ],
            ],
            'blitz' => [
                'ini' => [
                    'session.use_trans_sid'     => 0,
                    'session.serialize_handler' => 'php',
                    'session.use_cookies'       => 1,
                    'session.save_path'         => $tmp . 'sessions',
                    'session.save_handler'      => 'files',
                ],
            ],
            'cache' => [
                'ini' => [
                    'session.use_trans_sid' => 0,
                    'session.use_cookies'   => 1,
                ],
                'handler' => [
                    'engine' => 'CacheSession',
                    'config' => 'default',
                ],
            ],
            'database' => [
                'ini' => [
                    'session.use_trans_sid'     => 0,
                    'session.use_cookies'       => 1,
                    'session.serialize_handler' => 'php',
                ],
                'handler' => [
                    'engine' => 'DatabaseSession',
                ],
            ],
        ];

        if (isset($defaults[$name])) {
            if (
                PHP_VERSION_ID >= 70300
                && ($name !== 'php' || empty(ini_get('session.cookie_samesite')))
            ) {
                $defaults['php']['ini']['session.cookie_samesite'] = 'Lax';
            }

            return $defaults[$name];
        }

        return false;
    }

    /**
     * Constructor.
     *
     * ### Configuration:
     *
     * - timeout : durée en minutes pendant laquelle la session doit être valide.
     * - cookiePath : Le chemin d'URL pour lequel le cookie de session est défini. Cartes vers le
     * `session.cookie_path` configuration php.ini. Par défaut, le chemin de base de l'application.
     * - ini : Une liste de directives php.ini à modifier avant le démarrage de la session.
     * - handler : Un tableau contenant au moins la clé `engine`. A utiliser comme séance
     * moteur de persistance des données. Le reste des clés du tableau sera transmis comme
     * le tableau de configuration du moteur. Vous pouvez définir la clé `engine` sur une clé déjà
     * objet gestionnaire de session instancié.
     *
     * @param array<string, mixed> $config La configuration à appliquer à cet objet de session
     */
    public function __construct(array $config = [])
    {
        $config += [
            'timeout' => null,
            'cookie'  => null,
            'ini'     => [],
            'handler' => [],
        ];

        if ($config['timeout']) {
            $config['ini']['session.gc_maxlifetime'] = 60 * $config['timeout'];
        }

        if ($config['cookie']) {
            $config['ini']['session.name'] = $config['cookie'];
        }

        if (! isset($config['ini']['session.cookie_path'])) {
            $cookiePath                           = empty($config['cookiePath']) ? '/' : $config['cookiePath'];
            $config['ini']['session.cookie_path'] = $cookiePath;
        }

        $this->options($config['ini']);

        if (! empty($config['handler'])) {
            $class = $config['handler']['engine'];
            unset($config['handler']['engine']);
            $this->engine($class, $config['handler']);
        }

        $this->_lifetime = (int) ini_get('session.gc_maxlifetime');
        $this->_isCLI    = (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');
        session_register_shutdown();
    }

    /**
     * Définit l'instance du gestionnaire de session à utiliser pour cette session.
     * Si une chaîne est passée pour le premier argument, elle sera traitée comme le
     * le nom de la classe et le deuxième argument seront passés comme premier argument
     * dans le constructeur.
     *
     * Si une instance de SessionHandlerInterface est fournie comme premier argument,
     * le gestionnaire y sera défini.
     *
     * Si aucun argument n'est passé, il renverra l'instance de gestionnaire actuellement configurée
     * ou null s'il n'en existe pas.
     *
     * @param SessionHandlerInterface|string|null $class   Le gestionnaire de session à utiliser
     * @param array<string, mixed>                $options les options à passer au constructeur SessionHandler
     *
     * @throws InvalidArgumentException
     */
    public function engine($class = null, array $options = []): ?SessionHandlerInterface
    {
        if ($class === null) {
            return $this->_engine;
        }
        if ($class instanceof SessionHandlerInterface) {
            return $this->setEngine($class);
        }
        $className = App::className($class, 'Http/Session');

        if (! $className) {
            throw new InvalidArgumentException(
                sprintf('The class "%s" does not exist and cannot be used as a session engine', $class)
            );
        }

        $handler = new $className($options);
        if (! ($handler instanceof SessionHandlerInterface)) {
            throw new InvalidArgumentException(
                'The chosen SessionHandler does not implement SessionHandlerInterface, it cannot be used as an engine.'
            );
        }

        return $this->setEngine($handler);
    }

    /**
     * Définissez la propriété du moteur et mettez à jour le gestionnaire de session en PHP.
     */
    protected function setEngine(SessionHandlerInterface $handler): SessionHandlerInterface
    {
        if (! headers_sent() && session_status() !== \PHP_SESSION_ACTIVE) {
            session_set_save_handler($handler, false);
        }

        return $this->_engine = $handler;
    }

    /**
     * Appelle ini_set pour chacune des clés dans `$options` et les définit
     * à la valeur respective dans le tableau passé.
     *
     * ### Example:
     *
     * ```
     * $session->options(['session.use_cookies' => 1]);
     * ```
     *
     * @param array<string, mixed> $options Options ini à définir.
     *
     * @throws RuntimeException si aucune directive n'a pu être définie
     */
    public function options(array $options): void
    {
        if (session_status() === \PHP_SESSION_ACTIVE || headers_sent()) {
            return;
        }

        foreach ($options as $setting => $value) {
            if (ini_set($setting, (string) $value) === false) {
                throw new RuntimeException(
                    sprintf('Unable to configure the session, setting %s failed.', $setting)
                );
            }
        }
    }

    /**
     * Démarre la session.
     *
     * @throws RuntimeException si la session a déjà commencé
     *
     * @return bool Vrai si la session a été démarrée
     */
    public function start(): bool
    {
        if ($this->_started) {
            return true;
        }

        if ($this->_isCLI) {
            $_SESSION = [];
            $this->id('cli');

            return $this->_started = true;
        }

        if (session_status() === \PHP_SESSION_ACTIVE) {
            throw new RuntimeException('Session was already started');
        }

        if (ini_get('session.use_cookies') && headers_sent()) {
            return false;
        }

        if (! session_start()) {
            throw new RuntimeException('Could not start the session');
        }

        $this->_started = true;

        if ($this->_timedOut()) {
            $this->destroy();

            return $this->start();
        }

        return $this->_started;
    }

    /**
     * Écrire des données et fermer la session
     */
    public function close(): bool
    {
        if (! $this->_started) {
            return true;
        }

        if ($this->_isCLI) {
            $this->_started = false;

            return true;
        }

        if (! session_write_close()) {
            throw new RuntimeException('Could not close the session');
        }

        $this->_started = false;

        return true;
    }

    /**
     * Déterminez si la session a déjà été démarrée..
     */
    public function started(): bool
    {
        return $this->_started || session_status() === \PHP_SESSION_ACTIVE;
    }

    /**
     * Renvoie true si le nom de variable donné est défini dans la session.
     */
    public function check(?string $name = null): bool
    {
        if ($this->_hasSession() && ! $this->started()) {
            $this->start();
        }

        if (! isset($_SESSION)) {
            return false;
        }

        if ($name === null) {
            return (bool) $_SESSION;
        }

        return Arr::get($_SESSION, $name) !== null;
    }

    /**
     * Renvoie la variable de session donnée, ou toutes, si aucun paramètre n'est donné.
     *
     * @param string|null $name    Le nom de la variable de session (ou un chemin tel qu'envoyé à Hash.extract)
     * @param mixed       $default La valeur de retour lorsque le chemin n'existe pas
     *
     * @return mixed|null La valeur de la variable de session, ou la valeur par défaut si une session
     *                    n'est pas disponible, ne peut pas être démarré ou à condition que $name ne soit pas trouvé dans la session.
     */
    public function read(?string $name = null, $default = null)
    {
        if ($this->_hasSession() && ! $this->started()) {
            $this->start();
        }

        if (! isset($_SESSION)) {
            return $default;
        }

        if ($name === null) {
            return $_SESSION ?: [];
        }

        return Arr::get($_SESSION, $name, $default);
    }

    /**
     * Renvoie la variable de session donnée ou lève une exception si elle n'est pas trouvée.
     *
     * @param string $name Le nom de la variable de session (ou un chemin tel qu'envoyé à Arr.extract)
     *
     * @throws RuntimeException
     *
     * @return mixed|null
     */
    public function readOrFail(string $name)
    {
        if (! $this->check($name)) {
            throw new RuntimeException(sprintf('Expected session key "%s" not found.', $name));
        }

        return $this->read($name);
    }

    /**
     * Lit et supprime une variable de la session.
     *
     * @param string $name La clé à lire et à supprimer (ou un chemin tel qu'envoyé à Hash.extract).
     *
     * @return mixed|null La valeur de la variable de session, null si session non disponible,
     *                    session non démarrée ou nom fourni introuvable dans la session.
     */
    public function consume(string $name)
    {
        if (empty($name)) {
            return null;
        }
        $value = $this->read($name);
        if ($value !== null) {
            /** @psalm-suppress InvalidScalarArgument */
            $this->_overwrite($_SESSION, Arr::remove($_SESSION, $name));
        }

        return $value;
    }

    /**
     * Écrit la valeur dans le nom de variable de session donné.
     *
     * @param array|string $name  Nom de la variable
     * @param mixed|null   $value
     */
    public function write($name, $value = null): void
    {
        if (! $this->started()) {
            $this->start();
        }

        if (! is_array($name)) {
            $name = [$name => $value];
        }

        $data = $_SESSION ?? [];

        foreach ($name as $key => $val) {
            $data = Arr::insert($data, $key, $val);
        }

        /** @psalm-suppress PossiblyNullArgument */
        $this->_overwrite($_SESSION, $data);
    }

    /**
     * Renvoie l'identifiant de la session.
     * L'appel de cette méthode ne démarrera pas automatiquement la session. Vous devrez peut-être manuellement
     * revendiquer une session démarrée.
     *
     * En y passant un identifiant, vous pouvez également remplacer l'identifiant de session si la session
     * n'a pas encore été lancé.
     * Notez que selon le gestionnaire de session, tous les caractères ne sont pas autorisés
     * dans l'identifiant de session. Par exemple, le gestionnaire de session de fichiers autorise uniquement
     * caractères dans la plage a-z A-Z 0-9 , (virgule) et - (moins).
     *
     * @param string|null $id Id pour remplacer l'identifiant de la session en cours
     */
    public function id(?string $id = null): string
    {
        if ($id !== null && ! headers_sent()) {
            session_id($id);
        }

        return session_id();
    }

    /**
     * Supprime une variable de la session.
     */
    public function delete(string $name): void
    {
        if ($this->check($name)) {
            /** @psalm-suppress InvalidScalarArgument */
            $this->_overwrite($_SESSION, Arr::remove($_SESSION, $name));
        }
    }

    /**
     * Utilisé pour écrire de nouvelles données dans _SESSION, car PHP n'aime pas que nous définissions la variable _SESSION elle-même.
     *
     * @param array $old Ensemble d'anciennes variables => valeurs
     * @param array $new Nouvel ensemble de variable => valeur
     */
    protected function _overwrite(array &$old, array $new): void
    {
        if (! empty($old)) {
            foreach ($old as $key => $var) {
                if (! isset($new[$key])) {
                    unset($old[$key]);
                }
            }
        }

        foreach ($new as $key => $var) {
            $old[$key] = $var;
        }
    }

    /**
     * Méthode d'assistance pour détruire les sessions invalides.
     */
    public function destroy(): void
    {
        if ($this->_hasSession() && ! $this->started()) {
            $this->start();
        }

        if (! $this->_isCLI && session_status() === \PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $_SESSION       = [];
        $this->_started = false;
    }

    /**
     * Efface la session.
     *
     * En option, il efface également l'identifiant de session et renouvelle la session.
     *
     * @param bool $renew Si la session doit également être renouvelée. La valeur par défaut est false.
     */
    public function clear(bool $renew = false): void
    {
        $_SESSION = [];
        if ($renew) {
            $this->renew();
        }
    }

    /**
     * Retourne si une session existe
     */
    protected function _hasSession(): bool
    {
        return ! ini_get('session.use_cookies')
            || isset($_COOKIE[session_name()])
            || $this->_isCLI
            || (ini_get('session.use_trans_sid') && isset($_GET[session_name()]));
    }

    /**
     * Redémarre cette session.
     */
    public function renew(): void
    {
        if (! $this->_hasSession() || $this->_isCLI) {
            return;
        }

        $this->start();
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );

        if (session_id() !== '') {
            session_regenerate_id(true);
        }
    }

    /**
     * Renvoie true si la session n'est plus valide car la dernière fois qu'elle a été
     * l'accès s'est fait après le délai d'attente configuré.
     */
    protected function _timedOut(): bool
    {
        $time   = $this->read('Config.time');
        $result = false;

        $checkTime = $time !== null && $this->_lifetime > 0;
        if ($checkTime && (time() - (int) $time > $this->_lifetime)) {
            $result = true;
        }

        $this->write('Config.time', time());

        return $result;
    }
}
