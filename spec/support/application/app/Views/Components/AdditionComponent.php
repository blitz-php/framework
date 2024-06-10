<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Spec\BlitzPHP\App\Views\Components;

use BlitzPHP\View\Components\Component;

class AdditionComponent extends Component
{
    public int $value = 2;

    public function mount(?int $number = null, bool $skipAddition = false): void
    {
        $this->value = ! $skipAddition
            ? $this->value + (int) $number
            : $this->value;
    }
}
