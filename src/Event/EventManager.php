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
     * Stocke des informations sur les événements
     * pour affichage dans la barre d'outils de débogage.
     *
     * @var array
     */
    protected static $performanceLog = [];

    /**
     * Créer un objet gestionnaire d'événements
     *
     * @param array $listeners Listeners initiaux
     */
    public function __construct(protected array $listeners = [])
    {
        if (! array_key_exists(self::WILDCARD, $this->listeners)) {
            $this->listeners[self::WILDCARD] = [];
        }
    }

    public function getListeners(?string $event = null): array
    {
        if ($event === null) {
            return array_filter($this->listeners, static fn ($key) => $key !== self::WILDCARD, ARRAY_FILTER_USE_KEY);
        }

        if (! array_key_exists($event, $this->listeners)) {
            return [];
        }

        return $this->listeners[$event] ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function clearListeners(?string $event = null): void
    {
        if ($event === null) {
            $this->listeners = array_filter($this->listeners, static fn ($key) => $key === self::WILDCARD, ARRAY_FILTER_USE_KEY);
        } elseif (array_key_exists($event, $this->listeners)) {
            unset($this->listeners[$event]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function on(string $event, callable $callback, int $priority = 0): bool
    {
        if (! array_key_exists($event, $this->listeners)) {
            $this->listeners[$event] = [];
        }
        if (! array_key_exists($priority, $this->listeners[$event])) {
            $this->listeners[$event][$priority] = [];
        }

        if (! in_array($callback, $this->listeners[$event][$priority], true)) {
            $this->listeners[$event][$priority][] = $callback;

            return true;
        }

        return false;
    }

    /**
     * @deprecated use on() instead
     */
    public function attach(string $event, callable $callback, int $priority = 0): bool
    {
        return $this->on($event, $callback, $priority);
    }

    /**
     * {@inheritDoc}
     */
    public function off(string $event, callable $callback): bool
    {
        if (! array_key_exists($event, $this->listeners) || ! $this->listeners[$event]) {
            return false;
        }

        $eventsAgregation = $this->listeners[$event];

        foreach ($eventsAgregation as $priority => $events) {
            if (is_array($events) && in_array($callback, $events, true)) {
                $key = array_search($callback, $events, true);
                unset($this->listeners[$event][$priority][$key]);

                return true;
            }
        }

        return false;
    }

    /**
     * @deprecated use off() instead
     */
    public function detach(string $event, callable $callback): bool
    {
        return $this->off($event, $callback);
    }

    /**
     * {@inheritDoc}
     */
    public function emit($event, $target = null, $argv = [])
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
        if (! array_key_exists($eventName, $this->listeners)) {
            $this->listeners[$eventName] = [];
        }

        // $events = array_merge($this->listeners[self::WILDCARD], $this->listeners[$eventName]);
        $events = $this->listeners[$eventName];
        $result = null;
        ksort($events, SORT_NUMERIC);

        foreach ($events as $priority) {
            if (! is_array($priority)) {
                continue;
            }

            foreach ($priority as $callback) {
                if ($event->isPropagationStopped() || $result === false) {
                    break 2;
                }

                $start = microtime(true);

                $result = $callback($event);

                if (BLITZ_DEBUG || on_dev()) {
					static::$performanceLog[] = [
						'start' => $start,
						'end'   => microtime(true),
						'event' => strtolower($eventName),
					];
				}
            }
        }

        return $result;
    }

    /**
     * @deprecated use emit() instead
     *
     * @param mixed      $event
     * @param mixed|null $target
     * @param mixed      $argv
     */
    public function trigger($event, $target = null, $argv = [])
    {
        return $this->emit($event, $target, $argv);
    }

    /**
     * Getter pour les enregistrements du journal des performances.
     */
    public static function getPerformanceLogs(): array
    {
        return static::$performanceLog;
    }
}
