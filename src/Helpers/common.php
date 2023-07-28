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
use BlitzPHP\Container\Services;
use BlitzPHP\Contracts\Database\ConnectionInterface;
use BlitzPHP\Exceptions\PageNotFoundException;
use BlitzPHP\Http\Redirection;
use BlitzPHP\Http\ServerRequest;
use BlitzPHP\Http\Uri;
use BlitzPHP\Loader\Load;
use BlitzPHP\Session\Session;
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\Utilities\Iterable\Collection;
use BlitzPHP\Utilities\Support\Invader;
use Kint\Kint;

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
     * Chargera TOUS les helpers du nom correspondant, dans l'ordre suivant :
     *   1. app/Helpers
     *   2. {namespace}/Helpers
     *   3. system/Helpers
     */
    function helper(array|string $filenames)
    {
        Load::helper($filenames);
    }
}

if (! function_exists('model')) {
    /**
     * Simple maniere d'obtenir un modele.
     *
     * @template T of BlitzPHP\Models\BaseModel
     *
     * @param array<class-string<T>>|class-string<T> $name
     *
     * @return T
     */
    function model(string|array $name, ?ConnectionInterface &$conn = null)
    {
        return Load::model($name, $conn);
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
        // Assurez-vous qu'il ne s'agit PAS d'une instance partagée
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
        throw PageNotFoundException::pageNotFound($message);
    }
}

if (! function_exists('config')) {
    /**
     * GET/SET App config
     *
     * @param mixed $value
     *
     * @return Config|mixed
     */
    function config(?string $key = null, $value = null, bool $force_set = false)
    {
		$config = Services::config();

		if (empty($key)) {
			return $config;
		}

        if (! empty($value) || (empty($value) && true === $force_set)) {
            $config->set($key, $value);
        }

        return $config->get($key);
    }
}

// =========================== FONCTIONS DE PREVENTION D'ATTAQUE =========================== //

if (! function_exists('esc')) {
    /**
     * Effectue un simple échappement automatique des données pour des raisons de sécurité.
     * Pourrait envisager de rendre cela plus complexe à une date ultérieure.
     *
     * Si $data est une chaîne, il suffit alors de l'échapper et de la renvoyer.
     * Si $data est un tableau, alors il boucle dessus, s'échappant de chaque
     * 'valeur' des paires clé/valeur.
     *
     * Valeurs de contexte valides : html, js, css, url, attr, raw, null
     *
     * @param array|string $data
     *
     * @return array|string
     *
     * @throws InvalidArgumentException
     */
    function esc($data, ?string $context = 'html', ?string $encoding = null)
    {
        if (class_exists('\Laminas\Escaper\Escaper')) {
            return Helpers::esc($data, $context, $encoding);
        }

        return h($data, true, $encoding);
    }
}

if (! function_exists('h')) {
    /**
     * Méthode pratique pour htmlspecialchars.
     *
     * @param mixed       $text    Texte à envelopper dans htmlspecialchars. Fonctionne également avec des tableaux et des objets.
     *                             Les tableaux seront mappés et tous leurs éléments seront échappés. Les objets seront transtypés s'ils
     *                             implémenter une méthode `__toString`. Sinon, le nom de la classe sera utilisé.
     *                             Les autres types de scalaires seront renvoyés tels quels.
     * @param bool        $double  Encodez les entités html existantes.
     * @param string|null $charset Jeu de caractères à utiliser lors de l'échappement. La valeur par défaut est la valeur de configuration dans `mb_internal_encoding()` ou 'UTF-8'.
     *
     * @return mixed Texte enveloppé.
     */
    function h($text, bool $double = true, ?string $charset = null)
    {
        return Helpers::h($text, $double, $charset);
    }
}

if (! function_exists('purify')) {
    /**
     * Purifiez l'entrée à l'aide de la classe autonome HTMLPurifier.
     * Utilisez facilement plusieurs configurations de purificateur.
     *
     * @param string|string[]
     * @param false|string
     * @param mixed $dirty_html
     * @param mixed $config
     *
     * @return string|string[]
     */
    function purify($dirty_html, $config = false)
    {
        return Helpers::purify($dirty_html, $config);
    }
}

if (! function_exists('remove_invisible_characters')) {
    /**
     * Supprimer les caractères invisibles
     *
     * Cela empêche de prendre en sandwich des caractères nuls
     * entre les caractères ascii, comme Java\0script.
     */
    function remove_invisible_characters(string $str, bool $url_encoded = true): string
    {
        return Helpers::removeInvisibleCharacters($str, $url_encoded);
    }
}

if (! function_exists('stringify_attributes')) {
    /**
     * Chaîner les attributs à utiliser dans les balises HTML.
     *
     * @param array|object|string $attributes
     */
    function stringify_attributes($attributes, bool $js = false): string
    {
        return Helpers::stringifyAttributes($attributes, $js);
    }
}

// ================================= FONCTIONS D'ENVIRONNEMENT D'EXECUTION ================================= //

if (! function_exists('on_dev')) {
    /**
     * Testez pour voir si nous sommes dans un environnement de développement.
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
        return Helpers::isCli();
    }
}

if (! function_exists('is_php')) {
    /**
     * Détermine si la version actuelle de PHP est égale ou supérieure à la valeur fournie.
     */
    function is_php(string $version): bool
    {
        return Helpers::isPhp($version);
    }
}

if (! function_exists('is_windows')) {
    /**
     * Déterminez si l'environnement actuel est basé sur Windows.
     */
    function is_windows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
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
        if (preg_match('#^' . base_url() . '#i', $name)) {
            return true;
        }

        return ! preg_match('#^(https?://)#i', $name);
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

if (! function_exists('is_connected')) {
    /**
     * Verifie si l'utilisateur a une connexion internet active.
     */
    function is_connected(): bool
    {
        return Helpers::isConnected();
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

if (! function_exists('redirection')) {
    /**
     * Redirige l'utilisateur
     */
    function redirection(string $uri = '', string $method = 'location', ?int $code = 302)
    {
        $response = redirect()->to($uri, $code, $method);

        Services::emitter()->emitHeaders($response);

        exit(EXIT_SUCCESS);
    }
}

if (! function_exists('redirect')) {
    /**
     * Méthode pratique qui fonctionne avec la $request globale actuelle et
     * l'instance $router à rediriger à l'aide de routes nommées et le routage inversé
     * pour déterminer l'URL à laquelle aller. Si rien n'est trouvé, traitera
     * comme une redirection traditionnelle et passez la chaîne, en laissant
     * $redirection->redirect() détermine la méthode et le code corrects.
     *
     * Si plus de contrôle est nécessaire, vous devez utiliser explicitement $response->redirect.
     */
    function redirect(?string $uri = null): Redirection
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
            return '';
        }

        return site_url($url);
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
        $path = realpath($path) ?: $path;

        switch (true) {
            case strpos($path, APP_PATH) === 0:
                return 'APP_PATH' . DS . substr($path, strlen(APP_PATH));

            case strpos($path, SYST_PATH) === 0:
                return 'SYST_PATH' . DS . substr($path, strlen(SYST_PATH));
            
            case defined('VENDOR_PATH') && strpos($path, VENDOR_PATH . 'blitz-php' . DS) === 0:
                return 'BLITZ_PATH' . DS . substr($path, strlen(VENDOR_PATH . 'blitz-php' . DS));

            case defined('VENDOR_PATH') && strpos($path, VENDOR_PATH) === 0:
                return 'VENDOR_PATH' . DS . substr($path, strlen(VENDOR_PATH));

            case strpos($path, ROOTPATH) === 0:
                return 'ROOTPATH' . DS . substr($path, strlen(ROOTPATH));

            default:
                return $path;
        }
    }
}

if (! function_exists('old')) {
    /**
     * Fournit l'accès à "entrée ancienne" qui a été définie dans la session lors d'un redirect()-withInput().
     *
     * @param false|string $escape
     * @phpstan-param false|'attr'|'css'|'html'|'js'|'raw'|'url' $escape
     *
     * @return array|string|null
     */
    function old(string $key, ?string $default = null, $escape = 'html')
    {
        // Assurez-vous de charger la session
        if (session_status() === PHP_SESSION_NONE && ! on_test()) {
            session(); // @codeCoverageIgnore
        }

        // Retourne la valeur par défaut si rien n'a été trouvé dans l'ancien input.
        if (null === $value = Services::request()->old($key)) {
            return $default;
        }

        return $escape === false ? $value : esc($value, $escape);
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
        if (class_exists('\Kint\Kint')) {
            Kint::$aliases[] = 'dd';
            Kint::dump(...$vars);
        }

        exit;
    }
}

if (! function_exists('dump')) {
    /**
     * Prints a Kint debug report and exits.
     *
     * @param array ...$vars
     *
     * @codeCoverageIgnore Can't be tested ... exits
     */
    function dump(...$vars)
    {
        if (class_exists('\Kint\Kint')) {
            Kint::$aliases[] = 'dump';
            Kint::dump(...$vars);
        }
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

if (! function_exists('session')) {
    /**
     * Une méthode pratique pour accéder à l'instance de session, ou un élément qui a été défini dans la session.
     *
     * Exemples:
     *    session()->set('foo', 'bar');
     *    $foo = session('bar');
     *
     * @return array|bool|float|int|object|Session|string|null
     */
    function session(?string $val = null)
    {
        $session = Services::session();

        // Vous retournez un seul element ?
        if (is_string($val)) {
            return $session->get($val);
        }

        return $session;
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
     * @return string|void
     */
    function vd(...$params)
    {
        // return 	Helpers::r(...$params);
    }
}

if (! function_exists('vdt')) {
    /**
     * Shortcut to ref, plain text mode
     *
     * @return string|void
     */
    function vdt(...$params)
    {
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
     * @credit CodeIgniter <a href="http://codeigniter.com/">helpers force_https() - /system/Common.php</a>
     *
     * Non testable, car il sortira !
     *
     * @codeCoverageIgnore
     */
    function force_https(int $duration = 31536000, ?ServerRequest $request = null, ?Redirection $response = null)
    {
        if (null === $request) {
            $request = Services::request();
        }
        if (null === $response) {
            $response = Services::redirection();
        }

        if (is_cli() || $request->is('ssl')) {
            return;
        }

        // Si la bibliothèque de session est chargée, nous devons régénérer
        // l'ID de session pour des raisons de sécurité.
        Services::session()->regenerate();

        $baseURL = base_url();

        if (strpos($baseURL, 'http://') === 0) {
            $baseURL = (string) substr($baseURL, strlen('http://'));
        }

        $uri = Uri::createURIString(
            'https',
            $baseURL,
            $request->getUri()->getPath(), // Les URI absolus doivent utiliser un "/" pour un chemin vide
            $request->getUri()->getQuery(),
            $request->getUri()->getFragment()
        );

        // Définir un en-tête HSTS
        $response = $response->to($uri)->withHeader('Strict-Transport-Security', 'max-age=' . $duration);

        Services::emitter()->emitHeaders($response);

        exit(EXIT_SUCCESS);
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
    function get_type_name($var): string
    {
        return Helpers::typeName($var);
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
        return Helpers::isReallyWritable($file);
    }
}

if (! function_exists('lang')) {
    /**
     * Une méthode pratique pour traduire une chaîne ou un tableau d'entrées et formater
     * le résultat avec le MessageFormatter de l'extension intl.
     */
    function lang(string $line, array $args = [], ?string $locale = null): string
    {
        return Services::language($locale)->getLine($line, $args);
    }
}

if (! function_exists('__')) {
    /**
     * Une méthode pratique pour traduire une chaîne ou un tableau d'entrées et formater
     * le résultat avec le MessageFormatter de l'extension intl.
     */
    function __(string $line, array $args = [], ?string $locale = null): string
    {
        $tranlation = lang('App.' . $line, $args, $locale);

        return preg_replace('/^(App\.)/i', '', $tranlation);
    }
}

if (! function_exists('namespace_split')) {
    /**
     * Séparez l'espace de noms du nom de classe.
     *
     * Couramment utilisé comme `list($namespace, $className) = namespaceSplit($class);`.
     *
     * @param string $class Le nom complet de la classe, ie `BlitzPHP\Http\Request`.
     *
     * @return array Tableau avec 2 index. 0 => namespace, 1 => classname.
     */
    function namespace_split(string $class): array
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
     * @return \Psr\Http\Message\StreamInterface
     *
     * @throws \InvalidArgumentException si l'argument $resource n'est pas valide.
     */
    function to_stream($resource = '', array $options = []): Psr\Http\Message\StreamInterface
    {
        return \GuzzleHttp\Psr7\Utils::streamFor($resource, $options);
    }
}

if (! function_exists('value')) {
    /**
     * Renvoie la valeur par défaut de la valeur donnée.
     */
    function value(mixed $value, ...$args): mixed
    {
        return Helpers::value($value, ...$args);
    }
}

if (! function_exists('collect')) {
    /**
     * Créez une collection à partir de la valeur donnée.
     */
    function collect(mixed $value = null): Collection
    {
        return Helpers::collect($value);
    }
}

if (! function_exists('with')) {
    /**
     * Renvoie la valeur donnée, éventuellement transmise via le rappel donné.
     *
     * @param mixed $value
     */
    function with($value, ?callable $callback = null): mixed
    {
        Helpers::with($value, $callback);
    }
}

if (! function_exists('tap')) {
    /**
     * Appelez la Closure donnée avec cette instance puis renvoyez l'instance.
     */
    function tap(mixed $value, ?callable $callback = null): mixed
    {
        return Helpers::tap($value, $callback);
    }
}

if (! function_exists('last')) {
    /**
     * Recupere le dernier element d'un tableau
     */
    function last(array|object $array)
    {
        return end($array);
    }
}

if (! function_exists('invade')) {
    /**
     * Cette classe offre une fonction d'invasion qui vous permettra de lire / écrire des propriétés privées d'un objet.
     * Il vous permettra également de définir, obtenir et appeler des méthodes privées.
     *
     * @return Invader
     *
     * @see https://github.com/spatie/invade/blob/main/src/Invader.php
     */
    function invade(object $object)
    {
        return Invader::make($object);
    }
}
