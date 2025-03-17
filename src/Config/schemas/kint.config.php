<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Nette\Schema\Expect;

return Expect::structure([
    'plugins'                => Expect::array(),
    'depth_limit'            => Expect::int(6),
    'display_called_from'    => Expect::bool(true),
    'expanded'               => Expect::bool(false),
    'rich_theme'             => Expect::string('original.css'),
    'rich_folder'            => Expect::bool(false),
    'rich_value_plugins'     => Expect::arrayOf('string', 'string'),
    'rich_tab_plugins'       => Expect::arrayOf('string', 'string'),
    'cli_colors'             => Expect::bool(true),
    'cli_force_utf8'         => Expect::bool(false),
    'cli_detect_width'       => Expect::bool(true),
    'cli_min_terminal_width' => Expect::int(40),
])->otherItems();
