<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Database\Migration;

use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Database\Migration\Runner;
use BlitzPHP\Loader\Services;
use InvalidArgumentException;

/**
 * Execute toutes les nouvelles migrations.
 */
class Migrate extends Command
{
    /**
     * @var string Groupe
     */
    protected $group = 'Database';

    /**
     * @var string Nom
     */
    protected $name = 'migrate';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Recherche et exécute toutes les nouvelles migrations dans la base de données.';

    /**
     * {@inheritDoc}
     */
    protected $service = 'Service de gestion de base de données';
    
    /**
     * {@inheritDoc}
     */
    protected $options = [
        '-n, --namespace' => 'Défini le namespace de la migration',
        '-g, --group'     => 'Défini le groupe de la base de données',
        '--all'           => 'Défini pour tous les namespaces, ignore l\'option (-n)',
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $this->colorize(lang('Migrations.latest'), 'yellow');

        $namespace = $this->getOption('namespace');
        $group     = $this->getOption('group');
        $config    = config('database');
        
        if (empty($group)) {
            $group = $config['group'] ?? 'auto';

            if ($group === 'auto') {
                $group = on_test() ? 'test' : (on_prod() ? 'production' : 'development');
            }

            if (! isset($config[$group])) {
                $group = 'default';
            }
        }
        
        if (is_string($group) && ! isset($config[$group]) && strpos($group, 'custom-') !== 0) {
            throw new InvalidArgumentException($group . ' is not a valid database connection group.');
        }

        $runner = Runner::instance(config('migrations'), $config[$group]);

        $runner->clearMessages();

        if ($this->getOption('all')) {
            $namespaces = array_keys(Services::autoloader()->getNamespace());
        } elseif ($namespace) {
            $namespaces = [$namespace];
        } else {
            $namespaces = [APP_NAMESPACE];
        }

        $locator = Services::locator();

        $files = [];
        foreach ($namespaces as $namespace) {
            $files[$namespace] = $locator->listNamespaceFiles($namespace, '/Database/Migrations/');
        }

        $runner->setFiles($files);

        if (! $runner->latest($group)) {
            $this->fail(lang('Migrations.generalFault')); // @codeCoverageIgnore
        }

        $messages = $runner->getMessages();

        foreach ($messages as $message) {
            $this->colorize($message['message'], $message['color']);
        }

        $this->success(lang('Migrations.migrated'));
    }
}
