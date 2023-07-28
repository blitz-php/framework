<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

if (! function_exists('css_url')) {
    /**
     * CSS URL
     *
     * Renvoie l'url d'un fichier css.
     *
     * @param string $name nom du fichier dont on veut avoir l'url
     */
    function css_url(string $name): string
    {
        $name = explode('?', $name)[0];
        $name = str_replace(site_url() . 'css/', '', htmlspecialchars($name));

        if (is_localfile($name)) {
            $name .= (! preg_match('#\.css$#i', $name) ? '.css' : '');
            $filename = WEBROOT . 'css' . DS . $name;

            return site_url() . 'css/' . $name . ((file_exists($filename)) ? '?v=' . filemtime($filename) : '');
        }

        return $name . (! preg_match('#\.css$#i', $name) ? '.css' : '');
    }
}

if (! function_exists('js_url')) {
    /**
     * JS URL
     *
     * Renvoie l'url d'un fichier js.
     *
     * @param string $name nom du fichier dont on veut avoir l'url
     */
    function js_url(string $name): string
    {
        $name = explode('?', $name)[0];
        $name = str_replace(site_url() . 'js/', '', htmlspecialchars($name));

        if (is_localfile($name)) {
            $name .= (! preg_match('#\.js$#i', $name) ? '.js' : '');
            $filename = WEBROOT . 'js' . DS . $name;

            return site_url() . 'js/' . $name . ((file_exists($filename)) ? '?v=' . filemtime($filename) : '');
        }

        return $name . (! preg_match('#\.js$#i', $name) ? '.js' : '');
    }
}

if (! function_exists('lib_css_url')) {
    /**
     * LIB CSS URL
     *
     * Renvoie l'url d'un fichier css d'une librairie
     *
     * @param string $name nom du fichier dont on veut avoir l'url
     */
    function lib_css_url(string $name): string
    {
        $name = explode('?', $name)[0];
        $name = str_replace(site_url() . 'lib/', '', htmlspecialchars($name));

        if (is_localfile($name)) {
            $name .= (! preg_match('#\.css$#i', $name) ? '.css' : '');
            $filename = WEBROOT . 'lib' . DS . $name;

            return site_url() . 'lib/' . $name . ((file_exists($filename)) ? '?v=' . filemtime($filename) : '');
        }

        return $name . (! preg_match('#\.css$#i', $name) ? '.css' : '');
    }
}

if (! function_exists('lib_js_url')) {
    /**
     * LIB JS URL
     *
     * Renvoie l'url d'un fichier js d'une librairy.
     *
     * @param string $name nom du fichier dont on veut avoir l'url
     */
    function lib_js_url(string $name): string
    {
        $name = explode('?', $name)[0];
        $name = str_replace(site_url() . 'lib/', '', htmlspecialchars($name));

        if (is_localfile($name)) {
            $name .= (! preg_match('#\.js$#i', $name) ? '.js' : '');
            $filename = WEBROOT . 'lib' . DS . $name;

            return site_url() . 'lib/' . $name . ((file_exists($filename)) ? '?v=' . filemtime($filename) : '');
        }

        return $name . (! preg_match('#\.js$#i', $name) ? '.js' : '');
    }
}

if (! function_exists('lib_styles')) {
    /**
     * LIB_STYLES
     *
     * inclu une ou plusieurs feuilles de style css
     *
     * @param string|string[] $name  nom du fichier dont on veut inserer
     * @param bool            $print Specifie si on affiche directement la sortie ou si on la retourne
     *
     * @return string|void
     */
    function lib_styles($name, bool $print = true)
    {
        $name   = (array) $name;
        $return = [];

        foreach ($name as $style) {
            if (is_string($style)) {
                $style = (! preg_match('#\.css$#i', $style) ? $style . '.css' : $style);
                if (is_file(WEBROOT . 'lib' . DS . str_replace('/', DS, $style))) {
                    $return[] = '<link rel="preload" type="text/css" href="' . lib_css_url($style) . '" as="style">
						<link rel="stylesheet" type="text/css" href="' . lib_css_url($style) . '" />';
                } elseif (is_localfile($style)) {
                    $return[] = "<!-- The specified file do not exist. we can not load it. \n\t";
                    $return[] = '<link rel="stylesheet" type="text/css" href="' . lib_css_url($style) . '" /> -->';
                } else {
                    $return[] = '<link rel="preload" type="text/css" href="' . lib_css_url($style) . '" as="style">
						<link rel="stylesheet" type="text/css" href="' . lib_css_url($style) . '" />';
                }
            }
        }

        $output = implode("\n", $return);

        if (false === $print) {
            return $output;
        }

        echo $output;
    }
}

if (! function_exists('lib_scripts')) {
    /**
     * LIB_SCRIPTS
     *
     * inclu un ou plusieurs scripts js
     *
     * @param string|string[] $name  nom du fichier dont on veut inserer
     * @param bool            $print Specifie si on affiche directement la sortie ou si on la retourne
     *
     * @return string|void
     */
    function lib_scripts($name, bool $print = true)
    {
        $name   = (array) $name;
        $return = [];

        foreach ($name as $script) {
            if (is_string($script)) {
                $script = (! preg_match('#\.js$#i', $script) ? $script . '.js' : $script);
                if (is_file(WEBROOT . 'lib' . DS . str_replace('/', DS, $script))) {
                    $return[] = '<script type="text/javascript" src="' . lib_js_url($script) . '"></script>';
                } elseif (is_localfile($script)) {
                    $return[] = "<!-- The specified file do not exist. we can not load it. \n\t";
                    $return[] = '<script type="text/javascript" src="' . lib_js_url($script) . '"></script> -->';
                } else {
                    $return[] = '<script type="text/javascript" src="' . lib_js_url($script) . '"></script>';
                }
            }
        }

        $output = implode("\n", $return);

        if (false === $print) {
            return $output;
        }

        echo $output;
    }
}

if (! function_exists('styles')) {
    /**
     * STYLES
     *
     * inclu une ou plusieurs feuilles de style css
     *
     * @param string|string[] $name  nom du fichier dont on veut inserer
     * @param bool            $print Specifie si on affiche directement la sortie ou si on la retourne
     *
     * @return string|void
     */
    function styles($name, bool $print = true)
    {
        $name   = (array) $name;
        $return = [];

        foreach ($name as $style) {
            if (is_string($style)) {
                $style = (! preg_match('#\.css$#i', $style) ? $style . '.css' : $style);
                if (is_file(WEBROOT . 'css' . DS . str_replace('/', DS, $style))) {
                    $return[] = '<link rel="preload" type="text/css" href="' . css_url($style) . '" as="style">
						<link rel="stylesheet" type="text/css" href="' . css_url($style) . '" />';
                } elseif (is_localfile($style)) {
                    $return[] = "<!-- The specified file do not exist. we can not load it. \n\t";
                    $return[] = '<link rel="stylesheet" type="text/css" href="' . css_url($style) . '" /> -->';
                } else {
                    $return[] = '<link rel="preload" type="text/css" href="' . css_url($style) . '" as="style">
						<link rel="stylesheet" type="text/css" href="' . css_url($style) . '" />';
                }
            }
        }

        $output = implode("\n", $return);

        if (false === $print) {
            return $output;
        }

        echo $output;
    }
}

if (! function_exists('scripts')) {
    /**
     * SCRIPTS
     *
     * inclu un ou plusieurs scripts js
     *
     * @param string|string[] $name  nom du fichier dont on veut inserer
     * @param bool            $print Specifie si on affiche directement la sortie ou si on la retourne
     *
     * @return string|void
     */
    function scripts($name, bool $print = true)
    {
        $name   = (array) $name;
        $return = [];

        foreach ($name as $script) {
            if (is_string($script)) {
                $script = (! preg_match('#\.js$#i', $script) ? $script . '.js' : $script);
                if (is_file(WEBROOT . 'js' . DS . str_replace('/', DS, $script))) {
                    $return[] = '<script type="text/javascript" src="' . js_url($script) . '"></script>';
                } elseif (is_localfile($script)) {
                    $return[] = "<!-- The specified file do not exist. we can not load it. \n\t";
                    $return[] = '<script type="text/javascript" src="' . js_url($script) . '"></script> -->';
                } else {
                    $return[] = '<script type="text/javascript" src="' . js_url($script) . '"></script>';
                }
            }
        }

        $output = implode("\n", $return);

        if (false === $print) {
            return $output;
        }

        echo $output;
    }
}

if (! function_exists('less_url')) {
    /**
     * LESS URL
     *
     * Renvoie l'url d'un fichier less.
     *
     * @param string $name nom du fichier dont on veut avoir l'url
     */
    function less_url(string $name): string
    {
        $name = explode('?', $name)[0];
        $name = str_replace(site_url() . 'less/', '', htmlspecialchars($name));

        if (is_localfile($name)) {
            $name .= (! preg_match('#\.less$#i', $name) ? '.less' : '');
            $filename = WEBROOT . 'less' . DS . $name;

            return site_url() . 'less/' . $name . ((file_exists($filename)) ? '?v=' . filemtime($filename) : '');
        }

        return $name . (! preg_match('#\.less$#i', $name) ? '.less' : '');
    }
}

if (! function_exists('less_styles')) {
    /**
     * LESS_STYLES
     *
     * inclu une ou plusieurs feuilles de style less
     *
     * @param string|string[] $name  nom du fichier dont on veut inserer
     * @param bool            $print Specifie si on affiche directement la sortie ou si on la retourne
     *
     * @return string|void
     */
    function less_styles($name, bool $print = true)
    {
        $name   = (array) $name;
        $return = [];

        foreach ($name as $style) {
            if (is_string($style)) {
                $style = (! preg_match('#\.less$#i', $style) ? $style . '.less' : $style);
                if (is_file(WEBROOT . 'less' . DS . str_replace('/', DS, $style))) {
                    $return[] = '<link rel="stylesheet" type="text/less" href="' . less_url($style) . '" />';
                } elseif (is_localfile($style)) {
                    $return[] = "<!-- The specified file do not exist. we can not load it. \n\t";
                    $return[] = '<link rel="stylesheet" type="text/less" href="' . less_url($style) . '" /> -->';
                } else {
                    $return[] = '<link rel="stylesheet" type="text/less" href="' . less_url($style) . '" />';
                }
            }
        }

        $output = implode("\n", $return);

        if (false === $print) {
            return $output;
        }

        echo $output;
    }
}

if (! function_exists('img_url')) {
    /**
     * IMG URL
     *
     * Renvoie l'url d'une image
     *
     * @param string $name nom du fichier dont on veut avoir l'url
     */
    function img_url(?string $name, bool $add_version = true): string
    {
        if (empty($name)) {
            return '';
        }

        $name = explode('?', $name)[0];
        $name = str_replace(site_url() . 'img/', '', htmlspecialchars($name));

        if (is_localfile($name)) {
            $filename = WEBROOT . 'img' . DS . $name;

            return site_url() . 'img/' . $name . ((file_exists($filename) && $add_version) ? '?v=' . filemtime($filename) : '');
        }

        return $name;
    }
}

if (! function_exists('img')) {
    /**
     * IMG
     *
     * Cree une image
     *
     * @param string $name nom du fichier dont on veut inserer
     * @param string $alt  texte alternatif
     *
     * @return string|void
     */
    function img(string $name, string $alt = '', array $options = [])
    {
        $return = '<img src="' . img_url($name) . '" alt="' . $alt . '"';

        $noprint = isset($options['print']) && $options['print'] === false;
        unset($options['print']);

        foreach ($options as $key => $value) {
            $return .= ' ' . $key . '="' . $value . '"';
        }
        $return .= ' />';

        if ($noprint === true) {
            return $return;
        }

        echo $return;
    }
}

if (! function_exists('docs_url')) {
    /**
     * DOCS URL
     *
     * Renvoie l'url d'un document
     *
     * @param string $name nom du fichier dont on veut avoir l'url
     */
    function docs_url(?string $name, bool $add_version = true): string
    {
        if (empty($name)) {
            return '';
        }

        $name = explode('?', $name)[0];
        $name = str_replace(site_url() . 'docs/', '', htmlspecialchars($name));

        if (is_localfile($name)) {
            $filename = WEBROOT . 'docs' . DS . $name;

            return site_url() . 'docs/' . $name . ((file_exists($filename && $add_version)) ? '?v=' . filemtime($filename) : '');
        }

        return $name;
    }
}

if (! function_exists('videos_url')) {
    /**
     * VIDEOS URL
     *
     * Renvoie l'url d'une vidéo
     *
     * @param string $name nom du fichier dont on veut avoir l'url
     */
    function videos_url(?string $name, bool $add_version = true): string
    {
        if (empty($name)) {
            return '';
        }

        $name = explode('?', $name)[0];
        $name = str_replace(site_url() . 'videos/', '', htmlspecialchars($name));

        if (is_localfile($name)) {
            $filename = WEBROOT . 'videos' . DS . $name;

            return site_url() . 'videos/' . $name . ((file_exists($filename) && $add_version) ? '?v=' . filemtime($filename) : '');
        }

        return $name;
    }
}

if (! function_exists('mix')) {
    /**
     * Obtenez le chemin d'accès à un fichier Mix versionné.
     *
     * @throws \Exception
     */
    function mix(string $path, string $manifestDirectory = ''): string
    {
        static $manifests = [];

        $publicPath = trim(WEBROOT, '/\\');

        if ($path[0] !== '/') {
            $path = "/{$path}";
        }

        if ($manifestDirectory && $manifestDirectory[0] !== '/') {
            $manifestDirectory = "/{$manifestDirectory}";
        }

        $config = (object) config('mix');

        if (is_file($publicPath . $manifestDirectory . '/hot')) {
            $url = rtrim(file_get_contents($publicPath . $manifestDirectory . '/hot'));

            if (!empty($customUrl = $customUrl = $config->hot_proxy_url)) {
                return $customUrl . $path;
            }

            if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
                return explode(':', $url, 2)[1] . $path;
            }

            return "//localhost:3300{$path}";
        }

        $manifestPath = $publicPath . $manifestDirectory . '/mix-manifest.json';

        if (!isset($manifests[$manifestPath])) {
            if (!is_file($manifestPath)) {
                throw new Exception('Le manifeste Mix n\'existe pas.');
            }

            $manifests[$manifestPath] = json_decode(file_get_contents($manifestPath), true);
        }

        $manifest = $manifests[$manifestPath];

        if (!isset($manifest[$path])) {
            $exception = new Exception("Impossible de localiser le fichier Mix: {$path}.");

            if (! BLITZ_DEBUG) {
                return $path;
            } else {
                throw $exception;
            }
        }

        return $config->url . $manifestDirectory . $manifest[$path];
    }
}
