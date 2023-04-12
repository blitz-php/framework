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

class HigherOrderTapProxy
{
    /**
     * Créez une nouvelle instance de proxy tactile.
     *
     * @param  mixed  $target La cible étant tapée.
     */
    public function __construct(public mixed $target)
    {

    }

    /**
     * Passez dynamiquement les appels de méthode à la cible.
     */
    public function __call(string $method, array $parameters = []): mixed
    {
        $this->target->{$method}(...$parameters);

        return $this->target;
    }
}
