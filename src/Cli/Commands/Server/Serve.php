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
    protected string $group = 'BlitzPHP';

    protected string $name = 'serve';

    protected string $description = 'Lance le serveur de développement BlitzPHP.';

    protected string $usage = 'php klinge serve';

    protected string $service = 'Service de lancement du serveur de developpement';

    protected array $options = [
        '--php'  => ['Le binaire PHP', 'PHP_BINARY'],
        '--host' => ['L\'hôte HTTP', 'localhost'],
        '--port' => ['Le port de l\'hôte HTTP', 3300],
    ];

    /**
     * Le décalage de port actuel.
     */
    protected int $portOffset = 0;

    /**
     * Le nombre maximum de ports à partir desquels tenter de servir
     */
    protected int $tries = 10;

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
        $binary = $this->option('php', PHP_BINARY);
        $php    = escapeshellarg($binary === 'PHP_BINARY' ? PHP_BINARY : $binary);
        $host   = $this->option('host', 'localhost');
        $port   = (int) ($this->option('port', 3300)) + $this->portOffset;

        $this->task($this->taskMessages['demarrage'] ?: 'Demarrage du serveur de developpement');
        sleep(2);

        $this->io->ok($this->taskMessages['demarrer'] ?: 'Le serveur de développement BlitzPHP a démarré sur ');
        $this->writer->boldGreen('http://' . $host . ':' . $port, true);
        $this->write("Appuyez sur Control-C pour arrêter.\n", true);

        // Définissez le chemin d’accès du contrôleur frontal sur Racine du document.
        $docroot = escapeshellarg($this->rootDirectory);

        // Imitez la fonctionnalité mod_rewrite d’Apache avec les paramètres utilisateur.
        $rewrite = escapeshellarg(__DIR__ . '/rewrite.php');

        // Appelez le serveur Web intégré de PHP, en veillant à définir notre
        // chemin de base vers le dossier public et pour utiliser le fichier de réécriture
        // pour s'assurer que notre environnement est défini et qu'il simule le mod_rewrite de base.
        passthru($php . ' -S ' . $host . ':' . $port . ' -t ' . $docroot . ' ' . $rewrite, $status);

        if ($status && $this->portOffset < $this->tries) {
            $this->portOffset++;

            $this->handle();
        }
    }
}
