<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Kint\Renderer\AbstractRenderer;
use Nette\Schema\Expect;

return Expect::structure([
    'plugins'                => Expect::array(),
    'depth_limit'            => Expect::int()->default(6),
    'display_called_from'    => Expect::bool()->default(true),
    'expanded'               => Expect::bool()->default(false),
    'rich_theme'             => Expect::string()->default('original.css'),
    'rich_folder'            => Expect::bool()->default(false),
    'rich_sort'              => Expect::int()->default(AbstractRenderer::SORT_FULL),
    'rich_value_plugins'     => Expect::arrayOf('string', 'string'),
    'rich_tab_plugins'       => Expect::arrayOf('string', 'string'),
    'cli_colors'             => Expect::bool()->default(true),
    'cli_force_utf8'         => Expect::bool()->default(false),
    'cli_detect_width'       => Expect::bool()->default(true),
    'cli_min_terminal_width' => Expect::int()->default(40),
])->otherItems();
