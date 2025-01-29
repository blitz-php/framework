<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Cache\Cache;
use BlitzPHP\Cli\Console\Console;
use BlitzPHP\Config\Config;
use BlitzPHP\Container\Services;
use BlitzPHP\Contracts\Database\ConnectionInterface;
use BlitzPHP\Contracts\Http\StatusCode;
use BlitzPHP\Contracts\Session\CookieInterface;
use BlitzPHP\Contracts\Session\CookieManagerInterface;
use BlitzPHP\Debug\Logger;
use BlitzPHP\Exceptions\PageNotFoundException;
use BlitzPHP\Exceptions\RedirectException;
use BlitzPHP\Http\Redirection;
use BlitzPHP\Http\ServerRequest;
use BlitzPHP\Loader\Load;
use BlitzPHP\Session\Store;
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\Utilities\Iterable\Collection;
use BlitzPHP\Utilities\Support\Invader;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;

// ================================= FONCTIONS UTIILITAIRES ESSENTIELLES ================================= //

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
        if (is_string($value = Helpers::env($key, $default)) && trim($value) === '') {
            $value = $default;
        }

        return $value;
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
    function helper(array|string $filenames): void
    {
        Load::helper($filenames);
    }
}

if (! function_exists('model')) {
    /**
     * Simple maniere d'obtenir un modele.
     *
     * @template T
     *
     * @param class-string<T>|list<class-string<T>> $name
     *
     * @return T
     */
    function model(array|string $name, ?ConnectionInterface &$conn = null)
    {
        return Load::model($name, $conn);
    }
}

if (! function_exists('service')) {
    /**
     * Permet un accès plus propre au fichier de configuration des services.
     * Renvoie toujours une instance SHARED de la classe, donc l'appel de la fonction plusieurs fois renvera toujours la même instance.
     *
     * Ceux-ci sont égaux :
     *  - $cache = service('cache')
     *  - $cache = \BlitzPHP\Container\Services::cache();
     *
     * @template T
     *
     * @param class-string<T> $name
     *
     * @return object|T
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
     *
     * @template T
     *
     * @param class-string<T> $name
     *
     * @return object|T
     */
    function single_service(string $name, ...$params)
    {
        // Assurez-vous qu'il ne s'agit PAS d'une instance partagée
        $params[] = false;

        return service($name, ...$params);
    }
}

if (! function_exists('show404')) {
    /**
     * Afficher une page 404 introuvable dans le navigateur
     */
    function show404(string $message = 'The page you requested was not found.', string $heading = 'Page Not Found', array $params = []): never
    {
        throw PageNotFoundException::pageNotFound($message);
    }
}

if (! function_exists('command')) {
    /**
     * Exécute une seule commande.
     * Entrée attendue dans une seule chaîne comme celle qui serait utilisée sur la ligne de commande elle-même :
     *
     *  > command('migrate:create SomeMigration');
     *
     * @see https://github.com/codeigniter4/CodeIgniter4/blob/b56c85c9d09fd3b34893220b2221ed27f8d508e6/system/Common.php#L133
     *
     * @return false|string
     */
    function command(string $command)
    {
        $regexString = '([^\s]+?)(?:\s|(?<!\\\\)"|(?<!\\\\)\'|$)';
        $regexQuoted = '(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\')';

        $args   = [];
        $length = strlen($command);
        $cursor = 0;

        /**
         * Adopté de `StringInput::tokenize()` de Symfony avec quelques modifications.
         *
         * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Console/Input/StringInput.php
         */
        while ($cursor < $length) {
            if (preg_match('/\s+/A', $command, $match, 0, $cursor)) {
                // Rien a faire
            } elseif (preg_match('/' . $regexQuoted . '/A', $command, $match, 0, $cursor)) {
                $args[] = stripcslashes(substr($match[0], 1, strlen($match[0]) - 2));
            } elseif (preg_match('/' . $regexString . '/A', $command, $match, 0, $cursor)) {
                $args[] = stripcslashes($match[1]);
            } else {
                // @codeCoverageIgnoreStart
                throw new InvalidArgumentException(sprintf(
                    'Impossible d\'analyser l\'entrée à proximité "... %s ...".',
                    substr($command, $cursor, 10)
                ));
                // @codeCoverageIgnoreEnd
            }

            $cursor += strlen($match[0]);
        }

        $command = array_shift($args);
        $params  = [];

        foreach ($args as $key => $arg) {
            if (mb_strpos($arg, '--') !== false) {
                unset($args[$key]);
                [$arg, $v]          = explode('=', $arg) + [1 => true];
                $params[trim($arg)] = is_string($v) ? trim($v) : $v;
            }
        }

        ob_start();

        service(Console::class)->call($command, $args, $params);

        return ob_get_clean();
    }
}

if (! function_exists('config')) {
    /**
     * GET/SET App config
     *
     * @param mixed|null $default
     *
     * @return Config|mixed|void
     */
    function config(array|string|null $key = null, $default = null)
    {
        /** @var Config */
        $config = service('config');

        if (null === $key) {
            return $config;
        }

        if (is_string($key)) {
            return $config->get($key, $default);
        }

        foreach ($key as $k => $v) {
            if (is_string($k)) {
                $config->set($k, $v);
            }
        }

        return null;
    }
}

if (! function_exists('logger')) {
    /**
     * Une méthode de commodité pour les événements de journalisation via le système Log.
     *
     * Les niveaux de journal autorisés sont :
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
     * @return Logger|void
     */
    function logger($level = null, ?string $message = null, array $context = [])
    {
        /** @var Logger */
        $logger = service('logger');

        if (empty($level) || $message === null) {
            return $logger;
        }

        $logger->log($level, $message, $context);
    }
}

if (! function_exists('cache')) {
    /**
     * Une méthode pratique qui donne accès au cache
     * objet. Si aucun paramètre n'est fourni, renverra l'objet,
     * sinon, tentera de renvoyer la valeur mise en cache.
     *
     * Exemples:
     *    cache()->set('foo', 'bar'); ou cache('foo', 'bar');
     *    $foo = cache('bar');
     *
     * @param mixed|null $value
     *
     * @return bool|Cache|mixed
     */
    function cache(?string $key = null, $value = null)
    {
        /** @var Cache */
        $cache = service('cache');

        if ($key === null) {
            return $cache;
        }

        if (empty($value)) {
            return $cache->get($key);
        }

        return $cache->set($key, $value);
    }
}

if (! function_exists('cookie')) {
    /**
     * Une méthode pratique qui donne accès à l'objet cookie.
     * Si aucun paramètre n'est fourni, renverra l'objet,
     * sinon, tentera de renvoyer la valeur du cookie.
     *
     * Exemples:
     *    cookie()->make('foo', 'bar'); ou cookie('foo', 'bar');
     *    $foo = cookie('bar')
     *
     * @return CookieInterface|CookieManagerInterface|null
     */
    function cookie(?string $name = null, array|string|null $value = null, int $minutes = 0, array $options = [])
    {
        /** @var CookieManagerInterface */
        $cookie = service('cookie');

        if (null === $name) {
            return $cookie;
        }

        if (null === $value) {
            return $cookie->get($name);
        }

        return $cookie->make($name, $value, $minutes, $options);
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
     * @return array|bool|float|int|object|Store|string|null
     */
    function session(?string $val = null)
    {
        /** @var Store */
        $session = service('session');

        // Vous retournez un seul element ?
        if (is_string($val)) {
            return $session->get($val);
        }

        return $session;
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
     * @param list<string>|string $dirty_html
     * @param false|string        $config
     *
     * @return list<string>|string
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

// ================================= FONCTIONS DE FORMULAIRE ================================= //

if (! function_exists('csrf_token')) {
    /**
     * Renvoie la valeur de hachage actuelle pour la protection CSRF.
     * Peut être utilisé dans les vues lors de la construction manuelle d'input cachées, ou utilisé dans les variables javascript pour l'utilisation de l'API.
     */
    function csrf_token(): string
    {
        return session()->token();
    }
}

if (! function_exists('csrf_field')) {
    /**
     * Génère un champ input caché à utiliser dans les formulaires générés manuellement.
     */
    function csrf_field(?string $id = null): string
    {
        $name = config('security.csrf_token_name', '_token');

        return '<input type="hidden"' . ($id !== null && $id !== '' ? ' id="' . esc($id, 'attr') . '"' : '') . ' name="' . $name . '" value="' . csrf_token() . '">';
    }
}

if (! function_exists('csrf_meta')) {
    /**
     * Génère une balise méta à utiliser dans les appels javascript.
     */
    function csrf_meta(?string $id = null): string
    {
        $name = config('security.csrf_header_name', 'X-CSRF-TOKEN');

        return '<meta' . ($id !== null && $id !== '' ? ' id="' . esc($id, 'attr') . '"' : '') . ' name="' . $name . '" content="' . csrf_token() . '">';
    }
}

if (! function_exists('method_field')) {
    /**
     * Générer un champ de formulaire pour usurper le verbe HTTP utilisé par les formulaires.
     */
    function method_field(string $method): string
    {
        if (! in_array($method = strtoupper($method), ['PUT', 'POST', 'DELETE', 'PATCH'], true)) {
            throw new InvalidArgumentException(sprintf('Methode %s invalide', $method));
        }

        return '<input type="hidden" name="_method" value="' . $method . '">';
    }
}

// ================================= FONCTIONS D'ENVIRONNEMENT D'EXECUTION ================================= //

if (! function_exists('environment')) {
    /**
     * Renvoi l'environnement d'execution actuel ou determine si on est dans un environnement specifie
     *
     * @return bool|string
     */
    function environment(array|string|null $env = null)
    {
        $current = env('ENVIRONMENT');
        if (empty($current) || $current === 'auto') {
            $current = config('app.environment');
        }

        if ($env === '' || $env === '0' || $env === [] || $env === null) {
            return $current;
        }

        $envMap = [
            'dev'     => 'development',
            'local'   => 'development',
            'prod'    => 'production',
            'test'    => 'testing',
            'stage'   => 'testing',
            'staging' => 'testing',
        ];

        $current = $envMap[$current] ?? $current;

        if (is_string($env)) {
            $env = [$env];
        }

        $env = array_map(static fn ($k) => $envMap[$k] ?? $k, $env);

        return in_array($current, $env, true);
    }
}

if (! function_exists('on_dev')) {
    /**
     * Testez pour voir si nous sommes dans un environnement de développement.
     */
    function on_dev(bool $checkOnline = false): bool
    {
        if ($checkOnline && is_online()) {
            return false;
        }

        return environment(['dev', 'development', 'local']);
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

        return environment(['prod', 'production']);
    }
}

if (! function_exists('on_test')) {
    /**
     * Testez pour voir si nous sommes dans un environnement de test
     */
    function on_test(): bool
    {
        return environment(['test', 'testing', 'stage', 'staging']);
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
        return service('request')->is('ssl');
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
        return service('request')->is('ajax');
    }
}

if (! function_exists('redirection')) {
    /**
     * Redirige l'utilisateur
     */
    function redirection(string $uri = '', string $method = 'location', ?int $code = 302): never
    {
        $response = redirect()->to($uri, $code, [], null, $method);

        service('emitter')->emitHeaders($response);

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
        $redirection = service('redirection');

        if ($uri !== null && $uri !== '') {
            return $redirection->route($uri);
        }

        return $redirection;
    }
}

if (! function_exists('back')) {
    /**
     * Retourne a la page precedente
     *
     * @param mixed $fallback
     */
    function back(int $code = 302, array $headers = [], $fallback = false): Redirection
    {
        return redirect()->back($code, $headers, $fallback);
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
        $url = service('routes')->reverseRoute($method, ...$params);

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

        return match (true) {
            str_starts_with($path, APP_PATH)                                                 => 'APP_PATH' . DS . substr($path, strlen(APP_PATH)),
            str_starts_with($path, SYST_PATH)                                                => 'SYST_PATH' . DS . substr($path, strlen(SYST_PATH)),
            defined('VENDOR_PATH') && str_starts_with($path, VENDOR_PATH . 'blitz-php' . DS) => 'BLITZ_PATH' . DS . substr($path, strlen(VENDOR_PATH . 'blitz-php' . DS)),
            defined('VENDOR_PATH') && str_starts_with($path, VENDOR_PATH)                    => 'VENDOR_PATH' . DS . substr($path, strlen(VENDOR_PATH)),
            str_starts_with($path, ROOTPATH)                                                 => 'ROOTPATH' . DS . substr($path, strlen(ROOTPATH)),
            default                                                                          => $path,
        };
    }
}

if (! function_exists('old')) {
    /**
     * Fournit l'accès à "entrée ancienne" qui a été définie dans la session lors d'un redirect()-withInput().
     *
     * @param         false|string                               $escape
     * @param         mixed|null                                 $default
     * @phpstan-param false|'attr'|'css'|'html'|'js'|'raw'|'url' $escape
     *
     * @return array|string|null
     */
    function old(string $key, $default = null, $escape = 'html')
    {
        // Assurez-vous de charger la session
        if (session_status() === PHP_SESSION_NONE && ! on_test()) {
            session(); // @codeCoverageIgnore
        }

        // Retourne la valeur par défaut si rien n'a été trouvé dans l'ancien input.
        if (null === $value = service('request')->old($key)) {
            return $default;
        }

        return $escape === false ? $value : esc($value, $escape);
    }
}

// ================================= FONCTIONS DE DEBOGAGE ================================= //

if (! function_exists('deprecationWarning')) {
    /**
     * Méthode d'assistance pour générer des avertissements d'obsolescence
     *
     * @param string $message    Le message à afficher comme avertissement d'obsolescence.
     * @param int    $stackFrame Le cadre de pile à inclure dans l'erreur. Par défaut à 1
     *                           car cela devrait pointer vers le code de l'application/du plugin.
     */
    function deprecation_warning(string $message, int $stackFrame = 1): void
    {
        Helpers::deprecationWarning($message, $stackFrame);
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
    function trigger_warning(string $message): void
    {
        Helpers::triggerWarning($message);
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
     * @throws RedirectException
     */
    function force_https(int $duration = 31536000, ?ServerRequest $request = null, ?Redirection $response = null): void
    {
        $request ??= service('request');
        $response ??= service('redirection');

        if (is_cli() || $request->is('ssl')) {
            return;
        }

        // Si la session est active, nous devons régénérer
        // l'ID de session pour des raisons de sécurité.
        if (! on_test() && session_status() === PHP_SESSION_ACTIVE) {
            session()->regenerate(); // @codeCoverageIgnore
        }

        $uri = (string) $request->getUri()->withScheme('https');

        // Définir un en-tête HSTS
        $response = $response->to($uri)
            ->withStatus(StatusCode::TEMPORARY_REDIRECT)
            ->withHeader('Strict-Transport-Security', 'max-age=' . $duration)
            ->withStringBody('');

        throw new RedirectException($response);
    }
}

if (! function_exists('get_type_name')) {
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
        return service('request')->clientIp();
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
        return service('translator', $locale)->getLine($line, $args);
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
    function view_exist(string $name, ?string $ext = null, array $options = []): bool
    {
        return service('viewer')->exists($name, $ext, $options);
    }
}

if (! function_exists('view')) {
    /**
     * Saisit la classe compatible avec le RendererInterface et lui demande d'effectuer le rendu de la vue spécifiée.
     * Fournit simplement une méthode de commodité qui peut être utilisée dans les contrôleurs, les bibliothèques et les routes sous forme de closure.
     *
     * NOTE : Ne fournit pas d'échappement des données, ce qui doit être géré manuellement par le développeur.
     *
     * @return BlitzPHP\View\View
     */
    function view(string $view, array $data = [], array $options = [])
    {
        return service('viewer')->make($view, $data, $options);
    }
}

if (! function_exists('component')) {
    /**
     * Les composants de vue sont utilisées dans les vues pour insérer des morceaux de HTML qui sont gérés par d'autres classes.
     *
     * @throws ReflectionException
     */
    function component(array|string $library, array|string|null $params = null, int $ttl = 0, ?string $cacheName = null): string
    {
        if (is_array($library)) {
            $library = implode('::', $library);
        }

        return service('componentLoader')->render($library, $params, $ttl, $cacheName);
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
     * @param bool|callable|float|int|Iterator|resource|StreamInterface|string|null $resource Données du corps de l'entité
     * @param array                                                                 $options  Additional options
     *
     * @uses GuzzleHttp\Psr7\stream_for
     *
     * @throws InvalidArgumentException si l'argument $resource n'est pas valide.
     */
    function to_stream($resource = '', array $options = []): StreamInterface
    {
        return Utils::streamFor($resource, $options);
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
        return Helpers::with($value, $callback);
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
     *
     * @template T
     *
     * @param list<T> $array
     *
     * @return false|T
     */
    function last(array $array)
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
