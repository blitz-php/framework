<?php

/**
 * This file is part of Blitz PHP framework - Contracts.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Contracts\Traits;

trait CliMessage
{
    /**
     * @var array messages pour la console
     */
    private $messages = [];

    /**
     * Renvoi les messages pour la console
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Rajoute un nouveau message a la pile de message
     */
    private function pushMessage(string $message, string $color = 'green'): self
    {
        $this->messages[] = compact('message', 'color');

        return $this;
    }
}
