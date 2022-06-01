<?php
namespace BlitzPHP\Event;

use BlitzPHP\Contracts\Event\EventManagerInterface;
use Closure;

/**
 * EventListenerManagerTrait
 *
 * @credit      https://www.phpclasses.org/package/9961-PHP-Manage-events-implementing-PSR-14-interface.html - Kiril Savchev <k.savchev@gmail.com>
 */
trait EventListenerManagerTrait
{

    /**
     * L'actuel gestionnaire d'evenement
     *
     * @var EventManager
     */
    protected $eventManager;

    /**
     * Modifie le gestionnaire d'evenement
     */
    public function setEventManager(EventManagerInterface $eventManager): self
    {
        $this->eventManager = $eventManager;

        return $this;
    }

    /**
     *Renvoi le gestionnaire d'evenement
     */
    public function getEventManager(): EventManagerInterface
    {
        return $this->eventManager;
    }

    /**
     * Joindre un callback à un événement
     *
     * Si $bindContext est fourni, l'objet courant doit être lié comme
     * un contexte au callback fourni.
     */
    public function addEventListener(string $event, Closure $callback, int $priority = 0, bool $bindContext = false): bool
    {
        if ($bindContext) {
            $callback = Closure::bind($callback, $this, get_class($this));
        }

        return $this->eventManager->attach($event, $callback, $priority);
    }

    /**
     * Déclenche un événement
     *
     * @param array|EventInterface $event
     * @param mixed $target
     * @param array|object $params
     * @return mixed
     */
    public function fireEvent($event, $target = null, $params = [])
    {
        return $this->eventManager->trigger($event, $target, $params);
    }

    /**
     * Supprime un callback attaché à un événement
     */
    public function removeEventListener(string $event, callable $callback): bool
    {
        return $this->eventManager->detach($event, $callback);
    }
}
