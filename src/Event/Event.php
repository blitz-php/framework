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

/**
 * Event
 *
 * @credit      https://www.phpclasses.org/package/9961-PHP-Manage-events-implementing-PSR-14-interface.html - Kiril Savchev <k.savchev@gmail.com>
 */
class Event implements EventInterface
{
    /**
     * le nom de l'evenement
     *
     * @var string
     */
    protected $name = '';

    /**
     * Les paramètres de l'evenement
     *
     * @var array
     */
    protected $params = [];

    /**
     * Indicateur indiquant si l'événement doit être arrêté lors du déclenchement
     *
     * @var bool
     */
    protected $isPropagationStopped = false;

    /**
     * Creation de l'evenement
     *
     * @param mixed|null $target La cible de l'evenement
     */
    public function __construct(?string $name = '', protected $target = null, array $params = [])
    {
        $this->name   = $name;
        $this->params = $params;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * {@inheritDoc}
     */
    public function getParam(string $name)
    {
        return $this->params[$name] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * {@inheritDoc}
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * {@inheritDoc}
     */
    public function setTarget($target): void
    {
        $this->target = $target;
    }

    /**
     * {@inheritDoc}
     */
    public function isPropagationStopped(): bool
    {
        return $this->isPropagationStopped;
    }

    /**
     * {@inheritDoc}
     */
    public function stopPropagation(bool $flag = true): void
    {
        $this->isPropagationStopped = $flag;
    }
}
