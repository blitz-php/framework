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
use Error;
use Throwable;

/**
 * Gestionnaire d'événements PSR-14.
 *
 * Cette classe gère l'enregistrement et le déclenchement des événements.
 * Elle supporte les priorités, les wildcards et le logging de performances.
 *
 * @credit      https://www.phpclasses.org/package/9961-PHP-Manage-events-implementing-PSR-14-interface.html - Kiril Savchev <k.savchev@gmail.com>
 */
class EventManager implements EventManagerInterface
{
    /**
     * Le nom générique de l'événement (wildcard)
     */
    public const WILDCARD = '*';

    /**
     * Écouteurs d'événements organisés par nom d'événement et priorité
     *
     * @var array<string, array<int, array<callable>>>
	 *
	 * @example $listeners[eventName][priority][] = callback
     */
    protected array $listeners = [];

    /**
     * Log des performances pour le débogage
     *
     * @var array<array{start: float, end: float, event: string, listener: string}>
     */
    protected array $performanceLog = [];

    /**
     * Indique si le logging des performances est activé
     */
    protected bool $performanceLogging = false;

    /**
     * Constructeur du gestionnaire d'événements
     *
     * @param array<string, array<int, array<callable>>> $listeners Écouteurs initiaux
     */
    public function __construct(array $listeners = [])
    {
        $this->listeners = $listeners;

        // Initialise le wildcard s'il n'existe pas
        if (! isset($this->listeners[self::WILDCARD])) {
            $this->listeners[self::WILDCARD] = [];
        }

        // Active le logging en mode debug
        $this->performanceLogging = on_dev() && BLITZ_DEBUG;
    }

    /**
     * {@inheritDoc}
     */
    public function getListeners(?string $event = null): array
    {
        if ($event === null) {
            return array_filter($this->listeners, static fn($key): bool => $key !== self::WILDCARD, ARRAY_FILTER_USE_KEY);
        }

        return $this->listeners[$event] ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function clearListeners(?string $event = null): void
    {
        if ($event === null) {
            $this->listeners = [self::WILDCARD => []];
        } elseif (array_key_exists($event, $this->listeners)) {
            unset($this->listeners[$event]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function on(string $event, callable $callback, int $priority = 0): bool
    {
        if (! array_key_exists($priority, $this->listeners[$event] ?? [])) {
            $this->listeners[$event][$priority] = [];
        }

        if (in_array($callback, $this->listeners[$event][$priority], true)) {
            return false;
        }

        $this->listeners[$event][$priority][] = $callback;

        ksort($this->listeners[$event], SORT_NUMERIC);

        return true;
    }

	/**
     * Enregistre un écouteur qui ne s'exécute qu'une seule fois
     */
    public function once(string $event, callable $callback, int $priority = 0): bool
    {
		$wrapper = null;
        $wrapper = function (...$args) use ($callback, $event, &$wrapper) {
            $result = $callback(...$args);

            if ($wrapper !== null) {
                $this->off($event, $wrapper);
            }

            return $result;
        };

        return $this->on($event, $wrapper, $priority);
    }

    /**
     * Alias déprécié pour on()
     *
     * @deprecated 0.13 Utilisez on() à la place
	 *
     * @see self::on()
     */
    public function attach(string $event, callable $callback, int $priority = 0): bool
    {
		$this->deprecatedWarning('La méthode attach() est obsolète, utilisez plutôt on().');

        return $this->on($event, $callback, $priority);
    }

    /**
     * {@inheritDoc}
     */
    public function off(string $event, callable $callback): bool
    {
        if (empty($this->listeners[$event])) {
            return false;
        }

		foreach ($this->listeners[$event] as $priority => &$callbacks) {
            if (false !== $key = array_search($callback, $callbacks, true)) {
                unset($callbacks[$key]);

                // Réindexe le tableau
                $callbacks = array_values($callbacks);

                // Supprime la priorité si elle est vide
                if (empty($callbacks)) {
                    unset($this->listeners[$event][$priority]);
                }

                // Supprime l'événement s'il est vide
                if (empty($this->listeners[$event])) {
                    unset($this->listeners[$event]);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Alias déprécié pour off()
     *
     * @deprecated 0.13 Utilisez off() à la place
     *
	 * @see self::off()
     */
    public function detach(string $event, callable $callback): bool
    {
        $this->deprecatedWarning('La méthode detach() est obsolète, utilisez plutôt off().');

        return $this->off($event, $callback);
    }

    /**
     * {@inheritDoc}
     */
    public function emit($event, $target = null, $argv = []): mixed
    {
        if (! ($event instanceof EventInterface)) {
            $event = $this->createEvent($event, $target, $argv);
        } else {
            $this->updateEvent($event, $target, $argv);
        }

        $eventName = $event->getName();

        // Récupère les écouteurs pour cet événement et les wildcards
        $listeners = $this->getListenersForEvent($eventName);

        if ($listeners === []) {
            return null;
        }

        // Trie les écouteurs par priorité (ordre décroissant)
        krsort($listeners, SORT_NUMERIC);

        $result = null;

        foreach ($listeners as $priority => $callbacks) {
            if (! is_array($callbacks)) {
                continue;
            }

            foreach ($callbacks as $callback) {
                if ($event->isPropagationStopped() || $result === false) {
                    break 2;
                }

                $result = $this->executeCallback($callback, $event, $eventName, $priority);
            }
        }

        return $result;
    }

    /**
     * Alias déprécié pour emit()
     *
     * @deprecated 0.13 Utilisez emit() à la place
	 *
     * @see self::emit()
     */
    public function trigger($event, $target = null, $argv = []): mixed
    {
        $this->deprecatedWarning('La méthode trigger() est obsolète, utilisez plutôt emit().');

        return $this->emit($event, $target, $argv);
    }

    /**
     * Ajoute un middleware à un événement
     *
     * @param string $event Nom de l'événement
     * @param callable $middleware Middleware à exécuter
     * @param string $type Type de middleware ('pre' ou 'post')
     * @param int $priority Priorité d'exécution
	 *
     * @return bool True si le middleware a été ajouté
     */
    public function addMiddleware(string $event, callable $middleware, string $type = 'pre', int $priority = 0): bool
    {
        $eventName = $type === 'pre' ? "pre.{$event}" : "post.{$event}";

        return $this->on($eventName, $middleware, $priority);
    }

    /**
     * Vérifie si un événement a des écouteurs
     *
     * @param string $event Nom de l'événement
	 *
     * @return bool True si l'événement a des écouteurs
     */
    public function hasListeners(string $event): bool
    {
        return $this->getListenersForEvent($event, false) !== [];
    }

    /**
     * Compte le nombre d'écouteurs pour un événement
     *
     * @param string $event Nom de l'événement
	 *
     * @return int Nombre d'écouteurs
     */
    public function countListeners(string $event): int
    {
        $listeners = $this->getListenersForEvent($event);
        $count = 0;

        foreach ($listeners as $callbacks) {
            if (is_array($callbacks)) {
                $count += count($callbacks);
            }
        }

        return $count;
    }

    /**
     * Active ou désactive le logging des performances
     *
     * @param bool $enabled True pour activer, false pour désactiver
     */
    public function setPerformanceLogging(bool $enabled): self
    {
        $this->performanceLogging = $enabled;

        return $this;
    }

    /**
     * Récupère les logs de performance
     *
     * @return array<array{start: float, end: float, event: string, listener: string, priority: bool}>
     */
    public function getPerformanceLogs(): array
    {
        return $this->performanceLog;
    }

    /**
     * Efface les logs de performance
     */
    public function clearPerformanceLogs(): self
    {
        $this->performanceLog = [];

        return $this;
    }

    /**
     * Crée un objet événement
     */
    protected function createEvent(string $name, mixed $target = null, array|object $argv = []): EventInterface
    {
        $params = is_array($argv) ? $argv : get_object_vars($argv);

        return new Event($name, $target, $params);
    }

    /**
     * Met à jour un événement existant
     *
     * @param mixed        $target Nouvelle cible
     * @param array|object $argv Nouveaux paramètres
     */
    protected function updateEvent(Event $event, mixed $target = null, array|object $argv = []): void
    {
        if ($target !== null) {
            $event->setTarget($target);
        }

        if (!empty($argv)) {
			$params = is_array($argv) ? $argv : get_object_vars($argv);

			$event->mergeParams($params);
        }
    }

    /**
     * Récupère les écouteurs pour un événement donné
     *
     * @return array<int, array<callable>> Écouteurs organisés par priorité
     */
    protected function getListenersForEvent(string $eventName, bool $withWildcard = true): array
    {
		$specificListeners = $this->listeners[$eventName] ?? [];

		if ($withWildcard) {
			$globalWildcardListeners   = $this->listeners[self::WILDCARD] ?? [];
			$specificWildcardListeners = [];

			$prefix = explode('.', $eventName)[0];
			foreach ($this->listeners as $k => $v) {
				if (str_starts_with($k, $prefix . '.' . self::WILDCARD)) {
					$specificWildcardListeners = [...$specificWildcardListeners, ...$v];
				}
			}

			$sources = [$specificListeners, $specificWildcardListeners, $globalWildcardListeners];
		} else {
			$sources = [$specificListeners];
		}

        // Fusionne les écouteurs spécifiques et les wildcards
        $listeners = [];

        foreach ($sources as $source) {
            foreach ($source as $priority => $callbacks) {
                if (! isset($listeners[$priority])) {
                    $listeners[$priority] = [];
                }

                $listeners[$priority] = array_merge($listeners[$priority], $callbacks);
            }
        }

        return $listeners;
    }

    /**
     * Exécute un callback et log les performances si nécessaire
     */
    protected function executeCallback(callable $callback, EventInterface $event, string $eventName, int $priority): mixed
    {
        $startTime = null;

        if ($this->performanceLogging) {
            $startTime = microtime(true);
        }

        try {
            $result = $callback($event);
        } catch (Throwable $e) {
            // Log l'erreur mais continue l'exécution des autres écouteurs
			logger()->error(sprintf(
				'Event listener error: %s in %s:%s',
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			));

            // Relance l'exception si c'est une exception critique
            if ($e instanceof Error) {
                throw $e;
            }

            $result = null;
        }

        if ($this->performanceLogging && $startTime !== null) {
            $this->logPerformance($startTime, $eventName, $callback, $priority);
        }

        return $result;
    }

    /**
     * Log les performances d'un écouteur
     */
    protected function logPerformance(float $startTime, string $eventName, callable $callback, int $priority): void
    {
        $endTime = microtime(true);
        $listenerName = $this->getCallbackName($callback);

        $this->performanceLog[] = [
			'start'    => $startTime,
			'end'      => $endTime,
			'event'    => strtolower($eventName),
			'listener' => $listenerName,
			'duration' => $endTime - $startTime,
			'priority' => $priority,
        ];
    }

    /**
     * Récupère le nom d'un callback pour le logging
     */
    protected function getCallbackName(callable $callback): string
    {
        if (is_string($callback)) {
            return $callback;
        }

        if (is_array($callback)) {
            if (is_object($callback[0])) {
                return get_class($callback[0]) . '::' . $callback[1];
            }

            return $callback[0] . '::' . $callback[1];
        }

        if ($callback instanceof Closure) {
            return 'Closure';
        }

        if (is_object($callback) && method_exists($callback, '__invoke')) {
            return get_class($callback) . '::__invoke';
        }

        return 'Unknown';
    }

	private function deprecatedWarning(string $message)
	{
		if (! on_test()) {
			trigger_error($message, E_USER_DEPRECATED);
		}

		return true;
	}
}
