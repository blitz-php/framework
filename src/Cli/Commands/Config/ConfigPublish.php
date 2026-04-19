<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Config;

use BlitzPHP\Cli\Console\Command;
use Symfony\Component\Finder\Finder;

class ConfigPublish extends Command
{
    protected string $group       = 'Configuration';
    protected string $service     = 'Service de configuration';
    protected string $name        = 'config:publish';
    protected string $description = 'Publie des fichiers de configuration dans votre application.';
    protected array $arguments    = [
        'name' => ['Le nom du fichier de configuration à publier.'],
    ];
    protected array $options = [
        '--all'             => ['Publie tous les fichiers de configuration.'],
        '--skip-on-missing' => ['Ignore les fichiers de configuration non reconnu.'],
        '--force'           => ['Ecrase les fichiers de configuration existants.'],
    ];

    /**
     * {@inheritDoc}
     */
    public function handle()
    {
        $config = $this->getBaseConfigurationFiles();
        $name   = $this->argument('name');
        $skip   = (bool) $this->option('skip-on-missing');
        $names  = [];

        if ($name === null) {
            if ($this->option('all')) {
                $names = array_keys($config);
            } else {
                $names = $this->choices('Quel fichier de configuration souhaitez-vous publier ?', array_keys($config));
            }
        } elseif (is_string($name)) {
            $names = explode(',', $name);
        }

        if ($names === []) {
            $this->badge()->info('Aucune configuration à publier');

            return EXIT_SUCCESS;
        }

        $published = 0;

        foreach ($names as $name) {
            if (! isset($config[$name])) {
                $this->badge()->errorFull("Fichier de configuration '{$name}' non reconnu.");

                if (! $skip) {
                    return EXIT_ERROR;
                }

                continue;
            }

            $published++;

            $this->eol()->publish($name, $config[$name], config_path($name));
        }

        $this->eol()->success(sprintf('%d fichiers publié%s avec succès', $published, $published > 1 ? 's' : ''));

        return EXIT_SUCCESS;
    }

    /**
     * Publier le fichier donné vers la destination donnée.
     */
    protected function publish(string $name, string $file, string $destination)
    {
        if (file_exists($destination) && ! $this->option('force')) {
            $this->badge()->warningFull("Le fichier de configuration '{$name}' existe déjà.");

            return;
        }

        copy($file, $destination);

        $this->badge()->successFull("Fichier de configuration '{$name}' publié.");
    }

    /**
     * Récupère un tableau contenant les fichiers de configuration de base.
     */
    protected function getBaseConfigurationFiles(): array
    {
        $config = [];

        foreach (Finder::create()->files()->name('*.php')->in(SYST_PATH . 'Config/stubs') as $file) {
            $name          = basename($path = $file->getRealPath(), '.php');
            $config[$name] = $path;
        }

        $registrars = config()->registrar('config');

        foreach ($registrars as $key => $value) {
            $path = dirname($key);

            foreach ($value as $name) {
                $config[$name] = $path . DIRECTORY_SEPARATOR . $name . '.php';
            }
        }

        return collect($config)->sortKeys()->all();
    }
}
