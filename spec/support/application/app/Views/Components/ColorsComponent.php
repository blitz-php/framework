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

class ColorsComponent extends Component
{
    public string $color = '';

    public function colorType(): string
    {
        $warmColors = ['red', 'orange', 'yellow'];
        $coolColors = ['green', 'blue', 'purple'];

        if (in_array($this->color, $warmColors, true)) {
            return 'warm';
        }

        if (in_array($this->color, $coolColors, true)) {
            return 'cool';
        }

        return 'unknown';
    }
}
