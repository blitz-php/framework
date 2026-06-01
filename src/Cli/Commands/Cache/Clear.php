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

/**
 * Efface le cache actuel.
 */
class Clear extends Command
{
    /**
     * {@inheritDoc}
     */
    protected string $group = 'Cache';

    /**
     * {@inheritDoc}
     */
    protected string $name = 'cache:clear';

    /**
     * {@inheritDoc}
     */
    protected string $description = 'Efface les caches système actuels.';

    /**
     * {@inheritDoc}
     */
    protected string $service = 'Service de mise en cache';

    /**
     * {@inheritDoc}
     */
    protected array $arguments = [
        'driver' => ['Le pilote de cache à utiliser'],
    ];

    /**
     * {@inheritDoc}
     */
    public function handle()
    {
        $config = config('cache');
        $driver = $this->argument('driver', $this->parameter(0, $config['handler']));

        if (! array_key_exists($driver, $config['valid_handlers'])) {
            $this->fail(lang('Cache.invalidHandler', [$driver]));

            return EXIT_ERROR;
        }

        if (! service('cache', ['handler' => $driver] + $config)->clear()) {
            $this->fail(sprintf('Erreur lors de l\'effacement du cache pour le pilote %s.', $driver));

            return EXIT_ERROR;
        }

        $this->ok(sprintf('Cache vidé pour le pilote %s.', $driver));

        return EXIT_SUCCESS;
    }
}
