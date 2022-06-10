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

/**
 * Interface pour EventManager
 *
 * Ceci est une copie de l'ancienne interface des evenements PSR-14
 * L'EventManager a été remplacé par l'EventDispatcher mais BlitzPHP continue d'utiliser l'ancienne spécification va savoir pourquoi 😅
 */
interface EventManagerInterface
{
    /**
     * Attache un écouteur à un événement
     *
     * @param string   $event    l'événement à attacher
     * @param callable $callback une fonction appelable
     * @param int      $priority la priorité à laquelle le $callback est exécuté
     *
     * @return bool vrai en cas de succès faux en cas d'échec
     */
    public function attach(string $event, callable $callback, int $priority = 0): bool;

    /**
     * Détache un écouteur d'un événement
     *
     * @param string   $event    l'événement à détacher
     * @param callable $callback une fonction appelable
     *
     * @return bool vrai en cas de succès faux en cas d'échec
     */
    public function detach(string $event, callable $callback): bool;

    /**
     * Effacer tous les écouteurs pour un événement donné
     */
    public function clearListeners(string $event): void;

    /**
     * Déclencher un événement
     *
     * Peut accepter un EventInterface ou en créer un s'il n'est pas passé
     *
     * @param EventInterface|string $event
     * @param object|string         $target
     * @param array|object          $argv
     *
     * @return mixed
     */
    public function trigger($event, $target = null, $argv = []);
}
