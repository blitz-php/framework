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
    'plugins'                => [],
    'depth_limit'            => 6,
    'display_called_from'    => true,
    'expanded'               => false,
    'rich_theme'             => 'original.css',
    'rich_folder'            => false,
    'rich_sort'              => \Kint\Renderer\AbstractRenderer::SORT_FULL,
    'rich_value_plugins'     => [],
    'rich_tab_plugins'       => [],
    'cli_colors'             => true,
    'cli_force_utf8'         => false,
    'cli_detect_width'       => true,
    'cli_min_terminal_width' => 40,
];
