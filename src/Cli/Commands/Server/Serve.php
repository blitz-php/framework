<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Server;

use BlitzPHP\Cli\Console\Command;

/**
 * Lancer le serveur de développement PHP
 *
 * Non testable, car il lance phpunit pour une boucle :-/
 *
 * @codeCoverageIgnore
 */
class Serve extends Command
{
    /**
     * @var string Groupe
     */
    protected $group = 'BlitzPHP';

    /**
     * @var string Nom
     */
    protected $name = 'serve';

    /**
     * @var string Description
     */
    protected $description = 'Lance le serveur de développement BlitzPHP.';

    /**
     * @var string Usage
     */
    protected $usage = 'php klinge serve';

    /**
     * @var string
     */
    protected $service = 'Service de lancement du serveur de developpement';

    /**
     * @var array Options
     */
    protected $options = [
        '--php'  => ['The PHP Binary [default: "PHP_BINARY"]', PHP_BINARY],
        '--host' => ['The HTTP Host [default: "localhost"]', 'localhost'],
        '--port' => ['The HTTP Host Port [default: "3300"]', 3300],
    ];

    /**
     * Le décalage de port actuel.
     *
     * @var int
     */
    protected $portOffset = 0;

    /**
     * Le nombre maximum de ports à partir desquels tenter de servir
     *
     * @var int
     */
    protected $tries = 10;

    /**
     * Chemin de base dans lequel sera lancer le server
     *
     * @var string
     */
    protected $rootDirectory = WEBROOT;

    /**
     * Liste des messages des taches
     *
     * @var array
     */
    protected $taskMessages = [
        'demarrage' => '', // Message a afficher lors du demarrage du serveur
        'demarrer' => '', // Message a afficher lorsque le serveur a demarré
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $options = [
            'php'  => PHP_BINARY,
            'host' => 'localhost',
            'port' => 3300,
        ];

        if (isset($this->options['--php'])) {
            $options['php'] = $this->options['--php'][1] ?? PHP_BINARY;
        }
        if (isset($this->options['--host'])) {
            $options['host'] = $this->options['--host'][1] ?? 'localhost';
        }
        if (isset($this->options['--port'])) {
            $options['port'] = $this->options['--port'][1] ?? 3300;
        }

        $php  = escapeshellarg($params['php'] ?: $options['php']);
        $host = $params['host'] ?: $options['host'];
        $port = (int) ($params['port'] ?: $options['port']) + $this->portOffset;

        $this->task($this->taskMessages['demarrage'] ?: 'Demarrage du serveur de developpement');
        sleep(2);

        $this->io->ok($this->taskMessages['demarrer'] ?: 'Le serveur de développement BlitzPHP a démarré sur ');
        $this->writer->boldGreen('http://' . $host . ':' . $port, true);
        $this->write("Appuyez sur Control-C pour arrêter.\n", true);

        // Appelez le serveur Web intégré de PHP, en veillant à définir notre
        // chemin de base vers le dossier public et pour utiliser le fichier de réécriture
        // pour s'assurer que notre environnement est défini et qu'il simule le mod_rewrite de base.
        passthru($php . ' -S ' . $host . ':' . $port . ' -t ' . escapeshellarg($this->rootDirectory), $status);

        if ($status && $this->portOffset < $this->tries) {
            $this->portOffset++;

            $this->execute($params);
        }
    }
}
