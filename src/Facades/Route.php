<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Facades;

use BlitzPHP\Router\RouteBuilder;
use Closure;

/**
 * @method static void         configure(callable $callback(RouteBuilder $route))                         Configure les parametres de routing.
 * @method static void         add(string $from, array|callable|string $to, array $options = [])          Enregistre une seule route à la collection.
 * @method static RouteBuilder addPlaceholder($placeholder, ?string $pattern = null)                      Enregistre une nouvelle contrainte auprès du système.
 * @method static RouteBuilder addRedirect(string $from, string $to, int $status = 302)                   Ajoute une redirection temporaire d'une route à une autre.
 * @method static RouteBuilder as(string $name)                                                           Defini un nom de route
 * @method static void         cli(string $from, array|callable|string $to, array $options = [])          Enregistre une route qui ne sera disponible que pour les requêtes de ligne de commande.
 * @method static RouteBuilder controller(string $controller)                                             Defini le contrôleur a utiliser dans le routage
 * @method static void         delete(string $from, array|callable|string $to, array $options = [])       Enregistre une route qui ne sera disponible que pour les requêtes DELETE.
 * @method static RouteBuilder domain(string $domain)                                                     Defini une restriction de domaine pour la route
 * @method static void         environment(string $env, Closure $callback)                                Limite les routes à un ENVIRONNEMENT spécifié ou ils ne fonctionneront pas.
 * @method static RouteBuilder fallback($callable = null)                                                 Définit la classe/méthode qui doit être appelée si le routage ne trouver pas une correspondance.
 * @method static void         form(string $from, array|callable|string $to, array $options = [])         Enregistre une route qui ne sera disponible que pour les requêtes GET et POST.
 * @method static void         get(string $from, array|callable|string $to, array $options = [])          Enregistre une route qui ne sera disponible que pour les requêtes GET.
 * @method static void         head(string $from, array|callable|string $to, array $options = [])         Enregistre une route qui ne sera disponible que pour les requêtes HEAD.
 * @method static RouteBuilder hostname(string $hostname)                                                 Defini une restriction de non d'hôte pour la route
 * @method static void         map(array $routes = [], array $options = [])                               Une méthode de raccourci pour ajouter un certain nombre de routes en une seule fois. Il ne permet pas de définir des options sur la route, ou de définir la méthode utilisée.
 * @method static void         match(array $verbs = [], string $from = '', $to = '', array $options = []) Ajoute une seule route à faire correspondre pour plusieurs verbes HTTP.
 * @method static RouteBuilder middleware(array|string $middleware)
 * @method static RouteBuilder name(string $name)                                                         Defini un nom de route
 * @method static RouteBuilder namespace(string $namespace)                                               Defini le namespace a utiliser dans le routage
 * @method static void         options(string $from, array|callable|string $to, array $options = [])      Enregistre une route qui ne sera disponible que pour les requêtes OPTIONS.
 * @method static void         patch(string $from, array|callable|string $to, array $options = [])        Enregistre une route qui ne sera disponible que pour les requêtes PATCH.
 * @method static RouteBuilder permanentRedirect(string $from, string $to)                                Ajoute une redirection permanente d'une route à une autre.
 * @method static RouteBuilder placeholder(string $placeholder)
 * @method static void         post(string $from, array|callable|string $to, array $options = [])         Enregistre une route qui ne sera disponible que pour les requêtes POST.
 * @method static RouteBuilder prefix(string $prefix)
 * @method static void         presenter(string $name, array $options = [])                               Crée une collection de routes basées sur les verbes HTTP pour un contrôleur de présentation.
 * @method static RouteBuilder priority(int $priority)
 * @method static void         put(string $from, array|callable|string $to, array $options = [])          Enregistre une route qui ne sera disponible que pour les requêtes PUT.
 * @method static RouteBuilder redirect(string $from, string $to, int $status = 302)                      Ajoute une redirection temporaire d'une route à une autre.
 * @method static void         resource(string $name, array $options = [])                                Crée une collection de routes basés sur HTTP-verb pour un contrôleur.
 * @method static RouteBuilder set404Override($callable = null)                                           Définit la classe/méthode qui doit être appelée si le routage ne trouver pas une correspondance.
 * @method static RouteBuilder setAutoRoute(bool $value)                                                  Si TRUE, le système tentera de faire correspondre l'URI avec Contrôleurs en faisant correspondre chaque segment avec des dossiers/fichiers dans CONTROLLER_PATH, lorsqu'aucune correspondance n'a été trouvée pour les routes définies.
 * @method static RouteBuilder setDefaultConstraint(string $placeholder)                                  Définit la contrainte par défaut à utiliser dans le système. Typiquement à utiliser avec la méthode 'ressource'.
 * @method static RouteBuilder setDefaultController(string $value)                                        Définit le contrôleur par défaut à utiliser lorsqu'aucun autre contrôleur n'a été spécifié.
 * @method static RouteBuilder setDefaultMethod(string $value)                                            Définit la méthode par défaut pour appeler le contrôleur lorsqu'aucun autre méthode a été définie dans la route.
 * @method static RouteBuilder setDefaultNamespace(string $value)                                         Définit l'espace de noms par défaut à utiliser pour les contrôleurs lorsqu'aucun autre n'a été spécifié.
 * @method static RouteBuilder setPrioritize(bool $enabled = true)                                        Activer ou désactiver le tri des routes par priorité
 * @method static RouteBuilder setTranslateURIDashes(bool $value)                                         Indique au système s'il faut convertir les tirets des chaînes URI en traits de soulignement.
 * @method static RouteBuilder subdomain(string $subdomain)
 * @method static RouteBuilder useSupportedLocalesOnly(bool $useOnly)                                     Indique au router de limiter ou non les routes avec l'espace réservé {locale} à App::$supportedLocales
 * @method static void         view(string $from, string $view, array $options = [])                      Spécifie une route qui n'affichera qu'une vue. Ne fonctionne que pour les requêtes GET.
 * @method static RouteBuilder where($placeholder, ?string $pattern = null)                               Enregistre une nouvelle contrainte auprès du système.
 *
 * @see RouteBuilder
 */
final class Route extends Facade
{
    protected static function accessor(): object
    {
        return new RouteBuilder(service('routes'));
    }
}
