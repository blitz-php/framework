<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Container\Services;
use BlitzPHP\Core\App;
use BlitzPHP\Exceptions\RouterException;
use BlitzPHP\Http\ServerRequest;
use BlitzPHP\Http\Uri;
use BlitzPHP\Http\UrlGenerator;
use BlitzPHP\Utilities\Helpers;

/**
 * FONCTIONS DE MANIPULATION D'URL
 *
 * @credit	<a href="https://codeigniter.com">CodeIgniter 4.2 - url_helper</a>
 */

// =================================  ================================= //

if (! function_exists('url')) {
    /**
     * Générer une url pour l'application.
     *
     * @return string|UrlGenerator
     */
    function url(?string $path = null, mixed $parameters = [], ?bool $secure = null)
    {
        /** @var UrlGenerator $generator */
        $generator = service(UrlGenerator::class);

        if (null === $path) {
            return $generator;
        }

        return $generator->to($path, $parameters, $secure);
    }
}

if (! function_exists('site_url')) {
    /**
     * Renvoie une URL de site telle que définie par la configuration de l'application.
     *
     * @param mixed $relativePath Chaîne d'URI ou tableau de segments d'URI
     */
    function site_url($relativePath = '', ?string $scheme = null): string
    {
        if (is_array($relativePath)) {
            $relativePath = implode('/', $relativePath);
        }

        $uri = App::getUri($relativePath);

        return Uri::createURIString(
            $scheme ?? $uri->getScheme(),
            $uri->getAuthority(),
            $uri->getPath(),
            $uri->getQuery(),
            $uri->getFragment()
        );
    }
}

if (! function_exists('base_url')) {
    /**
     * Renvoie l'URL de base telle que définie par la configuration de l'application.
     * Les URL de base sont des URL de site coupées sans la page d'index.
     *
     * @param mixed $relativePath Chaîne d'URI ou tableau de segments d'URI
     */
    function base_url($relativePath = '', ?string $scheme = null): string
    {
        $index_page = index_page();
        config()->set('app.index_page', '');

        $url = rtrim(site_url($relativePath, $scheme), '/');
        config()->set('app.index_page', $index_page);

        return $url;
    }
}

if (! function_exists('current_url')) {
    /**
     * Renvoie l'URL complète (y compris les segments) de la page où cette fonction est placée
     *
     * @param bool $returnObject True pour renvoyer un objet au lieu d'une chaîne
     *
     * @return string|Uri
     */
    function current_url(bool $returnObject = false, ?ServerRequest $request = null)
    {
        $request ??= Services::request();
        $path = $request->getPath();

        // Ajouter des chaine de requêtes et des fragments
        if (($query = $request->getUri()->getQuery()) !== '') {
            $path .= '?' . $query;
        }
        if (($fragment = $request->getUri()->getFragment()) !== '') {
            $path .= '#' . $fragment;
        }

        $uri = App::getUri($path);

        return $returnObject ? $uri : Uri::createURIString($uri->getScheme(), $uri->getAuthority(), $uri->getPath());
    }
}

if (! function_exists('previous_url')) {
    /**
     * Renvoie l'URL précédente sur laquelle se trouvait le visiteur actuel. Pour des raisons de sécurité
     * nous vérifions d'abord une variable de session enregistrée, si elle existe, et l'utilisons.
     * Si ce n'est pas disponible, cependant, nous utiliserons une URL épurée de $_SERVER['HTTP_REFERER']
     * qui peut être défini par l'utilisateur, il n'est donc pas fiable et n'est pas défini par certains navigateurs/serveurs.
     *
     * @return mixed|string|Uri
     */
    function previous_url(bool $returnObject = false)
    {
        $referer = url()->previous();

        return $returnObject ? Services::uri($referer) : $referer;
    }
}

if (! function_exists('uri_string')) {
    /**
     * Renvoie la partie chemin de l'URL actuelle
     *
     * @param bool $relative Si le chemin résultant doit être relatif à baseURL
     */
    function uri_string(bool $relative = false): string
    {
        return $relative
            ? ltrim(Services::request()->getPath(), '/')
            : Services::request()->getUri()->getPath();
    }
}

if (! function_exists('index_page')) {
    /**
     * Renvoie la "index_page" de votre fichier de configuration
     */
    function index_page(): string
    {
        return config('app.index_page');
    }
}

if (! function_exists('anchor')) {
    /**
     * Crée une ancre basée sur l'URL locale.
     *
     * @param string             $title      le titre du lien
     * @param array|false|string $attributes tous les attributs
     * @param mixed              $uri
     */
    function anchor($uri = '', string $title = '', $attributes = ''): string
    {
        $siteUrl = is_array($uri) ? site_url($uri, null) : (preg_match('#^(\w+:)?//#i', $uri) ? $uri : site_url($uri, null));
        $siteUrl = rtrim($siteUrl, '/');

        if ($title === '') {
            $title = $siteUrl;
        }

        if ($attributes !== '') {
            $attributes = stringify_attributes($attributes);
        }

        return '<a href="' . $siteUrl . '"' . $attributes . '>' . $title . '</a>';
    }
}

if (! function_exists('anchor_popup')) {
    /**
     * Lien d'ancrage - Version contextuelle
     *
     * Crée une ancre basée sur l'URL locale. Le lien
     * ouvre une nouvelle fenêtre basée sur les attributs spécifiés.
     *
     * @param string             $title      le titre du lien
     * @param array|false|string $attributes tous les attributs
     */
    function anchor_popup(string $uri = '', string $title = '', $attributes = false): string
    {
        $siteUrl = preg_match('#^(\w+:)?//#i', $uri) ? $uri : site_url($uri, null);
        $siteUrl = rtrim($siteUrl, '/');

        if ($title === '') {
            $title = $siteUrl;
        }

        if ($attributes === false) {
            return '<a href="' . $siteUrl . '" onclick="window.open(\'' . $siteUrl . "', '_blank'); return false;\">" . $title . '</a>';
        }

        if (! is_array($attributes)) {
            $attributes = [$attributes];

            // Ref: http://www.w3schools.com/jsref/met_win_open.asp
            $windowName = '_blank';
        } elseif (! empty($attributes['window_name'])) {
            $windowName = $attributes['window_name'];
            unset($attributes['window_name']);
        } else {
            $windowName = '_blank';
        }

        $atts = [];

        foreach (['width' => '800', 'height' => '600', 'scrollbars' => 'yes', 'menubar' => 'no', 'status' => 'yes', 'resizable' => 'yes', 'screenx' => '0', 'screeny' => '0'] as $key => $val) {
            $atts[$key] = $attributes[$key] ?? $val;
            unset($attributes[$key]);
        }

        $attributes = stringify_attributes($attributes);

        return '<a href="' . $siteUrl
                . '" onclick="window.open(\'' . $siteUrl . "', '" . $windowName . "', '" . stringify_attributes($atts, true) . "'); return false;\""
                . $attributes . '>' . $title . '</a>';
    }
}

if (! function_exists('mailto')) {
    /**
     * Lien Mailto
     *
     * @param string       $title      le titre du lien
     * @param array|string $attributes tous les attributs
     */
    function mailto(string $email, string $title = '', $attributes = ''): string
    {
        if (trim($title) === '') {
            $title = $email;
        }

        return '<a href="mailto:' . $email . '"' . stringify_attributes($attributes) . '>' . $title . '</a>';
    }
}

if (! function_exists('safe_mailto')) {
    /**
     * Lien Mailto codé
     *
     * Créer un lien mailto protégé contre les spams écrit en Javascript
     *
     * @param string $title      le titre du lien
     * @param mixed  $attributes tous les attributs
     */
    function safe_mailto(string $email, string $title = '', $attributes = ''): string
    {
        if (trim($title) === '') {
            $title = $email;
        }

        $x = str_split('<a href="mailto:', 1);

        for ($i = 0, $l = strlen($email); $i < $l; $i++) {
            $x[] = '|' . ord($email[$i]);
        }

        $x[] = '"';

        if ($attributes !== '') {
            if (is_array($attributes)) {
                foreach ($attributes as $key => $val) {
                    $x[] = ' ' . $key . '="';

                    for ($i = 0, $l = strlen($val); $i < $l; $i++) {
                        $x[] = '|' . ord($val[$i]);
                    }

                    $x[] = '"';
                }
            } else {
                for ($i = 0, $l = mb_strlen($attributes); $i < $l; $i++) {
                    $x[] = mb_substr($attributes, $i, 1);
                }
            }
        }

        $x[] = '>';

        $temp = [];

        for ($i = 0, $l = strlen($title); $i < $l; $i++) {
            $ordinal = ord($title[$i]);

            if ($ordinal < 128) {
                $x[] = '|' . $ordinal;
            } else {
                if ($temp === []) {
                    $count = ($ordinal < 224) ? 2 : 3;
                } else {
                    $count = 0;
                }

                $temp[] = $ordinal;

                if (count($temp) === $count) {
                    $number = ($count === 3) ? (($temp[0] % 16) * 4096) + (($temp[1] % 64) * 64) + ($temp[2] % 64) : (($temp[0] % 32) * 64) + ($temp[1] % 64);
                    $x[]    = '|' . $number;
                    $count  = 1;
                    $temp   = [];
                }
            }
        }

        $x[] = '<';
        $x[] = '/';
        $x[] = 'a';
        $x[] = '>';

        $x = array_reverse($x);

        // améliore l'obscurcissement en éliminant les retours à la ligne et les espaces
        $output = '<script type="text/javascript">'
                . 'var l=new Array();';

        foreach ($x as $i => $value) {
            $output .= 'l[' . $i . "] = '" . $value . "';";
        }

        return $output . ('for (var i = l.length-1; i >= 0; i=i-1) {'
                . "if (l[i].substring(0, 1) === '|') document.write(\"&#\"+unescape(l[i].substring(1))+\";\");"
                . 'else document.write(unescape(l[i]));'
                . '}'
                . '</script>');
    }
}

if (! function_exists('auto_link')) {
    /**
     * Lien automatique
     *
     * Liens automatiquement URL et adresses e-mail.
     * Remarque : il y a un peu de code supplémentaire ici à gérer
     * URL ou e-mails se terminant par un point. Nous allons les dépouiller
     * off et ajoutez-les après le lien.
     *
     * @param string $type  le type : email, url, ou les deux
     * @param bool   $popup s'il faut créer des liens contextuels
     */
    function auto_link(string $str, string $type = 'both', bool $popup = false): string
    {
        // Recherche et remplace tous les URLs.
        if ($type !== 'email' && preg_match_all('#(\w*://|www\.)[^\s()<>;]+\w#i', $str, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            // Définissez notre HTML cible si vous utilisez des liens contextuels.
            $target = ($popup) ? ' target="_blank"' : '';

            // Nous traitons les liens dans l'ordre inverse (dernier -> premier) de sorte que
            // les décalages de chaîne renvoyés par preg_match_all() ne sont pas
            // déplacées au fur et à mesure que nous ajoutons plus de HTML.
            foreach (array_reverse($matches) as $match) {
                // $match[0] est la chaîne/le lien correspondant
                // $match[1] est soit un préfixe de protocole soit 'www.'
                //
                // Avec PREG_OFFSET_CAPTURE, les deux éléments ci-dessus sont un tableau,
                // où la valeur réelle est contenue dans [0] et son décalage à l'index [1].
                $a   = '<a href="' . (strpos($match[1][0], '/') ? '' : 'http://') . $match[0][0] . '"' . $target . '>' . $match[0][0] . '</a>';
                $str = substr_replace($str, $a, $match[0][1], strlen($match[0][0]));
            }
        }

        // Recherche et remplace tous les e-mails.
        if ($type !== 'url' && preg_match_all('#([\w\.\-\+]+@[a-z0-9\-]+\.[a-z0-9\-\.]+[^[:punct:]\s])#i', $str, $matches, PREG_OFFSET_CAPTURE)) {
            foreach (array_reverse($matches[0]) as $match) {
                if (filter_var($match[0], FILTER_VALIDATE_EMAIL) !== false) {
                    $str = substr_replace($str, safe_mailto($match[0]), $match[1], strlen($match[0]));
                }
            }
        }

        return $str;
    }
}

if (! function_exists('prep_url')) {
    /**
     * Ajoute simplement la partie http:// ou https:// si aucun schéma n'est inclus.
     *
     * @param bool $secure définissez true si vous voulez forcer https://
     */
    function prep_url(string $str = '', bool $secure = false): string
    {
        if (in_array($str, ['http://', 'https://', '//', ''], true)) {
            return '';
        }

        if (parse_url($str, PHP_URL_SCHEME) === null) {
            $str = 'http://' . ltrim($str, '/');
        }

        // force le remplacement de http:// par https://
        if ($secure) {
            $str = preg_replace('/^(?:http):/i', 'https:', $str);
        }

        return $str;
    }
}

if (! function_exists('url_title')) {
    /**
     * Créer un titre d'URL
     *
     * Prend une chaîne de "titre" en entrée et crée un
     * Chaîne d'URL conviviale avec une chaîne de "séparateur"
     * comme séparateur de mots.
     *
     * @param string $separator Séparateur de mots (généralement '-' ou '_')
     * @param bool   $lowercase Indique s'il faut transformer la chaîne de sortie en minuscules
     */
    function url_title(string $str, string $separator = '-', bool $lowercase = false): string
    {
        $qSeparator = preg_quote($separator, '#');

        $trans = [
            '&.+?;'                  => '',
            '[^\w\d\pL\pM _-]'       => '',
            '\s+'                    => $separator,
            '(' . $qSeparator . ')+' => $separator,
        ];

        $str = strip_tags($str);

        foreach ($trans as $key => $val) {
            $str = preg_replace('#' . $key . '#iu', $val, $str);
        }

        if ($lowercase === true) {
            $str = mb_strtolower($str);
        }

        return trim(trim($str, $separator));
    }
}

if (! function_exists('mb_url_title')) {
    /**
     * Créer un titre d'URL qui prend en compte les caractères accentués
     *
     * Prend une chaîne de "titre" en entrée et crée un
     * Chaîne d'URL conviviale avec une chaîne de "séparateur"
     * comme séparateur de mots.
     *
     * @param string $separator Séparateur de mots (généralement '-' ou '_')
     * @param bool   $lowercase Indique s'il faut transformer la chaîne de sortie en minuscules
     */
    function mb_url_title(string $str, string $separator = '-', bool $lowercase = false): string
    {
        helper('scl');

        return url_title(scl_moveSpecialChar($str), $separator, $lowercase);
    }
}

if (! function_exists('url_to')) {
    /**
     * Obtenir l'URL complète et absolue d'une méthode de contrôleur
     * (avec arguments supplémentaires)
     *
     * REMARQUE : Cela nécessite que le contrôleur/la méthode ait une route définie dans le fichier de configuration des routes.
     *
     * @param mixed ...$args
     *
     * @throws RouterException
     */
    function url_to(string $controller, ...$args): string
    {
        if (! $route = route($controller, ...$args)) {
            $explode = explode('::', $controller);

            if (isset($explode[1])) {
                throw RouterException::controllerNotFound($explode[0], $explode[1]);
            }

            throw RouterException::invalidRoute($controller);
        }

        return site_url($route);
    }
}

if (! function_exists('route')) {
    /**
     * Tente de rechercher une route en fonction de sa destination.
     *
     * @return false|string
     */
    function route(string $method, ...$params)
    {
        return Services::routes()->reverseRoute($method, ...$params);
    }
}

if (! function_exists('action')) {
    /**
     * Obtenir l'URL d'une action du contrôleur.
     *
     * @return false|string
     */
    function action(array|string $action, array $parameters = [])
    {
        return url()->action($action, $parameters);
    }
}

if (! function_exists('url_is')) {
    /**
     * Détermine si le chemin d'URL actuel contient le chemin donné.
     * Il peut contenir un caractère générique (*) qui autorisera tout caractère valide.
     *
     * Exemple:
     *   if (url_is('admin*)) ...
     */
    function url_is(string $path): bool
    {
        // Configurez notre regex pour autoriser les caractères génériques
        $path        = '/' . trim(str_replace('*', '(\S)*', $path), '/ ');
        $currentPath = '/' . trim(uri_string(true), '/ ');

        return (bool) preg_match("|^{$path}$|", $currentPath, $matches);
    }
}

if (! function_exists('link_active')) {
    /**
     * Lien actif dans la navbar
     * Un peut comme le router-active-link de vuejs
     */
    function link_active(array|string $path, string $active_class = 'active', bool $exact = false): string
    {
        if (is_array($path)) {
            foreach ($path as $p) {
                if ($active_class === link_active($p, $active_class, $exact)) {
                    return $active_class;
                }
            }

            return '';
        }

        $current_url     = trim(current_url(false), '/');
        $current_section = trim(str_replace(trim(site_url(), '/'), '', $current_url), '/');

        if ($current_section === $path || $current_url === $path) {
            return $active_class;
        }

        if (! $exact && preg_match('#^' . $path . '/?#i', $current_section)) {
            return $active_class;
        }

        if (trim(link_to($path), '/') === $current_url) {
            return $active_class;
        }

        return '';
    }
}

if (! function_exists('clean_url')) {
    function clean_url(string $url): string
    {
        return Helpers::cleanUrl($url);
    }
}

if (! function_exists('is_absolute_link')) {
    /**
     * Verifies si un chemin donnée est une url absolue ou relative
     */
    function is_absolute_link(string $url): bool
    {
        return Helpers::isAbsoluteUrl($url);
    }
}
