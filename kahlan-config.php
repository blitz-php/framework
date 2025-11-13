<?php
use Kahlan\Filter\Filters;
use Kahlan\Reporter\Coverage;
use Kahlan\Reporter\Coverage\Driver\Xdebug;
use Kahlan\Reporter\Coverage\Driver\Phpdbg;
use Kahlan\Reporter\Coverage\Exporter\Clover;

$commandLine = $this->commandLine();
$commandLine->option('ff', 'default', 1);
$commandLine->option('coverage-scrutinizer', 'default', 'scrutinizer.xml');

// Chemins sources pour la couverture
$namespaces = [
    'BlitzPHP'             => __DIR__ . '/src',
    'BlitzPHP\\Autoloader' => __DIR__ . '/vendor/blitz-php/autoloader',
    'BlitzPHP\\Cache'      => __DIR__ . '/vendor/blitz-php/cache',
    'BlitzPHP\\Session'    => __DIR__ . '/vendor/blitz-php/session',
    'BlitzPHP\\Utilities'  => __DIR__ . '/vendor/blitz-php/utilities',
];

if (!$this->commandLine()->get('src')) {
    $commandLine->set('src', array_values($namespaces));
}

Filters::apply($this, 'reporting', function($next) {
    $reporter = $this->reporters()->get('coverage');
    if (!$reporter || !$this->commandLine()->exists('coverage-scrutinizer')) {
        return $next();
    }

    Clover::write([
        'collector' => $reporter,
        'file' => $this->commandLine()->get('coverage-scrutinizer'),
    ]);

    return $next();
});

Filters::apply($this, 'coverage', function($next) use ($namespaces) {
    if (!extension_loaded('xdebug') && PHP_SAPI !== 'phpdbg') {
        return;
    }
    $reporters = $this->reporters();
    $coverage = new Coverage([
        'verbosity' => $this->commandLine()->get('coverage'),
        'driver'    => PHP_SAPI !== 'phpdbg' ? new Xdebug() : new Phpdbg(),
        'path'      => array_values($namespaces),
        'colors'    => !$this->commandLine()->get('no-colors')
    ]);
    $reporters->add('coverage', $coverage);
});

Filters::apply($this, 'namespaces', function($next) use($namespaces) {
    foreach ($namespaces as $namespace => $path) {
        $this->autoloader()->addPsr4($namespace . '\\', $path);
    }

    return $next();
});

require_once realpath(rtrim(getcwd(), '\\/ ')) . DIRECTORY_SEPARATOR . 'spec' . DIRECTORY_SEPARATOR . 'bootstrap.php';
