<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

return [
    'active_adapter'  => 'native',
    'compress_output' => 'auto',
    'view_base'       => VIEW_PATH,
    'debug'           => 'auto',
    'shared'          => static fn (): array => [],
    'decorators'      => [],
    'adapters'        => ['native' => ['extension' => 'php', 'save_data' => true]],
];
