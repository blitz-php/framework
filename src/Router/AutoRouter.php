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
use BlitzPHP\Exceptions\PageNotFoundException;
use BlitzPHP\Utilities\String\Str;

/**
 * Routeur sécurisé pour le routage automatique
 *
 * @credit <a href="http://www.codeigniter.com">CodeIgniter 4.2 - CodeIgniter\Router\AutoRouterImproved</a>
 */
final class AutoRouter implements AutoRouterInterface
{
    /**
     * Liste des contrôleurs enregistrés pour le verbe CLI qui ne doivent pas être accessibles sur le Web.
     *
     * @var class-string[]
     */
    private array $protectedControllers;

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
     * Indique si les tirets dans les URI doivent être convertis
     * en traits de soulignement lors de la détermination des noms de méthode.
     */
    private bool $translateURIDashes;

    /**
     * Verbe HTTP pour la requête.
     */
    private string $httpVerb;

    /**
     * Espace de noms par défaut pour les contrôleurs.
     */
    private string $defaultNamespace;

    public function __construct(
        array $protectedControllers,
        string $defaultNamespace,
        string $defaultController,
        string $defaultMethod,
        bool $translateURIDashes,
        string $httpVerb
    ) {
        $this->protectedControllers = $protectedControllers;
        $this->defaultNamespace     = $defaultNamespace;
        $this->translateURIDashes   = $translateURIDashes;
        $this->httpVerb             = $httpVerb;

        $this->controller = $defaultController;
        $this->method     = $defaultMethod;
    }

    /**
     * Tente de faire correspondre un chemin d'URI avec les contrôleurs et
     * les répertoires trouvés dans CONTROLLER_PATH, pour trouver une route correspondante.
     *
     * @return array [directory_name, controller_name, controller_method, params]
     */
    public function getRoute(string $uri): array
    {
        $segments = explode('/', $uri);

        $segments = $this->scanControllers($segments);

        // Si nous n'avons plus de segments - essayez le contrôleur par défaut ;
        // AVERTISSEMENT : les répertoires sont déplacés hors du tableau de segments.
        if (empty($segments)) {
            // $this->setDefaultController();
        }
        // S'il n'est pas vide, le premier segment doit être le contrôleur
        else {
            $this->setController(array_shift($segments));
        }

        $controllerName = $this->controllerName();

        if (! $this->isValidSegment($controllerName)) {
            throw new PageNotFoundException($this->controller . ' is not a valid controller name');
        }

        // Utilise le nom de la méthode s'il existe.
        // Si ce n'est pas le cas, ce n'est pas grave - le nom de la méthode par défaut
        // a déjà été défini.
        if (! empty($segments)) {
            $this->setMethod(array_shift($segments) ?: $this->method);
        }

        // Empêcher l'accès à la méthode initController
        if (strtolower($this->method) === 'initcontroller') {
            throw PageNotFoundException::pageNotFound();
        }

        /** @var array $params An array of params to the controller method. */
        $params = [];

        if (! empty($segments)) {
            $params = $segments;
        }

        // Assurez-vous que les routes enregistrées via $routes->cli() ne sont pas accessibles via le Web.
        if ($this->httpVerb !== 'cli') {
            $controller = '\\' . $this->defaultNamespace;

            $controller .= $this->directory ? str_replace('/', '\\', $this->directory) : '';
            $controller .= $controllerName;

            $controller = strtolower($controller);

            foreach ($this->protectedControllers as $controllerInRoute) {
                if (! is_string($controllerInRoute)) {
                    continue;
                }
                if (strtolower($controllerInRoute) !== $controller) {
                    continue;
                }

                throw new PageNotFoundException(
                    'Cannot access the controller in a CLI Route. Controller: ' . $controllerInRoute
                );
            }
        }

        // Charge le fichier afin qu'il soit disponible.
        $file = CONTROLLER_PATH . $this->directory . $controllerName . '.php';
        if (is_file($file)) {
            include_once $file;
        }

        // Assurez-vous que le contrôleur stocke le nom de classe complet
        // Nous devons vérifier une longueur supérieure à 1, puisque par défaut ce sera '\'
        if (strpos($this->controller, '\\') === false && strlen($this->defaultNamespace) > 1) {
            $this->setController('\\' . ltrim(
                str_replace(
                    '/',
                    '\\',
                    $this->defaultNamespace . $this->directory . $controllerName
                ),
                '\\'
            ));
        }

        return [$this->directory, $this->controllerName(), $this->methodName(), $params];
    }

    /**
     * Scanne le répertoire du contrôleur, essayant de localiser un contrôleur correspondant aux segments d'URI fournis
     *
     * @param array $segments segments d'URI
     *
     * @return array renvoie un tableau des segments uri restants qui ne correspondent pas à un répertoire
     */
    private function scanControllers(array $segments): array
    {
        $segments = array_filter($segments, static fn ($segment) => $segment !== '');
        // réindexe numériquement le tableau, supprimant les lacunes
        $segments = array_values($segments);

        // si une valeur de répertoire précédente a été définie, retournez simplement les segments et sortez d'ici
        if (isset($this->directory)) {
            return $segments;
        }

        // Parcourez nos segments et revenez dès qu'un contrôleur
        // est trouvé ou lorsqu'un tel répertoire n'existe pas
        $c = count($segments);

        while ($c-- > 0) {
            $segmentConvert = ucfirst(
                $this->translateURIDashes ? str_replace('-', '_', $segments[0]) : $segments[0]
            );
            // dès que nous rencontrons un segment non conforme à PSR-4, arrêtons la recherche
            if (! $this->isValidSegment($segmentConvert)) {
                return $segments;
            }

            $test = CONTROLLER_PATH . $this->directory . $segmentConvert;

            // tant que chaque segment n'est *pas* un fichier de contrôleur mais correspond à un répertoire, ajoutez-le à $this->répertoire
            if (! is_file($test . '.php') && is_dir($test)) {
                $this->setDirectory($segmentConvert, true, false);
                array_shift($segments);

                continue;
            }

            return $segments;
        }

        // Cela signifie que tous les segments étaient en fait des répertoires
        return $segments;
    }

    /**
     * Renvoie true si la chaîne $segment fournie représente un segment d'espace de noms/répertoire valide conforme à PSR-4
     *
     * regex comes from https://www.php.net/manual/en/language.variables.basics.php
     */
    private function isValidSegment(string $segment): bool
    {
        return (bool) preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $segment);
    }

    /**
     * Définit le sous-répertoire dans lequel se trouve le contrôleur.
     *
     * @param bool $validate si vrai, vérifie que $dir se compose uniquement de segments conformes à PSR4
     */
    public function setDirectory(?string $dir = null, bool $append = false, bool $validate = true)
    {
        if (empty($dir)) {
            $this->directory = null;

            return;
        }

        if ($validate) {
            $segments = explode('/', trim($dir, '/'));

            foreach ($segments as $segment) {
                if (! $this->isValidSegment($segment)) {
                    return;
                }
            }
        }

        if ($append !== true || empty($this->directory)) {
            $this->directory = trim($dir, '/') . '/';
        } else {
            $this->directory .= trim($dir, '/') . '/';
        }
    }

    /**
     * Renvoie le nom du sous-répertoire dans lequel se trouve le contrôleur.
     * Relatif à CONTROLLER_PATH
     */
    public function directory(): string
    {
        return ! empty($this->directory) ? $this->directory : '';
    }

    /**
     * Renvoie le nom du contrôleur matché
     *
     * @return closure|string
     */
    private function controllerName()
    {
        if (! is_string($this->controller)) {
            return $this->controller;
        }

        return $this->translateURIDashes
            ? str_replace('-', '_', trim($this->controller, '/\\'))
            : Str::toPascalCase($this->controller);
    }

    /**
     * Retourne le nom de la méthode à exécuter
     */
    private function methodName(): string
    {
        return $this->translateURIDashes
            ? str_replace('-', '_', $this->method)
            : Str::toCamelCase($this->method);
    }

    /**
     * Modifie le nom du controleur
     */
    private function setController(string $name): void
    {
        $this->controller = $this->makeController($name);
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

    /**
     * Modifie le nom de la méthode
     */
    private function setMethod(string $name): void
    {
        $this->method = preg_replace('#' . config('app.url_suffix') . '$#i', '', $name);
    }
}
