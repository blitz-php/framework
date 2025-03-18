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
    /**
     * @var string Groupe
     */
    protected $group = 'BlitzPHP';

    protected $service = 'Service de configuration';

    /**
     * @var string Nom
     */
    protected $name = 'config:publish';

    /**
     * @var string Description
     */
    protected $description = 'Publie des fichiers de configuration dans votre application.';

    /**
     * {@inheritDoc}
     */
    protected $arguments = [
        'name' => 'Le nom du fichier de configuration à publier.',
    ];

    /**
     * {@inheritDoc}
     */
    protected $options = [
        '--all'   => 'Publie tous les fichiers de configuration.',
        '--force' => 'Ecrase les fichiers de configuration existants.',
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $config = $this->getBaseConfigurationFiles();

        if (null === $this->argument('name') && $this->option('all')) {
            foreach ($config as $key => $file) {
                $this->publish($key, $file, config_path($key . '.php'));
            }

            return EXIT_SUCCESS;
        }

        if (null === $name = $this->argument('name')) {
            $choices = [];

            foreach (array_keys($config) as $key => $val) {
                $choices[$key + 1] = $val;
            }

            $name = $this->choices('Quel fichier de configuration souhaitez-vous publier ?', $choices);
            $name = $choices[$name] ?? null;
        }

        if (null !== $name && ! isset($config[$name])) {
            $this->error('Fichier de configuration non reconnu.');

            return EXIT_ERROR;
        }

        $this->eol()->publish($name, $config[$name], config_path($name . '.php'));
    }

    /**
     * Publier le fichier donné vers la destination donnée.
     */
    protected function publish(string $name, string $file, string $destination)
    {
        if (file_exists($destination) && ! $this->option('force')) {
            $this->error("Le fichier de configuration '{$name}' existe déjà.");

            return;
        }

        copy($file, $destination);

        $this->info("Fichier de configuration '{$name}' publié.");
    }

    /**
     * Récupère un tableau contenant les fichiers de configuration de base.
     */
    protected function getBaseConfigurationFiles(): array
    {
        $config = [];

        foreach (Finder::create()->files()->name('*.php')->in(SYST_PATH . 'Config/stubs') as $file) {
            $name          = basename($file->getRealPath(), '.php');
            $config[$name] = $file->getRealPath();
        }

        return collect($config)->sortKeys()->all();
    }
}
