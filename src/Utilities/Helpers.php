<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Utilities;

use Exception;
use HTMLPurifier;
use HTMLPurifier_Config;
use InvalidArgumentException;

class Helpers
{
    /**
     * Détermine si la version actuelle de PHP est égale ou supérieure à la valeur fournie
     */
    public static function isPhp(string $version): bool
    {
        static $_is_php;

        if (! isset($_is_php[$version])) {
            $_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
        }

        return $_is_php[$version];
    }

    /**
     * Tester si une application s'exécute en local ou en ligne
     */
    public static function isOnline(): bool
    {
        $host = explode(':', $_SERVER['HTTP_HOST'] ?? '')[0];

        return
            ! empty($host) // Si c'est vide, ca veut certainement dire qu'on est en CLI, or le CLI << n'est pas >> utilisé en ligne
            && ! in_array($host, ['localhost', '127.0.0.1'], true)
            && ! preg_match('#\.dev$#', $host)
            && ! preg_match('#\.test$#', $host)
            && ! preg_match('#\.lab$#', $host)
            && ! preg_match('#\.loc(al)?$#', $host)
            && ! preg_match('#^192\.168#', $host);
    }

    /**
     * Tests d'inscriptibilité des fichiers
     *
     * is_writable() renvoie TRUE sur les serveurs Windows lorsque vous ne pouvez vraiment pas écrire
     * le fichier, basé sur l'attribut en lecture seule. is_writable() n'est pas non plus fiable
     * sur les serveurs Unix si safe_mode est activé.
     *
     * @see https://bugs.php.net/bug.php?id=54709
     *
     * @throws Exception
     * @codeCoverageIgnore Pas pratique à tester, car travis fonctionne sous linux
     */
    public static function isReallyWritable(string $file): bool
    {
        // If we're on a Unix server with safe_mode off we call is_writable
        if (DIRECTORY_SEPARATOR === '/' || ! ini_get('safe_mode')) {
            return is_writable($file);
        }

        /* Pour les serveurs Windows et les installations safe_mode "on", nous allons en fait
         * écrire un fichier puis le lire. Bah...
         */
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . bin2hex(random_bytes(16));
            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }

            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);

            return true;
        }
        if (! is_file($file) || ($fp = @fopen($file, 'ab')) === false) {
            return false;
        }

        fclose($fp);

        return true;
    }

    public static function cleanUrl(string $url): string
    {
        $path  = parse_url($url);
        $query = '';

        if (! empty($path['host'])) {
            $r = $path['scheme'] . '://';
            if (! empty($path['user'])) {
                $r .= $path['user'];
                if (! empty($path['pass'])) {
                    $r .= ':' . $path['pass'] . '@';
                }
                $r .= '@';
            }
            if (! empty($path['host'])) {
                $r .= $path['host'];
            }
            if (! empty($path['port'])) {
                $r .= ':' . $path['port'];
            }
            $url = $r . $path['path'];
            if (! empty($path['query'])) {
                $query = '?' . $path['query'];
            }
        }
        $url = str_replace('/./', '/', $url);

        while (substr_count($url, '../')) {
            $url = preg_replace('!/([\\w\\d]+/\\.\\.)!', '', $url);
        }

        return $url . $query;
    }

    /**
     * Supprimer les caractères invisibles
     *
     * Cela empêche de prendre en sandwich des caractères nuls
     * entre les caractères ascii, comme Java\0script.
     */
    public static function removeInvisibleCharacters(string $str, bool $url_encoded = true): string
    {
        $non_displayables = [];

        if ($url_encoded) {
            $non_displayables[] = '/%0[0-8bcef]/i';	// url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-f]/i';	// url encoded 16-31
            $non_displayables[] = '/%7f/i';	// url encoded 127
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

        do {
            $str = preg_replace($non_displayables, '', $str, -1, $count);
        } while ($count);

        return $str;
    }

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
     * @throws InvalidArgumentException
     *
     * @return array|string
     */
    public static function esc($data, ?string $context = 'html', ?string $encoding = null)
    {
        if (is_array($data)) {
            foreach ($data as $key => &$value) {
                $value = self::esc($value, $context);
            }
        }

        if (is_string($data)) {
            $context = strtolower($context);

            // Fournit un moyen de NE PAS échapper aux données depuis
            // cela pourrait être appelé automatiquement par
            // la bibliothèque View.
            if (empty($context) || $context === 'raw') {
                return $data;
            }

            if (! in_array($context, ['html', 'js', 'css', 'url', 'attr'], true)) {
                throw new InvalidArgumentException('Invalid escape context provided.');
            }

            if ($context === 'attr') {
                $method = 'escapeHtmlAttr';
            } else {
                $method = 'escape' . ucfirst($context);
            }

            static $escaper;
            if (! $escaper) {
                $escaper = new \Laminas\Escaper\Escaper($encoding);
            }

            if ($encoding && $escaper->getEncoding() !== $encoding) {
                $escaper = new \Laminas\Escaper\Escaper($encoding);
            }

            $data = $escaper->{$method}($data);
        }

        return $data;
    }

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
     * @credit CackePHP (https://cakephp.org)
     */
    public static function h($text, bool $double = true, ?string $charset = null)
    {
        if (is_string($text)) {
            // optimize for strings
        } elseif (is_array($text)) {
            $texts = [];

            foreach ($text as $k => $t) {
                $texts[$k] = self::h($t, $double, $charset);
            }

            return $texts;
        } elseif (is_object($text)) {
            if (method_exists($text, '__toString')) {
                $text = (string) $text;
            } else {
                $text = '(object)' . get_class($text);
            }
        } elseif ($text === null || is_scalar($text)) {
            return $text;
        }

        static $defaultCharset = false;
        if ($defaultCharset === false) {
            $defaultCharset = mb_internal_encoding();
            if ($defaultCharset === null) {
                $defaultCharset = 'UTF-8';
            }
        }
        if (is_string($double)) {
            self::deprecationWarning(
                'Passing charset string for 2nd argument is deprecated. ' .
                'Use the 3rd argument instead.'
            );
            $charset = $double;
            $double  = true;
        }

        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, $charset ?: $defaultCharset, $double);
    }

    /**
     * Garantit qu'une extension se trouve à la fin d'un nom de fichier
     */
    public static function ensureExt(string $path, string $ext = 'php'): string
    {
        if ($ext) {
            $ext = '.' . preg_replace('#^\.#', '', $ext);

            if (substr($path, -strlen($ext)) !== $ext) {
                $path .= $ext;
            }
        }

        return trim($path);
    }

    /**
     * Purifiez l'entrée à l'aide de la classe autonome HTMLPurifier.
     * Utilisez facilement plusieurs configurations de purificateur.
     *
     * @param string|string[] $dirty_html
     * @param false|string    $config
     *
     * @return string|string[]
     */
    public static function purify($dirty_html, $config = false, string $charset = 'UTF-8')
    {
        if (is_array($dirty_html)) {
            foreach ($dirty_html as $key => $val) {
                $clean_html[$key] = self::purify($val, $config);
            }
        } else {
            switch ($config) {

                case 'comment':
                    $config = HTMLPurifier_Config::createDefault();
                    $config->set('Core.Encoding', $charset);
                    $config->set('HTML.Doctype', 'XHTML 1.0 Strict');
                    $config->set('HTML.Allowed', 'p,a[href|title],abbr[title],acronym[title],b,strong,blockquote[cite],code,em,i,strike');
                    $config->set('AutoFormat.AutoParagraph', true);
                    $config->set('AutoFormat.Linkify', true);
                    $config->set('AutoFormat.RemoveEmpty', true);
                    break;

                case false:
                    $config = HTMLPurifier_Config::createDefault();
                    $config->set('Core.Encoding', $charset);
                    $config->set('HTML.Doctype', 'XHTML 1.0 Strict');
                    break;

                default:
                    throw new InvalidArgumentException('The HTMLPurifier configuration labeled "' . htmlspecialchars($config, ENT_QUOTES, $charset) . '" could not be found.');
            }

            $purifier   = new HTMLPurifier($config);
            $clean_html = $purifier->purify($dirty_html);
        }

        return $clean_html;
    }

    /**
     * Chaîner les attributs à utiliser dans les balises HTML.
     *
     * Fonction d'assistance utilisée pour convertir une chaîne, un tableau ou un objet
     * d'attributs à une chaîne.
     *
     * @param array|object|string $attributes
     */
    public static function stringifyAttributes($attributes, bool $js = false): string
    {
        $atts = '';

        if (empty($attributes)) {
            return $atts;
        }

        if (is_string($attributes)) {
            return ' ' . $attributes;
        }

        $attributes = (array) $attributes;

        foreach ($attributes as $key => $val) {
            $atts .= ($js) ? $key . '=' . self::esc($val, 'js') . ',' : ' ' . $key . '="' . self::esc($val, 'attr') . '"';
        }

        return rtrim($atts, ',');
    }

    /**
     * Obtient une variable d'environnement à partir des sources disponibles et fournit une émulation
     * pour les variables d'environnement non prises en charge ou incohérentes (c'est-à-dire DOCUMENT_ROOT sur
     * IIS, ou SCRIPT_NAME en mode CGI). Expose également quelques coutumes supplémentaires
     * informations sur l'environnement.
     *
     * @param string     $key     Nom de la variable d'environnement
     * @param mixed|null $default
     *
     * @return string Paramétrage des variables d'environnement.
     * @credit CakePHP - http://book.cakephp.org/2.0/en/core-libraries/global-constants-and-functions.html#env
     */
    public static function env(string $key, $default = null)
    {
        if ($key === 'HTTPS') {
            if (isset($_SERVER['HTTPS'])) {
                return ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            }
            if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
                return ! empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https';
            }
            if (isset($_SERVER['HTTP_FRONT_END_HTTPS'])) {
                return ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off';
            }

            return strpos(self::env('SCRIPT_URI'), 'https://') === 0;
        }

        if ($key === 'SCRIPT_NAME') {
            if (self::env('CGI_MODE') && isset($_ENV['SCRIPT_URL'])) {
                $key = 'SCRIPT_URL';
            }
        }

        $val = null;
        if (isset($_SERVER[$key])) {
            $val = $_SERVER[$key];
        } elseif (isset($_ENV[$key])) {
            $val = $_ENV[$key];
        } elseif (getenv($key) !== false) {
            $val = getenv($key);
        }

        if ($key === 'REMOTE_ADDR' && $val === self::env('SERVER_ADDR')) {
            $addr = self::env('HTTP_PC_REMOTE_ADDR');
            if ($addr !== null) {
                $val = $addr;
            }
        }

        if ($val !== null) {
            return $val;
        }

        switch ($key) {
            case 'DOCUMENT_ROOT':
                $name     = self::env('SCRIPT_NAME');
                $filename = self::env('SCRIPT_FILENAME');
                $offset   = 0;
                if (! strpos($name, '.php')) {
                    $offset = 4;
                }

                return substr($filename, 0, -(strlen($name) + $offset));

            case 'PHP_SELF':
                return str_replace(self::env('DOCUMENT_ROOT'), '', self::env('SCRIPT_FILENAME'));

            case 'CGI_MODE':
                return PHP_SAPI === 'cgi';

            case 'HTTP_BASE':
                $host  = self::env('HTTP_HOST');
                $parts = explode('.', $host);
                $count = count($parts);

                if ($count === 1) {
                    return '.' . $host;
                }
                if ($count === 2) {
                    return '.' . $host;
                }
                if ($count === 3) {
                    $gTLD = [
                        'aero',
                        'asia',
                        'biz',
                        'cat',
                        'com',
                        'coop',
                        'edu',
                        'gov',
                        'info',
                        'int',
                        'jobs',
                        'mil',
                        'mobi',
                        'museum',
                        'name',
                        'net',
                        'org',
                        'pro',
                        'tel',
                        'travel',
                        'xxx',
                    ];
                    if (in_array($parts[1], $gTLD, true)) {
                        return '.' . $host;
                    }
                }
                array_shift($parts);

                return '.' . implode('.', $parts);
        }

        return $default;
    }

    /**
     * Recherche l'URL de base de l'application independamment de la configuration de l'utilisateur
     */
    public static function findBaseUrl(): string
    {
        if (isset($_SERVER['SERVER_ADDR'])) {
            $server_addr = $_SERVER['HTTP_HOST'] ?? ((strpos($_SERVER['SERVER_ADDR'], ':') !== false) ? '[' . $_SERVER['SERVER_ADDR'] . ']' : $_SERVER['SERVER_ADDR']);

            if (isset($_SERVER['SERVER_PORT'])) {
                $server_addr .= ':' . ((! preg_match('#:' . $_SERVER['SERVER_PORT'] . '$#', $server_addr)) ? $_SERVER['SERVER_PORT'] : '80');
            }

            if (
                (! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
                || (! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')
            ) {
                $base_url = 'https';
            } else {
                $base_url = 'http';
            }

            $base_url .= '://' . $server_addr . dirname(substr($_SERVER['SCRIPT_NAME'], 0, strpos($_SERVER['SCRIPT_NAME'], basename($_SERVER['SCRIPT_FILENAME']))));
        } else {
            $base_url = 'http://localhost:' . ($_SERVER['SERVER_PORT'] ?? '80');
        }

        return $base_url;
    }

    /**
     * Jolie fonction de commodité d'impression JSON.
     *
     * Dans les terminaux, cela agira de la même manière que json_encode() avec JSON_PRETTY_PRINT directement, lorsqu'il n'est pas exécuté sur cli
     * enveloppera également les balises <pre> autour de la sortie de la variable donnée. Similaire à pr().
     *
     * Cette fonction renvoie la même variable qui a été transmise.
     *
     * @param mixed $var Variable à imprimer.
     *
     * @return mixed le même $var qui a été passé à cette fonction
     *
     * @see pr()
     */
    public static function pj($var)
    {
        $template = (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') ? '<pre class="pj">%s</pre>' : "\n%s\n\n";
        printf($template, trim(json_encode($var, JSON_PRETTY_PRINT)));

        return $var;
    }

    /**
     * Méthode d'assistance pour générer des avertissements d'obsolescence
     *
     * @param string $message    Le message à afficher comme avertissement d'obsolescence.
     * @param int    $stackFrame Le cadre de pile à inclure dans l'erreur. Par défaut à 1
     *                           car cela devrait pointer vers le code de l'application/du plugin.
     *
     * @return void
     */
    public static function deprecationWarning(string $message, int $stackFrame = 1)
    {
        if (! (error_reporting() & E_USER_DEPRECATED)) {
            return;
        }

        $trace = debug_backtrace();
        if (isset($trace[$stackFrame])) {
            $frame = $trace[$stackFrame];
            $frame += ['file' => '[internal]', 'line' => '??'];

            $message = sprintf(
                '%s - %s, line: %s' . "\n" .
                ' You can disable deprecation warnings by setting `Error.errorLevel` to' .
                ' `E_ALL & ~E_USER_DEPRECATED` in your config/app.php.',
                $message,
                $frame['file'],
                $frame['line']
            );
        }

        @trigger_error($message, E_USER_DEPRECATED);
    }

    /**
     * Déclenche un E_USER_WARNING.
     */
    public static function triggerWarning(string $message)
    {
        $stackFrame = 1;
        $trace      = debug_backtrace();
        if (isset($trace[$stackFrame])) {
            $frame = $trace[$stackFrame];
            $frame += ['file' => '[internal]', 'line' => '??'];
            $message = sprintf(
                '%s - %s, line: %s',
                $message,
                $frame['file'],
                $frame['line']
            );
        }
        trigger_error($message, E_USER_WARNING);
    }

    /**
     * Divise un nom de plugin de syntaxe à points en son plugin et son nom de classe.
     * Si $name n'a pas de point, alors l'index 0 sera nul.
     *
     * Couramment utilisé comme
     * ```
     * list($plugin, $name) = Helpers::pluginSplit($name);
     * ```
     *
     * @param string      $name      Le nom que vous voulez diviser en plugin.
     * @param bool        $dotAppend Définir sur true si vous voulez que le plugin ait un '.' qui y est annexé.
     * @param string|null $plugin    Plugin optionnel par défaut à utiliser si aucun plugin n'est trouvé. La valeur par défaut est nulle.
     *
     * @return array Tableau avec 2 index. 0 => nom du plugin, 1 => nom de la classe.
     * @credit <a href="https://book.cakephp.org/4/en/core-libraries/global-constants-and-functions.html#pluginSplit">CakePHP</a>
     * @psalm-return array{string|null, string}
     */
    public static function pluginSplit(string $name, bool $dotAppend = false, ?string $plugin = null): array
    {
        if (strpos($name, '.') !== false) {
            $parts = explode('.', $name, 2);
            if ($dotAppend) {
                $parts[0] .= '.';
            }

            /** @psalm-var array{string, string}*/
            return $parts;
        }

        return [$plugin, $name];
    }

    /**
     * Séparez l'espace de noms du nom de classe.
     *
     * Couramment utilisé comme `list($namespace, $className) = Helpers::namespaceSplit($class);`.
     *
     * @param string $class Le nom complet de la classe, ie `BlitzPHP\Core\App`.
     *
     * @return array<string> Tableau avec 2 index. 0 => namespace, 1 => nom de la classe.
     */
    public static function namespaceSplit(string $class): array
    {
        $pos = strrpos($class, '\\');
        if ($pos === false) {
            return ['', $class];
        }

        return [substr($class, 0, $pos), substr($class, $pos + 1)];
    }
}
