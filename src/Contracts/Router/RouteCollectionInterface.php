<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Contracts\Router;

use Closure;

/**
 * Interface RouteCollectionInterface
 *
 * Le seul travail d'une collection de routes est de contenir une série de routes. Le nécessaire
 * le nombre de méthodes est délibérément très petit, mais les implémenteurs peuvent
 * ajouter un certain nombre de méthodes supplémentaires pour personnaliser la façon dont les routes sont définies.
 *
 * La RouteCollection fournit au routeur les routes afin qu'il puisse déterminer
 * quel contrôleur doit être exécuté.
 */
interface RouteCollectionInterface
{
    /**
     * Ajoute une seule route à la collection.
     *
     * Example:
     *      $routes->add('news', 'Posts::index');
     *
     * @param array|Closure|string $to
     */
    public function add(string $from, $to, ?array $options = null);

    /**
     * Enregistre une nouvelle contrainte auprès du système. Les contraintes sont utilisées
     * par les routes en tant qu'espaces réservés pour les expressions régulières afin de définir
     * les parcours plus humains.
     *
     * Vous pouvez passer un tableau associatif en tant que $placeholder et avoir
     * plusieurs espaces réservés ajoutés à la fois.
     *
     * @param array|string $placeholder
	 *
	 * @return mixed
     */
    public function addPlaceholder($placeholder, ?string $pattern = null);

    /**
     * Définit l'espace de noms par défaut à utiliser pour les contrôleurs lorsqu'aucun autre n'a été spécifié.
     *
     * @return mixed
     */
    public function setDefaultNamespace(string $value);

    /**
     * Renvoie l'espace de noms par défaut tel qu'il est défini dans le fichier de configuration Routes.
     */
    public function getDefaultNamespace(): string;

    /**
     * Définit le contrôleur par défaut à utiliser lorsqu'aucun autre contrôleur n'a été spécifié.
     *
     * @return mixed
     */
    public function setDefaultController(string $value);

    /**
     * Définit la méthode par défaut pour appeler le contrôleur lorsqu'aucun autre
     * méthode a été définie dans la route.
     *
     * @return mixed
     */
    public function setDefaultMethod(string $value);

    /**
     *  Si TRUE, le système tentera de faire correspondre l'URI avec
     * Contrôleurs en faisant correspondre chaque segment avec des dossiers/fichiers
     * dans CONTROLLER_PATH, lorsqu'aucune correspondance n'a été trouvée pour les routes définies.
     *
     * Si FAUX, la recherche s'arrêtera et n'effectuera AUCUN routage automatique.
     */
    public function setAutoRoute(bool $value): self;

    /**
     * Définit la classe/méthode qui doit être appelée si le routage ne trouver pas une correspondance.
     * Il peut s'agir soit d'une closure, soit d'un contrôleur/méthode exactement comme une route est définie : Users::index
     *
     * Ce paramètre est transmis à la classe Routeur et y est géré.
     *
     * @param callable|null $callable
     */
    public function set404Override($callable = null): self;

    /**
     * Renvoie le paramètre 404 Override, qui peut être nul, une closure, une chaîne contrôleur/méthode.
     *
     * @return Closure|string|null
     */
    public function get404Override();

    /**
     * Renvoie le nom du contrôleur par défaut. Avec l'espace de noms.
     */
    public function getDefaultController(): string;

    /**
     * Renvoie le nom de la méthode par défaut à utiliser dans le contrôleur.
     */
    public function getDefaultMethod(): string;

    /**
     * Renvoie l'indicateur qui indique s'il faut auto-router l'URI pour trouver les contrôleurs/méthodes.
     */
    public function shouldAutoRoute(): bool;

    /**
     * Renvoie le tableau brut des routes disponibles.
     *
     * @return mixed
     */
    public function getRoutes();

    /**
     * Renvoie le verbe HTTP actuellement utilisé.
     */
    public function getHTTPVerb(): string;

    /**
     * Définit le verbe HTTP actuel.
     * Utilisé principalement pour les tests.
     */
    public function setHTTPVerb(string $verb): self;

    /**
     * Tente de rechercher une route en fonction de sa destination.
     *
     * Si une route existe :
     *
     * 'path/(:any)/(:any)' => 'Controller::method/$1/$2'
     *
     * Cette méthode vous permet de connaître le contrôleur et la méthode
     * et obtenir la route qui y mène.
     *
     * // Égal à 'chemin/$param1/$param2'
     * reverseRoute('Controller::method', $param1, $param2);
     *
     * @param mixed ...$params
     *
     * @return false|string
     */
    public function reverseRoute(string $search, ...$params);

    /**
     * Détermine si la route est une route de redirection.
     */
    public function isRedirect(string $from): bool;

    /**
     * Récupère le code d'état HTTP d'une route de redirection.
     */
    public function getRedirectCode(string $from): int;

	/**
     * Renvoie la valeur actuelle du paramètre translateURIDashes.
     */
    public function shouldTranslateURIDashes(): bool;

	/**
     * Indique au système s'il faut convertir les tirets des chaînes URI en traits de soulignement.
	 * Dans certains moteurs de recherche, y compris Google, les tirets créent plus de sens et permettent au moteur de recherche
	 * de trouver plus facilement des mots et une signification dans l'URI pour un meilleur référencement.
	 * Mais cela ne fonctionne pas bien avec les noms de méthodes PHP...
     */
    public function setTranslateURIDashes(bool $value): self;

	/**
     * Obtenez tous les contrôleurs dans Route Handlers
     *
     * @param string|null $verbe HTTP verbe. `'*'` renvoie tous les contrôleurs dans n'importe quel verbe.
     */
    public function getRegisteredControllers(?string $verb = '*'): array;
}
