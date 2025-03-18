<?php

declare(strict_types=1);

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use PhpCsFixer\ConfigInterface;
use PhpCsFixer\Finder;

/** @var ConfigInterface $config */
$config = require __DIR__ . '/.php-cs-fixer.dist.php';

$finder = Finder::create()
    ->files()
    ->in([
        __DIR__ . '/src/Config/stubs',
    ]);

$overrides = [
    'header_comment' => false,
];

return $config
    ->setFinder($finder)
    ->setCacheFile('build/.php-cs-fixer.no-header.cache')
    ->setRules(array_merge($config->getRules(), $overrides));
