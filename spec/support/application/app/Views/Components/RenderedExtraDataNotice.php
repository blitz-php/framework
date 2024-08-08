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

class RenderedExtraDataNotice extends Component
{
    public string $message = '4, 8, 15, 16, 23, 42';

    public function render(): string
    {
        return $this->view('notice', ['message' => '42, 23, 16, 15, 8, 4']);
    }
}
