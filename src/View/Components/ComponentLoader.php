<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\View\Components;

use BlitzPHP\Cache\CacheInterface;
use BlitzPHP\Container\Services;
use BlitzPHP\Exceptions\ViewException;
use DI\NotFoundException;
use ReflectionException;
use ReflectionMethod;

/**
 * Une classe simple qui peut appeler n'importe quelle autre classe qui peut être chargée, et afficher son résultat.
 * Destinée à afficher de petits blocs de contenu dans des vues qui peuvent être gérées par d'autres bibliothèques
 * et qui ne nécessitent pas d'être chargées dans le contrôleur.
 *
 * Utilisée avec la fonction d'aide, son utilisation sera la suivante :
 *
 *         component('\Some\Class::method', 'limit=5 sort=asc', 60, 'cache-name');
 *
 * Les paramètres sont mis en correspondance avec les arguments de la méthode de rappel portant le même nom :
 *
 *         class Class {
 *             function method($limit, $sort)
 *         }
 *
 * Sinon, les paramètres seront transmis à la méthode de callback sous la forme d'un simple tableau si les paramètres correspondants ne sont pas trouvés.
 *
 *         class Class {
 *             function method(array $params=null)
 *         }
 *
 * @credit <a href="http://www.codeigniter.com">CodeIgniter 4.5 - CodeIgniter\View\Cell</a>
 */
class ComponentLoader
{
    /**
     * @param CacheInterface $cache Instance du Cache
     */
    public function __construct(protected CacheInterface $cache)
    {
    }

    /**
     * Rendre un composant, en renvoyant son corps sous forme de chaîne.
     *
     * @param string            $library   Nom de la classe et de la méthode du composant.
     * @param array|string|null $params    Paramètres à passer à la méthode.
     * @param int               $ttl       Nombre de secondes pour la mise en cache de la cellule.
     * @param string|null       $cacheName Nom de l'élément mis en cache.
     *
     * @throws ReflectionException
     */
    public function render(string $library, null|array|string $params = null, int $ttl = 0, ?string $cacheName = null): string
    {
        [$instance, $method] = $this->determineClass($library);

        $class = is_object($instance) ? $instance::class : null;

        $params = $this->prepareParams($params);

        // Le résultat est-il mis en cache ??
        $cacheName ??= str_replace(['\\', '/'], '', $class) . $method . md5(serialize($params));

        if ($output = $this->cache->get($cacheName)) {
            return $output;
        }

        if (method_exists($instance, 'initialize')) {
            $instance->initialize(Services::request(), Services::response(), Services::logger());
        }

        if (! method_exists($instance, $method)) {
            throw ViewException::invalidComponentMethod($class, $method);
        }

        $output = $instance instanceof Component
            ? $this->renderComponent($instance, $method, $params)
            : $this->renderSimpleClass($instance, $method, $params, $class);

        // Doit-on le mettre en cache?
        if ($ttl !== 0) {
            $this->cache->set($cacheName, $output, $ttl);
        }

        return $output;
    }

    /**
     * Analyse l'attribut params. S'il s'agit d'un tableau, il est renvoyé tel quel.
     * S'il s'agit d'une chaîne, elle doit être au format "clé1=valeur clé2=valeur".
     * Elle sera divisée et renvoyée sous forme de tableau.
     *
     * @param array<string, string>|string|null $params
     */
    public function prepareParams($params): array
    {
        if ($params === null || $params === '' || $params === [] || (! is_string($params) && ! is_array($params))) {
            return [];
        }

        if (is_string($params)) {
            $newParams = [];
            $separator = ' ';

            if (str_contains($params, ',')) {
                $separator = ',';
            }

            $params = explode($separator, $params);
            unset($separator);

            foreach ($params as $p) {
                if ($p !== '') {
                    [$key, $val] = explode('=', $p);

                    $newParams[trim($key)] = trim($val, ', ');
                }
            }

            $params = $newParams;
            unset($newParams);
        }

        if ($params === []) {
            return [];
        }

        return $params;
    }

    /**
     * Étant donné la chaîne de la bibliothèque, tente de déterminer la classe et la méthode à appeler.
     */
    protected function determineClass(string $library): array
    {
        //  Nous ne voulons pas appeler les méthodes statiques par défaut, c'est pourquoi nous convertissons tous les doubles points.
        $library = str_replace('::', ':', $library);

        //  Les composants contrôlées peuvent être appelées avec le seul nom de la classe, c'est pourquoi il faut ajouter une méthode par défaut
        if (! str_contains($library, ':')) {
            $library .= ':render';
        }

        [$class, $method] = explode(':', $library);

        if ($class === '') {
            throw ViewException::noComponentClass();
        }

        //  localise et renvoie une instance du composant
        try {
            $object = Services::container()->get($class);
        } catch (NotFoundException) {
            $locator = Services::locator();

            if (false === $path = $locator->locateFile($class, 'Components')) {
                throw ViewException::invalidComponentClass($class);
            }
            if (false === $_class = $locator->findQualifiedNameFromPath($path)) {
                throw ViewException::invalidComponentClass($class);
            }

            try {
                $object = Services::container()->get($_class);
            } catch (NotFoundException) {
                throw ViewException::invalidComponentClass($class);
            }
        }

        if (! is_object($object)) {
            throw ViewException::invalidComponentClass($class);
        }

        if ($method === '') {
            $method = 'index';
        }

        return [
            $object,
            $method,
        ];
    }

    /**
     * Rend un cellule qui étend la classe Component.
     */
    final protected function renderComponent(Component $instance, string $method, array $params): string
    {
        // Ne permet de définir que des propriétés publiques, ou des propriétés protégées/privées
        // qui ont une méthode pour les obtenir (get<Foo>Property()).
        $publicProperties  = $instance->getPublicProperties();
        $privateProperties = array_column($instance->getNonPublicProperties(['view']), 'name');
        $publicParams      = array_intersect_key($params, $publicProperties);

        foreach ($params as $key => $value) {
            $getter = 'get' . ucfirst((string) $key) . 'Property';
            if (in_array($key, $privateProperties, true) && method_exists($instance, $getter)) {
                $publicParams[$key] = $value;
            }
        }

        // Remplir toutes les propriétés publiques qui ont été passées,
        // mais seulement celles qui se trouvent dans le tableau $pulibcProperties.
        $instance = $instance->fill($publicParams);

        //  S'il existe des propriétés protégées/privées, nous devons les envoyer à la méthode mount().
        if (method_exists($instance, 'mount')) {
            //  si des $params ont des clés qui correspondent au nom d'un argument de la méthode mount,
            // passer ces variables à la méthode.
            $mountParams = $this->getMethodParams($instance, 'mount', $params);
            $instance->mount(...$mountParams);
        }

        return $instance->{$method}();
    }

    /**
     * Renvoie les valeurs de $params qui correspondent aux paramètres d'une méthode, dans l'ordre où ils sont définis.
     * Cela permet de les passer directement dans la méthode.
     */
    private function getMethodParams(Component $instance, string $method, array $params): array
    {
        $mountParams = [];

        try {
            $reflectionMethod = new ReflectionMethod($instance, $method);
            $reflectionParams = $reflectionMethod->getParameters();

            foreach ($reflectionParams as $reflectionParam) {
                $paramName = $reflectionParam->getName();

                if (array_key_exists($paramName, $params)) {
                    $mountParams[] = $params[$paramName];
                }
            }
        } catch (ReflectionException) {
            // ne rien faire
        }

        return $mountParams;
    }

    /**
     * Rend la classe non-Component, en passant les paramètres string/array.
     *
     * @todo Déterminer si cela peut être remanié pour utiliser $this-getMethodParams().
     *
     * @param object $instance
     */
    final protected function renderSimpleClass($instance, string $method, array $params, string $class): string
    {
        // Essayez de faire correspondre la liste de paramètres qui nous a été fournie avec le nom
        // du paramètre dans la méthode de callback.
        $refMethod  = new ReflectionMethod($instance, $method);
        $paramCount = $refMethod->getNumberOfParameters();
        $refParams  = $refMethod->getParameters();

        if ($paramCount === 0) {
            if ($params !== []) {
                throw ViewException::missingComponentParameters($class, $method);
            }

            $output = $instance->{$method}();
        } elseif (($paramCount === 1)
            && ((! array_key_exists($refParams[0]->name, $params))
            || (array_key_exists($refParams[0]->name, $params)
            && count($params) !== 1))
        ) {
            $output = $instance->{$method}($params);
        } else {
            $fireArgs     = [];
            $methodParams = [];

            foreach ($refParams as $arg) {
                $methodParams[$arg->name] = true;
                if (array_key_exists($arg->name, $params)) {
                    $fireArgs[$arg->name] = $params[$arg->name];
                }
            }

            foreach (array_keys($params) as $key) {
                if (! isset($methodParams[$key])) {
                    throw ViewException::invalidComponentParameter($key);
                }
            }

            $output = $instance->{$method}(...array_values($fireArgs));
        }

        return $output;
    }
}
