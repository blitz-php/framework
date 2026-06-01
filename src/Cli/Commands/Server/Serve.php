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
    protected string $group       = 'BlitzPHP';
    protected string $name        = 'serve';
    protected string $description = 'Lance le serveur de développement BlitzPHP.';
    protected string $service     = 'Service de lancement du serveur de developpement';
    protected array $options      = [
        '--php'  => ['Le binaire PHP', 'PHP_BINARY'],
        '--host' => ['L\'hôte HTTP', 'localhost'],
        '--port' => ['Le port de l\'hôte HTTP', 3300],
    ];

    /**
     * The number of times to retry if the port is already in use.
     */
    private const RETRIES = 10;

    /**
     * Chemin de base dans lequel sera lancer le server
     */
    protected string $rootDirectory = WEBROOT;

    /**
     * Liste des messages des taches
     */
    protected array $taskMessages = [
        'demarrage' => '', // Message a afficher lors du demarrage du serveur
        'demarrer'  => '', // Message a afficher lorsque le serveur a demarré
    ];

    /**
     * {@inheritDoc}
     */
    public function handle()
    {
		$basePort = (int) ($this->option('port', 3300));
		$status   = EXIT_SUCCESS;
		$host     = $this->option('host', 'localhost');

		for ($offset = 0; $offset <= self::RETRIES; $offset++) {
			$port = $basePort + $offset;

			$this->task($this->taskMessages['demarrage'] ?: 'Demarrage du serveur de developpement');
			sleep(2);

			$this->io->ok($this->taskMessages['demarrer'] ?: 'Le serveur de développement BlitzPHP a démarré sur ');
			$this->writer->boldGreen('http://' . $host . ':' . $port, true);
			$this->write("Appuyez sur Control-C pour arrêter.\n", true);

			passthru(
                $this->buildServeCommand($host, $port),
                $status,
            );

            if ($status === EXIT_SUCCESS) {
                return $status;
            }

			$this->newLine();
		}

		return $status;
    }

    /**
     * Builds the shell command passed to PHP's built-in webserver, escaping
     * every user-influenced argument so it cannot be interpreted by /bin/sh.
     */
    protected function buildServeCommand(string $host, int $port): string
    {
		$php     = 'PHP_BINARY' === ($binary = $this->option('php')) ? PHP_BINARY : $binary;
		$docroot = $this->rootDirectory;
		$rewrite = __DIR__ . '/rewrite.php';

        return sprintf(
            '%s -S %s -t %s %s',
            escapeshellarg($php),
            escapeshellarg($host . ':' . $port),
            escapeshellarg($docroot),
            escapeshellarg($rewrite),
        );
    }
}
