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

class ListerComponent extends Component
{
    protected array $items = [];

    public function getItemsProperty()
    {
        $items = array_map(static fn ($item) => $item = '-' . $item, $this->items);

        return $items;
    }
}