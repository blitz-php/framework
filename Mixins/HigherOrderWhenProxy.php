<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Traits\Mixins;

class HigherOrderWhenProxy
{
    /**
     * La condition pour le proxy.
     */
    protected bool $condition = false;

    /**
     * Indique si le proxy a une condition.
     */
    protected bool $hasCondition = false;

    /**
     * Déterminez si la condition doit être annulée.
     */
    protected bool $negateConditionOnCapture = false;

    /**
     * Créez une nouvelle instance de proxy.
     *
     * @param mixed $target La cible étant conditionnellement opérée.
     */
    public function __construct(protected mixed $target)
    {
    }

    /**
     * Définissez la condition sur le proxy.
     */
    public function condition(bool $condition): self
    {
        [$this->condition, $this->hasCondition] = [$condition, true];

        return $this;
    }

    /**
     * Indique que la condition doit être annulée.
     */
    public function negateConditionOnCapture(): self
    {
        $this->negateConditionOnCapture = true;

        return $this;
    }

    /**
     * Proxy accédant à un attribut sur la cible.
     */
    public function __get(string $key): mixed
    {
        if (! $this->hasCondition) {
            $condition = $this->target->{$key};

            return $this->condition($this->negateConditionOnCapture ? ! $condition : $condition);
        }

        return $this->condition
            ? $this->target->{$key}
            : $this->target;
    }

    /**
     * Proxy un appel de méthode sur la cible.
     */
    public function __call(string $method, array $parameters = []): mixed
    {
        if (! $this->hasCondition) {
            $condition = $this->target->{$method}(...$parameters);

            return $this->condition($this->negateConditionOnCapture ? ! $condition : $condition);
        }

        return $this->condition
            ? $this->target->{$method}(...$parameters)
            : $this->target;
    }
}
