<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Contracts\Event;

use Closure;

interface EventListenerManagerInterface
{
    /**
     * Modifie le gestionnaire d'evenement
     */
    public function setEventManager(EventManagerInterface $eventManager): self;

    /**
     * Renvoi le gestionnaire d'evenement
     */
    public function getEventManager(): EventManagerInterface;

    /**
     * Joindre un callback à un événement
     *
     * Si $bindContext est fourni, l'objet courant doit être lié comme
     * un contexte au callback fourni.
     */
    public function addEventListener(string $event, Closure $callback, int $priority = 0, bool $bindContext = false): bool;

    /**
     * Supprime un callback attaché à un événement
     */
    public function removeEventListener(string $event, callable $callback): bool;

    /**
     * Déclenche un événement
     *
     * @param array|EventInterface $event
     * @param mixed                $target
     * @param array|object         $params
     *
     * @return mixed
     */
    public function fireEvent($event, $target = null, $params = []);
}
