<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Utilities;

use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Loader\DotEnv;

/**
 * Commande pour afficher l'environnement actuel, ou pour définir un nouveau dans le fichier `.env`.
 */
final class Environment extends Command
{
    /**
     * @var string Groupe
     */
    protected $group = 'BlitzPHP';

    /**
     * @var string Nom
     */
    protected $name = 'env';

    /**
     * @var string Description
     */
    protected $description = 'Récupère l\'environnement actuel, ou en définir un nouveau.';

    /**
     * Arguments de la commande
     *
     * @var array<string, string>
     */
    protected $arguments = [
        '[environment]' => '[Optionel] Nouveau environnement à définir. Si aucun n\'est fourni, cela imprimera l\'environnement actuel.',
    ];

    /**
     * Valeurs autorisées pour l'environnement.
     * Tester le travail est exclu puisque klinge ne fonctionnera pas sur elle.
     *
     * @var array<int, string>
     */
    private static array $knownTypes = [
        'production',
        'development',
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        if (null === $env = $this->argument('environment')) {
            $this->write('Votre environnement est actuellement défini comme: ');
            $this->ok(config('app.environment'))->eol();

            return;
        }

		$env = strtolower($env);

        if ($env === 'testing') {
            $this->fail('L\'environnement « test » est réservé aux tests PHPUnit ou Kahlan.');
            $this->fail('Vous ne pourrez pas exécuter klinge sous un environnement « test ».');
            $this->newLine();

            return;
        }

        if (! in_array($env, self::$knownTypes, true)) {
            $this->error(sprintf('Type d\'environnement non valide "%s". Attendu un des "%s".', $env, implode('" et "', self::$knownTypes)));
            $this->newLine();

            return;
        }

        if (! $this->writeNewEnvironmentToEnvFile($env)) {
            $this->error('Erreur dans l\'écriture nouvel environnement dans le fichier `.env`.');
            $this->newLine();

            return;
        }

        $this->success(sprintf('Environnement est modifié avec succès pour "%s".', $env));
        $this->write('La constante ENVIRONNEMENT sera modifiée dans la prochaine exécution du script.');
        $this->newLine();
    }

    /**
     * @see https://regex101.com/r/4sSORp/1 for the regex in action
     */
    private function writeNewEnvironmentToEnvFile(string $newEnv): bool
    {
        $baseEnv = ROOTPATH . '.env.example';
        $envFile = ROOTPATH . '.env';

        if (! is_file($envFile)) {
            if (! is_file($baseEnv)) {
                $this->writer->warn('Les deux fichiers `.env.example` et `.env` sont manquants.', true);
                $this->write('Il est impossible d\'écrire le nouveau type d\'environnement.');
                $this->newLine();

                return false;
            }

            copy($baseEnv, $envFile);
        }

        return DotEnv::instance()->replace(['ENVIRONMENT' => $newEnv]);
    }
}
