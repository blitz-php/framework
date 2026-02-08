<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Event;

use BlitzPHP\Contracts\Event\EventInterface;
use BlitzPHP\Contracts\Event\EventManagerInterface;
use Closure;
use RuntimeException;

/**
 * Trait de gestion des écouteurs d'événements
 *
 * Ce trait fournit des méthodes pratiques pour gérer les événements
 * dans les classes qui l'utilisent.
 *
 * @credit      https://www.phpclasses.org/package/9961-PHP-Manage-events-implementing-PSR-14-interface.html - Kiril Savchev <k.savchev@gmail.com>
 */
trait EventListenerManagerTrait
{
    /**
     * Le gestionnaire d'événements actuel.
     */
    protected ?EventManagerInterface $eventManager = null;

    /**
     * Définit le gestionnaire d'événements.
     */
    public function setEventManager(EventManagerInterface $eventManager): self
    {
        $this->eventManager = $eventManager;

        return $this;
    }

    /**
     * Récupère le gestionnaire d'événements.
     *
     * @return EventManager
     *
     * @throws RuntimeException Si aucun gestionnaire n'est défini
     */
    public function getEventManager(): EventManagerInterface
    {
        if ($this->eventManager === null) {
            throw new RuntimeException('Aucun gestionnaire d\'événements n\'a été défini.');
        }

        return $this->eventManager;
    }

    /**
     * Vérifie si un gestionnaire d'événements est défini.
     */
    public function hasEventManager(): bool
    {
        return $this->eventManager !== null;
    }

    /**
     * Ajoute un écouteur d'événement
     *
     * @param string  $event       Nom de l'événement
     * @param Closure $callback    Callback à exécuter
     * @param int     $priority    Priorité d'exécution
     * @param bool    $bindContext Si true, bind la closure au contexte actuel ($this)
     *
     * @return bool True si l'écouteur a été ajouté
     *
     * @throws RuntimeException Si aucun gestionnaire d'événements n'est défini
     */
    public function addEventListener(string $event, Closure $callback, int $priority = 0, bool $bindContext = false): bool
    {
        if ($bindContext) {
            $callback = Closure::bind($callback, $this, static::class);
        }

        return $this->getEventManager()->on($event, $callback, $priority);
    }

    /**
     * Ajoute un écouteur d'événement qui ne s'exécute qu'une fois
     *
     * @param string  $event       Nom de l'événement
     * @param Closure $callback    Callback à exécuter
     * @param int     $priority    Priorité d'exécution
     * @param bool    $bindContext True pour lier le contexte de l'objet courant
     *
     * @return bool True si l'écouteur a été ajouté
     */
    public function addEventListenerOnce(string $event, Closure $callback, int $priority = 0, bool $bindContext = false): bool
    {
        if ($bindContext) {
            $callback = Closure::bind($callback, $this, static::class);
        }

        return $this->getEventManager()->once($event, $callback, $priority);
    }

    /**
     * Déclenche un événement
     *
     * @param EventInterface|string $event  Nom ou objet de l'événement
     * @param mixed                 $target Cible de l'événement
     * @param array|object          $params Paramètres supplémentaires
     *
     * @return mixed Résultat de l'exécution des écouteurs
     *
     * @throws RuntimeException Si aucun gestionnaire d'événements n'est défini
     */
    public function fireEvent($event, $target = null, $params = []): mixed
    {
        return $this->getEventManager()->emit($event, $target, $params);
    }

    /**
     * Supprime un écouteur d'événement
     *
     * @param string   $event    Nom de l'événement
     * @param callable $callback Callback à supprimer
     *
     * @return bool True si l'écouteur a été supprimé
     *
     * @throws RuntimeException Si aucun gestionnaire d'événements n'est défini
     */
    public function removeEventListener(string $event, callable $callback): bool
    {
        return $this->getEventManager()->off($event, $callback);
    }

    /**
     * Supprime tous les écouteurs d'un événement
     *
     * @param string|null $event Nom de l'événement (null pour tous)
     *
     * @throws RuntimeException Si aucun gestionnaire d'événements n'est défini
     */
    public function clearEventListeners(?string $event = null): void
    {
        $this->getEventManager()->clearListeners($event);
    }

    /**
     * Vérifie si un événement a des écouteurs
     *
     * @throws RuntimeException Si aucun gestionnaire d'événements n'est défini
     */
    public function hasEventListeners(string $event): bool
    {
        return $this->getEventListeners($event) !== [];
    }

    /**
     * Récupère les écouteurs d'un événement
     *
     * @param string|null $event Nom de l'événement (null pour tous)
     *
     * @return array Liste des écouteurs
     *
     * @throws RuntimeException Si aucun gestionnaire d'événements n'est défini
     */
    public function getEventListeners(?string $event = null): array
    {
        return $this->getEventManager()->getListeners($event);
    }

    /**
     * Déclenche un événement avec des middlewares pré et post
     *
     * @param array      $preMiddlewares  Middlewares pré-exécution
     * @param array      $postMiddlewares Middlewares post-exécution
     * @param mixed|null $target
     *
     * @return mixed Résultat de l'exécution
     */
    public function fireEventWithMiddleware(string $event, $target = null, array $params = [], array $preMiddlewares = [], array $postMiddlewares = []): mixed
    {
        $manager = $this->getEventManager();

        // Exécute les middlewares pré-exécution
        foreach ($preMiddlewares as $middleware) {
            if (is_callable($middleware)) {
                $middleware($params, $target);
            }
        }

        // Déclenche l'événement principal
        $result = $manager->emit($event, $target, $params);

        // Exécute les middlewares post-exécution
        foreach ($postMiddlewares as $middleware) {
            if (is_callable($middleware)) {
                $middleware($result, $params, $target);
            }
        }

        return $result;
    }

    /**
     * Crée un événement avec le contexte courant comme cible
     */
    protected function createEvent(string $name, array $params = []): Event
    {
        return new Event($name, $this, $params);
    }

    /**
     * Déclenche un événement avec le contexte courant comme cible
     *
     * @return mixed Résultat de l'exécution
     */
    protected function fireEventWithSelf(string $name, array $params = []): mixed
    {
        return $this->fireEvent($this->createEvent($name, $params));
    }
}
