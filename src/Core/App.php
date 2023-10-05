<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Core;

use BlitzPHP\Http\Uri;
use BlitzPHP\Utilities\Helpers;
use InvalidArgumentException;

/**
 * Il est responsable de l'emplacement des ressources et de la gestion des chemins.
 *
 * ### Ajout de chemins
 *
 * Des chemins supplémentaires pour les modèles et les plugins sont configurés avec Configurer maintenant. Voir config/app.php pour un
 * Exemple. Les variables `App.paths.plugins` et `App.paths.templates` sont utilisées pour configurer les chemins des plugins
 * et modèles respectivement. Toutes les ressources basées sur les classes doivent être mappées à l'aide du chargeur automatique de votre application.
 *
 * ### Inspecter les chemins chargés
 *
 * Vous pouvez inspecter les chemins actuellement chargés en utilisant `App::classPath('Controller')` par exemple pour voir les chemins chargés
 * chemins de contrôleur.
 *
 * Il est également possible d'inspecter les chemins des classes de plugins, par exemple, pour obtenir
 * le chemin vers les assistants d'un plugin que vous appelleriez `App::classPath('View/Helper', 'MyPlugin')`
 *
 * ### Localisation des plugins
 *
 * Les plugins peuvent également être localisés avec l'application. Utiliser Plugin::path('DebugKit') par exemple,
 * vous donne le chemin complet vers le plugin DebugKit.
 *
 * @creadit <a href="https://book.cakephp.org/4/en/core-libraries/app.html">CakePHP - Cake\Core\App</a>
 */
class App
{
    /**
     * Renvoie le nom de la classe dans le namespace. Cette méthode vérifie si la classe est définie sur le
     * application/plugin, sinon essayez de charger depuis le noyau de BlitzPHP
     *
     * @param string $type Type de la classe
     *
     * @return string|null Nom de classe avec le namespace, null si la classe est introuvable.
     * @psalm-return class-string|null
     */
    public static function className(string $class, string $type = '', string $suffix = ''): ?string
    {
        if (str_contains($class, '\\')) {
            return class_exists($class) ? $class : null;
        }

        [$plugin, $name] = Helpers::pluginSplit($class);
        $fullname        = '\\' . str_replace('/', '\\', $type . '\\' . $name) . $suffix;

        $base = $plugin ?: APP_NAMESPACE;
        if ($base !== null) {
            $base = str_replace('/', '\\', rtrim($base, '\\'));

            if (static::_classExistsInBase($fullname, $base)) {
                /** @var class-string */
                return $base . $fullname;
            }
        }

        if ($plugin || ! static::_classExistsInBase($fullname, 'BlitzPHP')) {
            return null;
        }

        /** @var class-string */
        return 'BlitzPHP' . $fullname;
    }

    /**
     * Renvoie le nom de division du plugin d'une classe
     *
     * Exemples:
     *
     * ```
     * App::shortName(
     *     'SomeVendor\SomePlugin\Controller\Component\TestComponent',
     *     'Controller/Component',
     *     'Component'
     * )
     * ```
     *
     * Returns: SomeVendor/SomePlugin.Test
     *
     * ```
     * App::shortName(
     *     'SomeVendor\SomePlugin\Controller\Component\Subfolder\TestComponent',
     *     'Controller/Component',
     *     'Component'
     * )
     * ```
     *
     * Returns: SomeVendor/SomePlugin.Subfolder/Test
     *
     * ```
     * App::shortName(
     *     'Cake\Controller\Component\AuthComponent',
     *     'Controller/Component',
     *     'Component'
     * )
     * ```
     *
     * Returns: Auth
     *
     * @param string $type Type de la classe
     *
     * @return string Plugin split name of class
     */
    public static function shortName(string $class, string $type, string $suffix = ''): string
    {
        $class = str_replace('\\', '/', $class);
        $type  = '/' . $type . '/';

        $pos = strrpos($class, $type);
        if ($pos === false) {
            return $class;
        }

        $pluginName = (string) substr($class, 0, $pos);
        $name       = (string) substr($class, $pos + strlen($type));

        if ($suffix) {
            $name = (string) substr($name, 0, -strlen($suffix));
        }

        $nonPluginNamespaces = [
            'BlitzPHP',
            str_replace('\\', '/', APP_NAMESPACE),
        ];
        if (in_array($pluginName, $nonPluginNamespaces, true)) {
            return $name;
        }

        return $pluginName . '.' . $name;
    }

    /**
     * _classExistsInBase
     *
     * Enveloppe d'isolation de test
     */
    protected static function _classExistsInBase(string $classname, string $namespace): bool
    {
        return class_exists($namespace . $classname);
    }

    /**
     * Renvoie le chemin complet vers un paquet à l'intérieur du noyau BlitzPHP
     *
     * Usage:
     *
     * ```
     * App::core('Cache/Engine');
     * ```
     *
     * Retournera le chemin complet vers le package des moteurs de cache.
     *
     * @param string $type Package type.
     *
     * @return string[] Chemin d'accès complet au package
     */
    public static function core(string $type): array
    {
        return [SYST_PATH . str_replace('/', DIRECTORY_SEPARATOR, $type) . DIRECTORY_SEPARATOR];
    }

    /**
     * Utilisé par les autres fonctions d'URL pour construire un
     * URI spécifique au framework basé sur la configuration de l'application.
     *
     * @internal En dehors du framework, ceci ne doit pas être utilisé directement.
     *
     * @param string $relativePath Peut inclure des requêtes ou des fragments
     *
     * @throws InvalidArgumentException Pour les chemins ou la configuration non valides
     */
    public static function getUri(string $relativePath = ''): Uri
    {
        $config = (object) config('app');

        if ($config->base_url === '') {
            throw new InvalidArgumentException(__METHOD__ . ' requires a valid baseURL.');
        }

        // Si un URI complet a été passé, convertissez-le
        if (is_int(strpos($relativePath, '://'))) {
            $full         = new Uri($relativePath);
            $relativePath = Uri::createURIString(null, null, $full->getPath(), $full->getQuery(), $full->getFragment());
        }

        $relativePath = URI::removeDotSegments($relativePath);

        // Construire l'URL complète basée sur $config et $relativePath
        $url = rtrim($config->base_url, '/ ') . '/';

        // Recherche une page d'index
        if ($config->index_page !== '') {
            $url .= $config->index_page;

            // Vérifie si nous avons besoin d'un séparateur
            if ($relativePath !== '' && $relativePath[0] !== '/' && $relativePath[0] !== '?') {
                $url .= '/';
            }
        }

        $url .= $relativePath;

        $uri = new Uri($url);

        // Vérifie si le schéma baseURL doit être contraint dans sa version sécurisée
        if ($config->force_global_secure_requests && $uri->getScheme() === 'http') {
            $uri->setScheme('https');
        }

        return $uri;
    }
}
