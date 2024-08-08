<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Router;

use BlitzPHP\Contracts\Router\AutoRouterInterface;
use BlitzPHP\Exceptions\MethodNotFoundException;
use BlitzPHP\Exceptions\PageNotFoundException;
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\Utilities\String\Text;
use ReflectionClass;
use ReflectionException;

/**
 * Routeur sécurisé pour le routage automatique
 *
 * @credit <a href="http://www.codeigniter.com">CodeIgniter 4.4 - CodeIgniter\Router\AutoRouterImproved</a>
 */
final class AutoRouter implements AutoRouterInterface
{
    /**
     * Sous-répertoire contenant la classe contrôleur demandée.
     * Principalement utilisé par 'autoRoute'.
     */
    private ?string $directory = null;

    /**
     * Le nom de la classe du contrôleur.
     */
    private string $controller;

    /**
     * Nom de la méthode à utiliser.
     */
    private string $method;

    /**
     * Tableau de paramètres de la méthode du contrôleur.
     *
     * @var string[]
     */
    private array $params = [];

    /**
     *  Indique si on doit traduire les tirets de l'URL pour les controleurs/methodes en CamelCase.
     *  E.g., blog-controller -> BlogController
     */
    private readonly bool $translateUriToCamelCase;

    /**
     * Namespace des controleurs
     */
    private string $namespace;

    /**
     * Segments de l'URI
     *
     * @var string[]
     */
    private array $segments = [];

    /**
     * Position du contrôleur dans les segments URI.
     * Null pour le contrôleur par défaut.
     */
    private ?int $controllerPos = null;

    /**
     * Position de la méthode dans les segments URI.
     * Null pour la méthode par défaut.
     */
    private ?int $methodPos = null;

    /**
     * Position du premier Paramètre dans les segments URI.
     * Null pour les paramètres non definis.
     */
    private ?int $paramPos = null;

    /**
     * Carte des segments URI et des namespace.
     *
     * La clé est le premier segment URI. La valeur est le namespace du contrôleur.
     * Ex.,
     *   [
     *       'blog' => 'Acme\Blog\Controllers',
     *   ]
     *
     * @var array [ uri_segment => namespace ]
     */
    private array $moduleRoutes;

    /**
     * URI courant
     */
    private ?string $uri = null;

    /**
     * Constructeur
     *
     * @param class-string[] $protectedControllers Liste des contrôleurs enregistrés pour le verbe CLI qui ne doivent pas être accessibles sur le Web.
     * @param string         $namespace            Espace de noms par défaut pour les contrôleurs.
     * @param string         $defaultController    Nom du controleur par defaut.
     * @param string         $defaultMethod        Nom de la methode par defaut.
     * @param bool           $translateURIDashes   Indique si les tirets dans les URI doivent être convertis en traits de soulignement lors de la détermination des noms de méthode.
     */
    public function __construct(
        private readonly array $protectedControllers,
        string $namespace,
        private string $defaultController,
        private readonly string $defaultMethod,
        private readonly bool $translateURIDashes
    ) {
        $this->namespace = rtrim($namespace, '\\');

        $routingConfig                 = (object) config('routing');
        $this->moduleRoutes            = $routingConfig->module_routes;
        $this->translateUriToCamelCase = $routingConfig->translate_uri_to_camel_case;

        // Definir les valeurs par defaut
        $this->controller = $this->defaultController;
    }

    private function createSegments(string $uri): array
    {
        $segments = explode('/', $uri);
        $segments = array_filter($segments, static fn ($segment) => $segment !== '');

        // réindexer numériquement le tableau, en supprimant les lacunes
        return array_values($segments);
    }

    /**
     * Recherchez le premier contrôleur correspondant au segment URI.
     *
     * S'il y a un contrôleur correspondant au premier segment, la recherche s'arrête là.
     * Les segments restants sont des paramètres du contrôleur.
     *
     * @return bool true si une classe de contrôleur est trouvée.
     */
    private function searchFirstController(): bool
    {
        $segments = $this->segments;

        $controller = '\\' . $this->namespace;

        $controllerPos = -1;

        while ($segments !== []) {
            $segment = array_shift($segments);
            $controllerPos++;

            $class = $this->translateURI($segment);

            // dès que nous rencontrons un segment qui n'est pas compatible PSR-4, arrêter la recherche
            if (! $this->isValidSegment($class)) {
                return false;
            }

            $controller = $this->makeController($controller . '\\' . $class);

            if (class_exists($controller)) {
                $this->controller    = $controller;
                $this->controllerPos = $controllerPos;

                $this->checkUriForController($controller);

                // Le premier élément peut être un nom de méthode.
                $this->params = $segments;
                if ($segments !== []) {
                    $this->paramPos = $this->controllerPos + 1;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Recherchez le dernier contrôleur par défaut correspondant aux segments URI.
     *
     * @return bool true si une classe de contrôleur est trouvée.
     */
    private function searchLastDefaultController(): bool
    {
        $segments = $this->segments;

        $segmentCount = count($this->segments);
        $paramPos     = null;
        $params       = [];

        while ($segments !== []) {
            if ($segmentCount > count($segments)) {
                $paramPos = count($segments);
            }

            $namespaces = array_map(
                fn ($segment) => $this->translateURI($segment),
                $segments
            );

            $controller = '\\' . $this->namespace
                . '\\' . implode('\\', $namespaces)
                . '\\' . $this->defaultController;

            if (class_exists($controller)) {
                $this->controller = $controller;
                $this->params     = $params;

                if ($params !== []) {
                    $this->paramPos = $paramPos;
                }

                return true;
            }

            // ajoutons le dernier élément dans $segments au début de $params.
            array_unshift($params, array_pop($segments));
        }

        // Vérifiez le contrôleur par défaut dans le répertoire des contrôleurs.
        $controller = '\\' . $this->namespace
            . '\\' . $this->defaultController;

        if (class_exists($controller)) {
            $this->controller = $controller;
            $this->params     = $params;

            if ($params !== []) {
                $this->paramPos = 0;
            }

            return true;
        }

        return false;
    }

    /**
     * Recherche contrôleur, méthode et params dans l'URI.
     *
     * @return array [directory_name, controller_name, controller_method, params]
     */
    public function getRoute(string $uri, string $httpVerb): array
    {
        $this->uri = $uri;
        $httpVerb  = strtolower($httpVerb);

        // Reinitialise les parametres de la methode du controleur.
        $this->params = [];

        $defaultMethod = $httpVerb . ucfirst($this->defaultMethod);
        $this->method  = $defaultMethod;

        $this->segments = $this->createSegments($uri);

        // Verifier les routes de modules
        if ($this->segments !== [] && array_key_exists($this->segments[0], $this->moduleRoutes)) {
            $uriSegment      = array_shift($this->segments);
            $this->namespace = rtrim($this->moduleRoutes[$uriSegment], '\\');
        }

        if ($this->searchFirstController()) {
            // Le contrôleur a ete trouvé.
            $baseControllerName = Helpers::classBasename($this->controller);

            // Empêcher l'accès au chemin de contrôleur par défaut
            if (strtolower($baseControllerName) === strtolower($this->defaultController)) {
                throw new PageNotFoundException(
                    'Impossible d\'accéder au contrôleur par défaut "' . $this->controller . '" avec le nom du contrôleur comme chemin de l\'URI.'
                );
            }
        } elseif ($this->searchLastDefaultController()) {
            // Le controleur par defaut a ete trouve.
            $baseControllerName = Helpers::classBasename($this->controller);
        } else {
            // Aucun controleur trouvé
            throw new PageNotFoundException('Aucun contrôleur trouvé pour: ' . $uri);
        }

        // Le premier élément peut être un nom de méthode.
        /** @var string[] $params */
        $params = $this->params;

        $methodParam = array_shift($params);

        $method = '';
        if ($methodParam !== null) {
            $method = $httpVerb . $this->translateURI($methodParam);

            $this->checkUriForMethod($method);
        }

        if ($methodParam !== null && method_exists($this->controller, $method)) {
            // Methode trouvee.
            $this->method = $method;
            $this->params = $params;

            // Mise a jour des positions.
            $this->methodPos = $this->paramPos;
            if ($params === []) {
                $this->paramPos = null;
            }
            if ($this->paramPos !== null) {
                $this->paramPos++;
            }

            // Empêcher l'accès à la méthode du contrôleur par défaut
            if (strtolower($baseControllerName) === strtolower($this->defaultController)) {
                throw new PageNotFoundException(
                    'Impossible d\'accéder au contrôleur par défaut "' . $this->controller . '::' . $this->method . '"'
                );
            }

            // Empêcher l'accès au chemin de méthode par défaut
            if (strtolower($this->method) === strtolower($defaultMethod)) {
                throw new PageNotFoundException(
                    'Impossible d\'accéder à la méthode par défaut "' . $this->method . '" avec le nom de méthode comme chemin d\'URI.'
                );
            }
        } elseif (method_exists($this->controller, $defaultMethod)) {
            // La methode par defaut a ete trouvée
            $this->method = $defaultMethod;
        } else {
            // Aucune methode trouvee
            throw PageNotFoundException::controllerNotFound($this->controller, $method);
        }

        // Vérifiez le contrôleur n'est pas défini dans les routes.
        $this->protectDefinedRoutes();

        // Assurez-vous que le contrôleur n'a pas la méthode _remap().
        $this->checkRemap();

        // Assurez-vous que les segments URI pour le contrôleur et la méthode
        // ne contiennent pas de soulignement lorsque $translateURIDashes est true.
        $this->checkUnderscore();

        // Verifier le nombre de parametres
        try {
            $this->checkParameters();
        } catch (MethodNotFoundException) {
            throw PageNotFoundException::controllerNotFound($this->controller, $this->method);
        }

        $this->setDirectory();

        return [$this->directory, $this->controllerName(), $this->methodName(), $this->params];
    }

    /**
     * @internal Juste pour les tests.
     *
     * @return array<string, int|null>
     */
    public function getPos(): array
    {
        return [
            'controller' => $this->controllerPos,
            'method'     => $this->methodPos,
            'params'     => $this->paramPos,
        ];
    }

    private function checkParameters(): void
    {
        try {
            $refClass = new ReflectionClass($this->controller);
        } catch (ReflectionException) {
            throw PageNotFoundException::controllerNotFound($this->controller, $this->method);
        }

        try {
            $refMethod = $refClass->getMethod($this->method);
            $refParams = $refMethod->getParameters();
        } catch (ReflectionException) {
            throw new MethodNotFoundException();
        }

        if (! $refMethod->isPublic()) {
            throw new MethodNotFoundException();
        }

        if (count($refParams) < count($this->params)) {
            throw new PageNotFoundException(
                'Le nombre de param dans l\'URI est supérieur aux paramètres de la méthode du contrôleur.'
                . ' Handler:' . $this->controller . '::' . $this->method
                . ', URI:' . $this->uri
            );
        }
    }

    private function checkRemap(): void
    {
        try {
            $refClass = new ReflectionClass($this->controller);
            $refClass->getMethod('_remap');

            throw new PageNotFoundException(
                'AutoRouterImproved ne prend pas en charge la methode `_remap()`.'
                . ' Contrôleur:' . $this->controller
            );
        } catch (ReflectionException) {
            // Ne rien faire
        }
    }

    private function checkUnderscore(): void
    {
        if ($this->translateURIDashes === false) {
            return;
        }

        $paramPos = $this->paramPos ?? count($this->segments);

        for ($i = 0; $i < $paramPos; $i++) {
            if (str_contains($this->segments[$i], '_')) {
                throw new PageNotFoundException(
                    'AutoRouterImproved interdit l\'accès à l\'URI'
                    . ' contenant les undescore ("' . $this->segments[$i] . '")'
                    . ' quand $translate_uri_dashes est activé.'
                    . ' Veuillez utiliser les tiret.'
                    . ' Handler:' . $this->controller . '::' . $this->method
                    . ', URI:' . $this->uri
                );
            }
        }
    }

    /**
     * Vérifier l'URI du contrôleur pour $translateUriToCamelCase
     *
     * @param string $classname Nom de classe du contrôleur généré à partir de l'URI.
     *                          La casse peut être un peu incorrecte.
     */
    private function checkUriForController(string $classname): void
    {
        if ($this->translateUriToCamelCase === false) {
            return;
        }

        if (! in_array(ltrim($classname, '\\'), get_declared_classes(), true)) {
            throw new PageNotFoundException(
                '"' . $classname . '" n\'a pas été trouvé.'
            );
        }
    }

    /**
     * Vérifier l'URI pour la méthode $translateUriToCamelCase
     *
     * @param string $method Nom de la méthode du contrôleur généré à partir de l'URI.
     *                       La casse peut être un peu incorrecte.
     */
    private function checkUriForMethod(string $method): void
    {
        if ($this->translateUriToCamelCase === false) {
            return;
        }

        if (
			// Par exemple, si `getSomeMethod()` existe dans le contrôleur, seul l'URI `controller/some-method` devrait être accessible.
			// Mais si un visiteur navigue vers l'URI `controller/somemethod`, `getSomemethod()` sera vérifié, et `method_exists()` retournera true parce que les noms de méthodes en PHP sont insensibles à la casse.
            method_exists($this->controller, $method)
            // Mais nous n'autorisons pas `controller/somemethod`, donc vérifiez le nom exact de la méthode.
			&& ! in_array($method, get_class_methods($this->controller), true)
		) {
            throw new PageNotFoundException(
                '"' . $this->controller . '::' . $method . '()" n\'a pas été trouvé.'
            );
        }
    }

    /**
     * Renvoie true si la chaîne $segment fournie représente un segment d'espace de noms/répertoire valide conforme à PSR-4
     *
     * La regex vient de https://www.php.net/manual/en/language.variables.basics.php
     */
    private function isValidSegment(string $segment): bool
    {
        return (bool) preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $segment);
    }

    private function translateURI(string $segment): string
    {
        if ($this->translateUriToCamelCase) {
            if (strtolower($segment) !== $segment) {
                throw new PageNotFoundException(
                    'AutoRouter interdit l\'accès à l\'URI'
                    . ' contenant des lettres majuscules ("' . $segment . '")'
                    . ' lorsque $translateUriToCamelCase est activé.'
                    . ' Veuillez utiliser le tiret.'
                    . ' URI:' . $this->uri
                );
            }

            if (str_contains($segment, '--')) {
                throw new PageNotFoundException(
                    'AutoRouter interdit l\'accès à l\'URI'
                    . ' contenant un double tiret ("' . $segment . '")'
                    . ' lorsque $translateUriToCamelCase est activé.'
                    . ' Veuillez utiliser le tiret simple.'
                    . ' URI:' . $this->uri
                );
            }

            return str_replace(
                ' ',
                '',
                ucwords(
                    preg_replace('/[\-]+/', ' ', $segment)
                )
            );
        }

        $segment = ucfirst($segment);

        if ($this->translateURIDashes) {
            return str_replace('-', '_', $segment);
        }

        return $segment;
    }

    /**
     * Obtenez le chemin du dossier du contrôleur et définissez-le sur la propriété.
     */
    private function setDirectory(): void
    {
        $segments = explode('\\', trim($this->controller, '\\'));

        // Supprimer le court nom de classe.
        array_pop($segments);

        $namespaces = implode('\\', $segments);

        $dir = str_replace(
            '\\',
            '/',
            ltrim(substr($namespaces, strlen($this->namespace)), '\\')
        );

        if ($dir !== '') {
            $this->directory = $dir . '/';
        }
    }

    private function protectDefinedRoutes(): void
    {
        $controller = strtolower($this->controller);

        foreach ($this->protectedControllers as $controllerInRoutes) {
            $routeLowerCase = strtolower($controllerInRoutes);

            if ($routeLowerCase === $controller) {
                throw new PageNotFoundException(
                    'Impossible d\'accéder à un contrôleur définie dans les routes. Contrôleur : ' . $controllerInRoutes
                );
            }
        }
    }

    /**
     * Renvoie le nom du sous-répertoire dans lequel se trouve le contrôleur.
     * Relatif à CONTROLLER_PATH
     *
     * @deprecated 1.0
     */
    public function directory(): string
    {
        return ! empty($this->directory) ? $this->directory : '';
    }

    /**
     * Renvoie le nom du contrôleur matché
     */
    private function controllerName(): string
    {
        return $this->translateURIDashes
            ? str_replace('-', '_', trim($this->controller, '/\\'))
            : Text::convertTo($this->controller, 'pascal');
    }

    /**
     * Retourne le nom de la méthode à exécuter
     */
    private function methodName(): string
    {
        return $this->translateURIDashes
            ? str_replace('-', '_', $this->method)
            : Text::convertTo($this->method, 'camel');
    }

    /**
     * Construit un nom de contrôleur valide
     */
    public function makeController(string $name): string
    {
        return preg_replace(
            ['#(\_)?Controller$#i', '#' . config('app.url_suffix') . '$#i'],
            '',
            ucfirst($name)
        ) . 'Controller';
    }
}
