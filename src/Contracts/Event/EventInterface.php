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
 * Representation d'un evenement
 *
 * Ceci est une copie de l'ancienne interface des evenements PSR-14
 * L'EventManager a été remplacé par l'EventDispatcher mais BlitzPHP continue d'utiliser l'ancienne spécification va savoir pourquoi 😅
 */
interface EventInterface
{
    /**
     * Obtenir le nom de l'événement
     */
    public function getName(): string;

    /**
     * Obtenir la cible/le contexte à partir duquel l'événement a été déclenché
     *
     * @return object|string|null
     */
    public function getTarget();

    /**
     * Obtenir les paramètres passés à l'événement
     */
    public function getParams(): array;

    /**
     * Obtenir un seul paramètre par nom
     *
     * @return mixed
     */
    public function getParam(string $name);

    /**
     * Définir le nom de l'événement
     */
    public function setName(string $name): void;

    /**
     * Définir la cible de l'événement
     *
     * @param object|string|null $target
     */
    public function setTarget($target): void;

    /**
     * Définir les paramètres de l'événement
     */
    public function setParams(array $params): void;

    /**
     * Indiquez si vous souhaitez ou non arrêter la propagation de cet événement
     */
    public function stopPropagation(bool $flag): void;

    /**
     * Cet événement a-t-il indiqué que la propagation de l'événement devait s'arrêter ?
     */
    public function isPropagationStopped(): bool;
}
