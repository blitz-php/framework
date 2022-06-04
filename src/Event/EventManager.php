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

/**
 * EventManager
 *
 * @credit      https://www.phpclasses.org/package/9961-PHP-Manage-events-implementing-PSR-14-interface.html - Kiril Savchev <k.savchev@gmail.com>
 */
class EventManager implements EventManagerInterface
{
    /**
     * Le nom générique de l'événement
     */
    public const WILDCARD = '*';

    /**
     * @var array
     */
    protected $events;

    /**
     * Stocke des informations sur les événements
     * pour affichage dans la barre d'outils de débogage.
     *
     * @var array
     */
    protected static $performanceLog = [];

    /**
     * Créer un objet gestionnaire d'événements
     *
     * @param array $events [Optionnel]
     */
    public function __construct(array $events = [])
    {
        $this->events = $events;
        if (! array_key_exists(self::WILDCARD, $this->events)) {
            $this->events[self::WILDCARD] = [];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function clearListeners(string $event): void
    {
        $this->events[$event] = [];
    }

    /**
     * {@inheritDoc}
     */
    public function attach(string $event, callable $callback, int $priority = 0): bool
    {
        if (! array_key_exists($event, $this->events)) {
            $this->events[$event] = [];
        }
        if (! array_key_exists($priority, $this->events[$event])) {
            $this->events[$event][$priority] = [];
        }

        if (! in_array($callback, $this->events[$event][$priority], true)) {
            $this->events[$event][$priority][] = $callback;

            return true;
        }

        return false;
    }

    /**
     * Alias de la méthode attach
     */
    public function on(string $event, callable $callback, int $priority = 0): bool
    {
        return $this->attach($event, $callback, $priority);
    }

    /**
     * {@inheritDoc}
     */
    public function detach(string $event, callable $callback): bool
    {
        if (! array_key_exists($event, $this->events) || ! $this->events[$event]) {
            return false;
        }

        $eventsAgregation = $this->events[$event];

        foreach ($eventsAgregation as $priority => $events) {
            if (is_array($events) && in_array($callback, $events, true)) {
                $key = array_search($callback, $events, true);
                unset($this->events[$event][$priority][$key]);
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function trigger($event, $target = null, $argv = [])
    {
        if (! ($event instanceof EventInterface)) {
            $event = new Event($event, $target, $argv);
        } else {
            if ($target) {
                $event->setTarget($target);
            }
            if ($argv) {
                $event->setParams($argv);
            }
        }

        $eventName = $event->getName();
        if (! array_key_exists($eventName, $this->events)) {
            $this->events[$eventName] = [];
        }

        $events = array_merge($this->events[self::WILDCARD], $this->events[$eventName]);
        $result = null;

        foreach ($events as $priority) {
            if (! is_array($priority)) {
                continue;
            }

            foreach ($priority as $callback) {
                if ($event->isPropagationStopped()) {
                    break 2;
                }

                $start = microtime(true);

                $result = $callback($event, $result);

                static::$performanceLog[] = [
                    'start' => $start,
                    'end'   => microtime(true),
                    'event' => strtolower($eventName),
                ];
            }
        }

        return $result;
    }

    /**
     * Getter pour les enregistrements du journal des performances.
     */
    public static function getPerformanceLogs(): array
    {
        return static::$performanceLog;
    }
}
