<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Config\Config;
use BlitzPHP\Http\ServerRequest;
use BlitzPHP\Http\Uri;
use BlitzPHP\Loader\Services;
use BlitzPHP\Utilities\Helpers;
use Kint\Kint;
use Psr\Http\Message\ResponseInterface;

// ================================= FONCTIONS D'ACCESSIBILITE ================================= //

if (! function_exists('env')) {

    /**
     * Obtient une variable d'environnement à partir des sources disponibles et fournit une émulation
     * pour les variables d'environnement non prises en charge ou incohérentes
     *
     * @param string     $key     Nom de la variable d'environnement
     * @param mixed|null $default
     *
     * @return string Paramétrage des variables d'environnement.
     */
    function env(string $key, $default = null)
    {
        return Helpers::env($key, $default);
    }
}

if (! function_exists('helper')) {
    /**
     * Charge un fichier d'aide en mémoire. Prend en charge les assistants d'espace de noms,
     * à la fois dans et hors du répertoire 'helpers' d'un répertoire à espace de noms.
     *
     * Chargera TOUS les assistants du nom correspondant, dans l'ordre suivant :
     *   1. app/Helpers
     *   2. {namespace}/Helpers
     *   3. system/Helpers
     *
     * @param array|string $filenames
     */
    function helper($filenames)
    {
        // Load::helper($filenames);
    }
}

if (! function_exists('service')) {
    /**
     * Permet un accès plus propre au fichier de configuration des services.
     * Renvoie toujours une instance SHARED de la classe, donc
     * appeler la fonction plusieurs fois doit toujours
     * renvoie la même instance.
     *
     * Ceux-ci sont égaux :
     *  - $cache = service('cache')
     *  - $cache = \BlitzPHP\Loader\Services::cache();
     */
    function service(string $name, ...$params)
    {
        return Services::$name(...$params);
    }
}

if (! function_exists('single_service')) {
    /**
     * Autoriser l'accès propre à un service.
     * Renvoie toujours une nouvelle instance de la classe.
     */
    function single_service(string $name, ...$params)
    {
        // Ensure it's NOT a shared instance
        $params[] = false;

        return Services::$name(...$params);
    }
}

if (! function_exists('show404')) {
    /**
     * Afficher une page 404 introuvable dans le navigateur
     */
    function show404(string $message = 'The page you requested was not found.', string $heading = 'Page Not Found', array $params = [])
    {
        // return Errors::show404($message, $heading, $params);
    }
}

if (! function_exists('config')) {
    /**
     * GET/SET App config
     *
     * @param mixed $value
     *
     * @return mixed
     */
    function config(string $config, $value = null, bool $force_set = false)
    {
        if (! empty($value) || (empty($value) && true === $force_set)) {
            Config::set($config, $value);
        }

        return Config::get($config);
    }
}

// ================================= FONCTIONS D'ENVIRONNEMENT D'EXECUTION ================================= //

if (! function_exists('on_dev')) {
    /**
     * Testez pour voir si nous sommes dans un environnement de développement.
     * 
     * @param bool checkOnline 
     */
    function on_dev(bool $checkOnline = false): bool
    {
        if ($checkOnline && is_online()) {
            return false;
        }
        
        $env = config('app.environment');

        return in_array($env, ['dev', 'development'], true);
    }
}

if (! function_exists('on_prod')) {
    /**
     * Testez pour voir si nous sommes dans un environnement de production.
     */
    function on_prod(bool $checkOnline = false): bool
    {
        if ($checkOnline && is_online()) {
            return true;
        }

        $env = config('app.environment');

        return in_array($env, ['prod', 'production'], true);
    }
}

if (! function_exists('on_test')) {
    /**
     * Testez pour voir si nous sommes dans un environnement de test
     */
    function on_test(): bool
    {
        $env = config('app.environment');

        return in_array($env, ['test', 'testing'], true);
    }
}

if (! function_exists('is_cli')) {
    /**
     * Testez pour voir si une demande a été faite à partir de la ligne de commande.
     */
    function is_cli(): bool
    {
        return PHP_SAPI === 'cli' || defined('STDIN');
    }
}

if (! function_exists('is_php')) {
    /**
     * Détermine si la version actuelle de PHP est égale ou supérieure à la valeur fournie.
     */
    function is_php(string $version): bool
    {
        return false;
        // return Helpers::is_php($version);
    }
}

if (! function_exists('is_windows')) {
    /**
     * Déterminez si l'environnement actuel est basé sur Windows.
     */
    function is_windows(): bool
    {
        return strtolower(substr(PHP_OS, 0, 3)) === 'win';
    }
}

if (! function_exists('is_https')) {
    /**
     * Determines if the application is accessed via an encrypted * (HTTPS) connection.
     */
    function is_https(): bool
    {
        return Services::request()->is('ssl');
    }
}

if (! function_exists('is_localfile')) {
    /**
     * Vérifiez si le fichier auquel vous souhaitez accéder est un fichier local de votre application ou non
     */
    function is_localfile(string $name): bool
    {
        return false;
        // return Helpers::is_localfile($name);
    }
}

if (! function_exists('is_online')) {
    /**
     * Tester si l'application s'exécute en local ou en ligne.
     */
    function is_online(): bool
    {
        return Helpers::isOnline();
    }
}

if (! function_exists('is_ajax_request')) {
    /**
     * Testez pour voir si une requête contient l'en-tête HTTP_X_REQUESTED_WITH.
     */
    function is_ajax_request(): bool
    {
        return Services::request()->is('ajax');
    }
}

// ================================= FONCTIONS DE MANIPULATION D'URL ================================= //

if (! function_exists('site_url')) {
    /**
     * Créez une URL locale basée sur votre chemin de base. Les segments peuvent être passés via le
     * premier paramètre sous forme de chaîne ou de tableau.
     *
     * @param mixed $uri
     */
    function site_url($uri = '', ?string $protocol = null): string
    {
        return '';
        // return Helpers::site_url($uri, $protocol);
    }
}

if (! function_exists('base_url')) {
    /**
     * Créez une URL locale basée sur votre chemin de base.
     * Les segments peuvent être transmis sous forme de chaîne ou de tableau, comme site_url
     * ou une URL vers un fichier peut être transmise, par ex. à un fichier image.
     *
     * @param mixed $uri
     */
    function base_url($uri = '', ?string $protocol = null): string
    {
        return '';
        // return Helpers::base_url($uri, $protocol);
    }
}

if (! function_exists('current_url')) {
    /**
     * Current URL
     *
     * Returns the full URL (including segments) of the page where this
     * function is placed
     *
     * @param bool $returnObject True to return an object instead of a strong
     *
     * @return \dFramework\core\http\Uri|string
     */
    function current_url(bool $returnObject = false)
    {
        $uri = Services::uri(site_url($_SERVER['REQUEST_URI']));

        // Since we're basing off of the IncomingRequest URI,
        // we are guaranteed to have a host based on our own configs.
        return $returnObject
            ? $uri
            : (string) $uri->setQuery('');
    }
}

if (! function_exists('previous_url')) {
    /**
     * Renvoie l'URL précédente sur laquelle se trouvait le visiteur actuel. Pour des raisons de sécurité
     * nous vérifions d'abord une variable de session enregistrée, si elle existe, et l'utilisons.
     * Si ce n'est pas disponible, cependant, nous utiliserons une URL épurée de $_SERVER['HTTP_REFERER']
     * qui peut être défini par l'utilisateur, il n'est donc pas fiable et n'est pas défini par certains navigateurs/serveurs.
     *
     * @return \BlitzPHP\Http\Uri|mixed|string
     */
    function previous_url(bool $returnObject = false)
    {
        // Récupérez d'abord la session, si nous l'avons,
        // car c'est plus fiable et plus sûr.
        // Sinon, récupérez une version épurée de $_SERVER.
        $referer = $_SESSION['_blitz_previous_url'] ?? null;
        if (false === filter_var($referer, FILTER_VALIDATE_URL)) {
            $referer = Services::request()->getServer('HTTP_REFERER', FILTER_SANITIZE_URL);
        }

        $referer ??= site_url('/');

        return $returnObject ? Services::uri($referer) : $referer;
    }
}

if (! function_exists('redirect')) {
    /**
     * Redirige l'utilisateur
     */
    function redirect(string $uri = '', string $method = 'location', ?int $code = 302)
    {
        Services::response()->redirect($uri, $method, $code);
    }
}

if (! function_exists('redirection')) {
    /**
     * Méthode pratique qui fonctionne avec la $request globale actuelle et
     * l'instance $router à rediriger à l'aide de routes nommées et le routage inversé
     * pour déterminer l'URL à laquelle aller. Si rien n'est trouvé, traitera
     * comme une redirection traditionnelle et passez la chaîne, en laissant
     * $redirection->redirect() détermine la méthode et le code corrects.
     *
     * Si plus de contrôle est nécessaire, vous devez utiliser explicitement $response->redirect.
     *
     * @return \BlitzPHP\Http\Redirection|void
     */
    function redirection(?string $uri = null)
    {
        $redirection = Services::redirection();

        if (! empty($uri)) {
            return $redirection->route($uri);
        }

        return $redirection;
    }
}

if (! function_exists('link_to')) {
    /**
     * Étant donné une chaîne de contrôleur/méthode et tous les paramètres,
     * tentera de créer l'URL relative à la route correspondante.
     *
     * REMARQUE : Cela nécessite que le contrôleur/la méthode
     * ait une route définie dans le fichier de configuration des routes.
     */
    function link_to(string $method, ...$params): string
    {
        $url = Services::routes()->reverseRoute($method, ...$params);
        if (empty($url)) {
            $rul = '';
        }

        return site_url($url);
    }
}

if (! function_exists('clean_url')) {
    function clean_url(string $url): string
    {
        return Helpers::cleanUrl($url);
    }
}

if (! function_exists('clean_path')) {
    /**
     * Une méthode pratique pour nettoyer les chemins pour
     * une sortie plus belle. Utile pour les exceptions
     * gestion, journalisation des erreurs, etc.
     */
    function clean_path(string $path): string
    {
        // Resolve relative paths
        $path = realpath($path) ?: $path;

        switch (true) {
            case strpos($path, APP_PATH) === 0:
                return 'APP_PATH' . DIRECTORY_SEPARATOR . substr($path, strlen(APP_PATH));

            case strpos($path, SYST_PATH) === 0:
                return 'SYST_PATH' . DIRECTORY_SEPARATOR . substr($path, strlen(SYST_PATH));

            case defined('COMPOSER_PATH') && strpos($path, COMPOSER_PATH) === 0:
                return 'COMPOSER_PATH' . DIRECTORY_SEPARATOR . substr($path, strlen(COMPOSER_PATH));

            case strpos($path, ROOTPATH) === 0:
                return 'ROOTPATH' . DIRECTORY_SEPARATOR . substr($path, strlen(ROOTPATH));

            default:
                return $path;
        }
    }
}

// ================================= FONCTIONS DE DEBOGAGE ================================= //

if (! function_exists('dd')) {
    /**
     * Prints a Kint debug report and exits.
     *
     * @param array ...$vars
     *
     * @codeCoverageIgnore Can't be tested ... exits
     */
    function dd(...$vars)
    {
        Kint::$aliases[] = 'dd';
        Kint::dump(...$vars);

        exit;
    }
}

if (! function_exists('deprecationWarning')) {
    /**
     * Méthode d'assistance pour générer des avertissements d'obsolescence
     *
     * @param string $message    Le message à afficher comme avertissement d'obsolescence.
     * @param int    $stackFrame Le cadre de pile à inclure dans l'erreur. Par défaut à 1
     *                           car cela devrait pointer vers le code de l'application/du plugin.
     *
     * @return void
     */
    function deprecation_warning(string $message, int $stackFrame = 1)
    {
        Helpers::deprecationWarning($message, $stackFrame);
    }
}

if (! function_exists('logger')) {
    /**
     * A convenience/compatibility method for logging events through
     * the Log system.
     *
     * Allowed log levels are:
     *  - emergency
     *  - alert
     *  - critical
     *  - error
     *  - warning
     *  - notice
     *  - info
     *  - debug
     *
     * @param int|string $level
     * @param string     $message
     * @param array|null $context
     *
     * @return \BlitzPHP\Debug\Logger|mixed
     */
    function logger($level = null, ?string $message = null, array $context = [])
    {
        $logger = Services::logger();

        if (! empty($level) && ! empty($message)) {
            return $logger->log($level, $message, $context);
        }

        return $logger;
    }
}

if (! function_exists('cache')) {
    /**
     * Une méthode pratique qui donne accès au cache
     * objet. Si aucun paramètre n'est fourni, renverra l'objet,
     * sinon, tentera de renvoyer la valeur mise en cache.
     *
     * Examples:
     *    cache()->set('foo', 'bar'); or cache('foo', 'bar');
     *    $foo = cache('bar');
     *
     * @param mixed|null $value
     *
     * @return \BlitzPHP\Cache\Cache|bool|mixed
     */
    function cache(?string $key = null, $value = null)
    {
        $cache = Services::cache();

        if (empty($key)) {
            return $cache;
        }

        if (empty($value)) {
            return $cache->get($key);
        }

        return $cache->set($key, $value);
    }
}

if (! function_exists('pr')) {
    /**
     * print_r() convenience function.
     *
     * In terminals this will act similar to using print_r() directly, when not run on cli
     * print_r() will also wrap <pre> tags around the output of given variable. Similar to debug().
     *
     * This function returns the same variable that was passed.
     *
     * @param mixed $var Variable to print out.
     *
     * @return mixed the same $var that was passed to this function
     */
    function pr($var)
    {
        $template = (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') ? '<pre class="pr">%s</pre>' : "\n%s\n\n";
        printf($template, trim(print_r($var, true)));

        return $var;
    }
}

if (! function_exists('pj')) {
    /**
     * json pretty print convenience function.
     *
     * In terminals this will act similar to using json_encode() with JSON_PRETTY_PRINT directly, when not run on cli
     * will also wrap <pre> tags around the output of given variable. Similar to pr().
     *
     * This function returns the same variable that was passed.
     *
     * @param mixed $var Variable to print out.
     *
     * @return mixed the same $var that was passed to this function
     *
     * @see pr()
     */
    function pj($var)
    {
        return Helpers::pj($var);
    }
}

if (! function_exists('trigger_warning')) {
    /**
     * Déclenche un E_USER_WARNING.
     */
    function trigger_warning(string $message)
    {
        Helpers::triggerWarning($message);
    }
}

if (! function_exists('vd')) {
    /**
     * Shortcut to ref, HTML mode
     *
     * @param mixed $args
     *
     * @return string|void
     */
    function vd()
    {
        $params = func_get_args();
        // return 	Helpers::r(...$params);
    }
}

if (! function_exists('vdt')) {
    /**
     * Shortcut to ref, plain text mode
     *
     * @param mixed $args
     *
     * @return string|void
     */
    function vdt()
    {
        $params = func_get_args();
        // return 	Helpers::rt(...$params);
    }
}

// ================================= FONCTIONS DIVERSES ================================= //

if (! function_exists('force_https')) {
    /**
     * Utilisé pour forcer l'accès à une page via HTTPS.
     * Utilise une redirection standard, plus définira l'en-tête HSTS
     * pour les navigateurs modernes qui prennent en charge, ce qui donne une meilleur
     * protection contre les attaques de l'homme du milieu.
     *
     * @see https://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security
     *
     * @param int $duration Combien de temps l'en-tête SSL doit-il être défini ? (en secondes)
     *                      Par défaut à 1 an.
     *
     * Non testable, car il sortira !
     *
     * @credit CodeIgniter 4.0.0
     * @codeCoverageIgnore
     */
    function force_https(int $duration = 31536000, ?ServerRequest $request = null, ?ResponseInterface $response = null)
    {
        if (null === $request) {
            $request = Services::request();
        }
        if (null === $response) {
            $response = Services::response();
        }

        if (is_cli() || $request->is('ssl')) {
            return;
        }

        // If the session library is loaded, we should regenerate
        // the session ID for safety sake.
        if (class_exists('Session', false)) {
            // Session::regenerate();
        }

        $baseURL = base_url();

        if (strpos($baseURL, 'http://') === 0) {
            $baseURL = (string) substr($baseURL, strlen('http://'));
        }

        $uri = Uri::createURIString(
            'https',
            $baseURL,
            $request->getUri()->getPath(), // Absolute URIs should use a "/" for an empty path
            $request->getUri()->getQuery(),
            $request->getUri()->getFragment()
        );

        // Set an HSTS header
        $response = $response->withHeader('Strict-Transport-Security', 'max-age=' . $duration);
        $response->redirect($uri);

        exit(1);
    }
}

if (! function_exists('getTypeName')) {
    /**
     * Renvoie la classe d'objets ou le type var de ce n'est pas un objet
     *
     * @param mixed $var Variable à vérifier
     *
     * @return string Renvoie le nom de la classe ou le type de variable
     */
    function getTypeName($var): string
    {
        return is_object($var) ? get_class($var) : gettype($var);
    }
}

if (! function_exists('ip_address')) {
    /**
     * Renvoie l'adresse IP de l'utilisateur actuel
     */
    function ip_address(): string
    {
        return (string) Services::request()->clientIp();
    }
}

if (! function_exists('is_really_writable')) {
    /**
     * Tests d'inscriptibilité des fichiers
     */
    function is_really_writable(string $file): bool
    {
        return true;
        // return Helpers::is_really_writable($file);
    }
}

if (! function_exists('lang')) {
    /**
     * Une méthode pratique pour traduire une chaîne ou un tableau d'entrées et formater
     * le résultat avec le MessageFormatter de l'extension intl.
     *
     * @param array  $args
     * @param string $locale
     *
     * @return string
     */
    function lang(string $line, ?array $args = [], ?string $locale = null)
    {
        return Services::language($locale)->getLine($line, $args);
    }
}

if (! function_exists('namespaceSplit')) {
    /**
     * Séparez l'espace de noms du nom de classe.
     *
     * Couramment utilisé comme `list($namespace, $className) = namespaceSplit($class);`.
     *
     * @param string $class Le nom complet de la classe, ie `BlitzPHP\Http\Request`.
     *
     * @return array Tableau avec 2 index. 0 => namespace, 1 => classname.
     */
    function namespaceSplit(string $class): array
    {
        $pos = strrpos($class, '\\');
        if ($pos === false) {
            return ['', $class];
        }

        return [substr($class, 0, $pos), substr($class, $pos + 1)];
    }
}

if (! function_exists('view_exist')) {
    /**
     * Verifie si un fichier de vue existe. Utile pour limiter les failles include
     */
    function view_exist(string $name, string $ext = '.php'): bool
    {
        $ext  = str_replace('.', '', $ext);
        $name = str_replace(VIEW_PATH, '', $name);
        $name = preg_match('#\.' . $ext . '$#', $name) ? $name : $name . '.' . $ext;

        return is_file(VIEW_PATH . rtrim($name, DS));
    }
}

if (! function_exists('view')) {
    /**
     * Charge une vue
     *
     * @return \BlitzPHP\View\View
     */
    function view(string $view, ?array $data = [], ?array $options = [])
    {
        $object = Services::viewer(false);

        $object->addData($data)->setOptions($options);

        return $object->display($view);
    }
}

if (! function_exists('flash')) {
    /**
     * Fournisseur d'acces rapide a la classe PHP Flash
     *
     * @return FlashMessages|string
     */
    /*
    function flash()
    {
         @var FlashMessages $flash
        $flash = service(FlashMessages::class);

        $params = func_get_args();
        $type = array_shift($params);

        if (!empty($type)) {
            if (empty($params)) {
                if ($type === 'all') {
                    $type = null;
                }
                return $flash->display($type, false);
            }

            $message = array_shift($params);

            return $flash->add($message, $type, ...$params);
        }

        return $flash;
    }*/
}

if (! function_exists('geo_ip')) {
    /**
     * Recuperation des coordonnees (pays, ville, etc) d'un utilisateur en fonction de son ip
     */
    function geo_ip(?string $ip = null): ?array
    {
        return json_decode(file_get_contents('http://ip-api.com/json/' . $ip), true);
    }
}

if (! function_exists('to_stream')) {
    /**
     * Créez un nouveau flux basé sur le type d'entrée.
     *
     * Options est un tableau associatif pouvant contenir les clés suivantes :
     * - metadata : Tableau de métadonnées personnalisées.
     * - size : Taille du flux.
     *
     * @param bool|callable|float|int|\Iterator|\Psr\Http\Message\StreamInterface|resource|string|null $resource Données du corps de l'entité
     * @param array                                                                                    $options  Additional options
     *
     * @uses GuzzleHttp\Psr7\stream_for
     *
     * @throws \InvalidArgumentException si l'argument $resource n'est pas valide.
     *
     * @return \Psr\Http\Message\StreamInterface
     */
    function to_stream($resource = '', array $options = []): Psr\Http\Message\StreamInterface
    {
        return \GuzzleHttp\Psr7\Utils::streamFor($resource, $options);
    }
}

if (! function_exists('value')) {
    /**
     * Renvoie la valeur par défaut de la valeur donnée.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (! function_exists('with')) {
    /**
     * Renvoie la valeur donnée, éventuellement transmise via le rappel donné.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    function with($value, ?callable $callback = null)
    {
        return null === $callback ? $value : $callback($value);
    }
}
