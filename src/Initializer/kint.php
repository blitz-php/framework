<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Kint\Kint;
use Kint\Renderer\CliRenderer;
use Kint\Renderer\RichRenderer;

helper('kint');

if (is_online()) {
    return;
}

$config = (object) config('kint');

Kint::$depth_limit         = $config->depth_limit;
Kint::$display_called_from = $config->display_called_from;
Kint::$expanded            = $config->expanded;

if (! empty($config->plugins)) {
    Kint::$plugins = $config->plugins;
}

RichRenderer::$theme  = $config->rich_theme;
RichRenderer::$folder = $config->rich_folder;

if (! empty($config->rich_value_plugins)) {
    RichRenderer::$value_plugins = $config->rich_value_plugins;
}
if (! empty($config->rich_tab_plugins)) {
    RichRenderer::$tab_plugins = $config->rich_tab_plugins;
}

CliRenderer::$cli_colors         = $config->cli_colors;
CliRenderer::$force_utf8         = $config->cli_force_utf8;
CliRenderer::$detect_width       = $config->cli_detect_width;
CliRenderer::$min_terminal_width = $config->cli_min_terminal_width;
