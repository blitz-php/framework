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
 * Affiche des informations sur le cache.
 */
class Info extends Command
{
    /**
     * {@inheritDoc}
     */
    protected $group = 'Cache';

    /**
     * {@inheritDoc}
     */
    protected $name = 'cache:info';

    /**
     * {@inheritDoc}
     */
    protected $description = 'Affiche les informations du cache de fichiers dans le système actuel.';

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
        $config = config('cache');
        helper('number');

        if ($config['handler'] !== 'file') {
            $this->fail('Cette commande ne prend en charge que le gestionnaire de cache de fichiers.');

            return;
        }

        $cache  = Services::cache($config);
        $caches = $cache->info();
        $tbody  = [];

        foreach ($caches as $key => $field) {
            $tbody[] = [
                'nom'               => $key,
                'chemin du serveur' => clean_path($field['server_path']),
                'taille'            => number_to_size($field['size']),
                'date'              => $field['date'],                      // @todo formatter avec Utilities\Date
            ];
        }

        $this->table($tbody, ['head' => 'boldGreen']);
    }
}
