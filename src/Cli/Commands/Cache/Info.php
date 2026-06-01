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
 * Affiche des informations sur le cache.
 */
class Info extends Command
{
    /**
     * {@inheritDoc}
     */
    protected string $group = 'Cache';

    /**
     * {@inheritDoc}
     */
    protected string $name = 'cache:info';

    /**
     * {@inheritDoc}
     */
    protected string $description = 'Affiche les informations du cache de fichiers dans le système actuel.';

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

        if ($config['handler'] !== 'file') {
            $this->fail(sprintf(
                'Cette commande ne prend en charge que le gestionnaire de cache de fichiers. Le gestionnaire configuré est "%s".',
                $config['handler'],
            ));

            return EXIT_ERROR;
        }

        $cache  = service('cache', $config);
        $tbody  = [];

		helper('number');

        foreach ($cache->info() as $key => $field) {
            $tbody[] = [
                'nom'               => $key,
                'chemin du serveur' => clean_path($field['server_path']),
                'taille'            => number_to_size($field['size']),
                'date'              => $field['date'],                      // @todo formatter avec Utilities\Date
            ];
        }

        $this->table($tbody, ['head' => 'boldGreen']);

        return EXIT_SUCCESS;
    }
}
