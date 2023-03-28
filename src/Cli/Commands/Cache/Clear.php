<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Cache;

use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Loader\Services;

/**
 * Efface le cache actuel.
 */
class Clear extends Command
{
    /**
     * {@inheritDoc}
     */
    protected $group = 'Cache';

    /**
     * {@inheritDoc}
     */
    protected $name = 'cache:clear';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Efface les caches système actuels.';

     /**
     * {@inheritDoc}
     */
    protected $service = 'Service de mise en cache';

    /**
     * {@inheritDoc}
     */
    protected $arguments = [
        'driver' => 'Le pilote de cache à utiliser',
    ];

    
    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $config  = config('cache');
        $handler = $this->argument('driver', $config['handler']);

        if (! array_key_exists($handler, $config['valid_handlers'])) {
            $this->fail($handler . 'n\'est pas un gestionnaire de cache valide.');

            return;
        }

        $config['handler'] = $handler;
        $cache             = Services::cache($config);

        if (! $cache->clear()) {
            // @codeCoverageIgnoreStart
            $this->fail('Erreur lors de l\'effacement du cache.');

            return;
            // @codeCoverageIgnoreEnd
        }

        $this->ok('Cache vidé.');
    }
}
