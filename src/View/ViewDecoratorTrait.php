<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\View;

use BlitzPHP\Exceptions\ViewException;

trait ViewDecoratorTrait
{
    /**
     * Exécute la sortie générée via tous les décorateurs de vue déclarés.
     */
    protected function decorate(string $output): string
    {
        foreach (config('view.decorators') as $decorator) {
            if (! is_subclass_of($decorator, ViewDecoratorInterface::class, true)) {
                throw ViewException::invalidDecorator($decorator);
            }

            $output = $decorator::decorate($output);
        }

        return $output;
    }
}
