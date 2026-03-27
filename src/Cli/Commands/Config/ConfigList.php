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

use Ahc\Cli\Output\Color;
use BlitzPHP\Cli\Console\Command;

class ConfigList extends Command
{
    protected string $group       = 'Configuration';
    protected string $service     = 'Service de configuration';
    protected string $name        = 'config:list';
    protected string $description = 'Liste les fichiers de configuration disponibles et leur statut de publication.';
    protected array $options = [
        '--only-available' => ['Affiche uniquement les configurations disponibles (non publiées).'],
        '--only-published' => ['Affiche uniquement les configurations publiées.'],
        '--namespace'      => ['Filtre par namespace racine (ex: --namespace=BlitzPHP\\\\Database).'],
    ];

    /**
     * {@inheritDoc}
     */
    public function handle()
    {
        $configs = $this->getAvailableConfigurations();

        if ($configs === []) {
            $this->badge()->info('Aucune configuration disponible à afficher.');
            return EXIT_SUCCESS;
        }

        // Filtrag
        $onlyAvailable   = (bool) $this->option('only-available');
        $onlyPublished   = (bool) $this->option('only-published');
        $namespaceFilter = $this->option('namespace');

        // Application des filtres
        $filteredConfigs = $configs;
        
        if ($onlyAvailable) {
            $filteredConfigs = array_filter($filteredConfigs, fn($c) => !$c['published']);
        }
        if ($onlyPublished) {
            $filteredConfigs = array_filter($filteredConfigs, fn($c) => $c['published']);
        }
        if ($namespaceFilter) {
            $filteredConfigs = array_filter($filteredConfigs, function($c) use ($namespaceFilter) {
                return stripos($c['namespace'], $namespaceFilter) !== false;
            });
        }

        if ($filteredConfigs === []) {
            $this->badge()->info('Aucune configuration ne correspond aux critères.');
            return EXIT_SUCCESS;
        }
        
        $this->displayAsTable($filteredConfigs);
        $this->resume($configs, count($filteredConfigs));
    
        return EXIT_SUCCESS;
    }

    /**
     * Affiche les configurations sous forme de tableau
     */
    protected function displayAsTable(array $configs): void
    {
        // Construction des données du tableau
        $rows = [];
        
        foreach ($configs as $config) {
            $status = $config['published'] 
                ? $this->color->ok('Publié')
                : $this->color->error('Non publié');
            
            $namespace  = $config['namespace'];
            $sourcePath = $this->shortenPath($config['path'], 40);
            
            $rows[] = [$config['name'], $status, $namespace, $sourcePath];
            
            // Ajouter une ligne supplémentaire pour le chemin de publication si publié
            if ($config['published'] && isset($config['published_path'])) {
                $pubPath = $this->shortenPath($config['published_path'], 60);
                $rows[]  = ['', '', $this->color->comment('└─ Publié vers :'), $pubPath];
            }
            
            // Ajouter une ligne pour l'override si présent
            if (isset($config['namespace_override'])) {
                $override = $config['namespace_override'];
                $rows[]   = ['', '', $this->color->comment('└─ Surchargé par :'), $override];
            }
        }
        
        $this->table(
            ['Configuration', 'Statut', 'Namespace', 'Source'],
            $rows, 
            ['head' => 'boldCyan']
        );
    }
    
    /**
     * Affichage du resumé de la configuration
     */
    protected function resume(array $configs, int $displayed): void
    {
        $total     = count($configs);
        $published = count(array_filter($configs, fn($c) => $c['published']));
        $available = $total - $published;
        
        $this->border();
        $this->justify('Total', (string) $total);
        $this->justify('Affichées', (string) $displayed, [
            'second' => ['fg' => Color::CYAN, 'bold' => true],
        ]);
        $this->justify('Publiées', (string) $published, [
            'second' => ['fg' => Color::GREEN, 'bold' => true],
        ]);
        $this->justify('Non publiées', (string) $available, [
            'second' => ['fg' => Color::RED, 'bold' => true],
        ]);
    }

    /**
     * Récupère toutes les configurations disponibles avec leur statut
     */
    protected function getAvailableConfigurations(): array
    {
        $configs = [];
        
        // 1. Configurations du framework (stubs)
        foreach ($this->getFrameworkConfigurations() as $name => $path) {
            $configs[$name] = [
                'name'           => $name,
                'source'         => 'Framework',
                'namespace'      => 'BlitzPHP',
                'path'           => $path,
                'published'      => $this->isPublished($name),
                'published_path' => $this->getPublishedPath($name),
            ];
        }
        
        // 2. Configurations des packages (registrars)
        $registrars = $this->getPackageConfigurations();
        foreach ($registrars as $namespace => $items) {
            foreach ($items as $name => $data) {
                // Éviter les doublons (si un package surcharge une config framework)
                if (isset($configs[$name]) && $configs[$name]['source'] === 'Framework') {
                    $configs[$name]['source'] = 'Framework (surchargé)';
                    $configs[$name]['namespace_override'] = $namespace;
                    $configs[$name]['package_path'] = $data['path'];
                } else {
                    $configs[$name] = [
                        'name'           => $name,
                        'source'         => 'Package',
                        'namespace'      => $namespace,
                        'path'           => $data['path'],
                        'published'      => $this->isPublished($name),
                        'published_path' => $this->getPublishedPath($name),
                    ];
                }
            }
        }
        
        // Trier par nom
        ksort($configs);
        
        return $configs;
    }

    /**
     * Récupère les configurations du framework (stubs)
     */
    protected function getFrameworkConfigurations(): array
    {
        $configs   = [];
        $stubsPath = SYST_PATH . 'Config/stubs';
        
        if (is_dir($stubsPath)) {
            foreach (glob($stubsPath . '/*.php') as $file) {
                $name           = basename($file, '.php');
                $configs[$name] = $file;
            }
        }
        
        return $configs;
    }

    /**
     * Récupère les configurations des packages via les registrars
     */
    protected function getPackageConfigurations(): array
    {
        $configs = [];
        
        // Récupérer les registrars depuis le système
        $registrarPaths = config()->registrar('config');
        
        foreach ($registrarPaths as $file => $configNames) {
            $namespace = $this->extractRootNamespace($file);
            $rootpath  = dirname($file);
            
            if ($namespace === null) {
                continue;
            }
            
            foreach ($configNames as $configName) {
                if (! isset($configs[$namespace])) {
                    $configs[$namespace] = [];
                }
                
                $configs[$namespace][$configName] = [
                    'path' => $rootpath . DIRECTORY_SEPARATOR . $configName . '.php',
                    'file' => $file,
                ];
            }
        }
        
        return $configs;
    }

    /**
     * Extrait le namespace racine à partir du chemin du fichier Registrar
     */
    protected function extractRootNamespace(string $file): ?string
    {
        $locator   = service('locator');
        $className = $locator->findQualifiedNameFromPath($file);
        
        if ($className === false) {
            return null;
        }
        
        // Extraire le namespace racine (jusqu'au premier segment après le vendor ou app)
        $parts = explode('\\', $className);
        
        // Si c'est un package du framework BlitzPHP
        if ($parts[0] === 'BlitzPHP') {
            return $parts[0] . '\\' . ($parts[1] ?? '');
        }
        
        // Si c'est un package dans l'application (App namespace)
        if ($parts[0] === APP_NAMESPACE) {
            return implode('\\', array_slice($parts, 0, -2));
        }
        
        // Pour les packages tiers, prendre les deux premiers segments
        return $parts[0] . '\\' . ($parts[1] ?? '');
    }

    /**
     * Vérifie si une configuration a été publiée
     */
    protected function isPublished(string $name): bool
    {
        return file_exists($this->getPublishedPath($name));
    }

    /**
     * Récupère le chemin où la configuration devrait être publiée
     */
    protected function getPublishedPath(string $name): string
    {
        return config_path($name . '.php');
    }

    /**
     * Raccourcit un chemin si trop long
     */
    protected function shortenPath(string $path, int $maxLength): string
    {
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        if (strlen($path) <= $maxLength) {
            return $path;
        }
        
        $parts     = explode(DIRECTORY_SEPARATOR, $path);
        $shortened = [];
        
        // Garder les 2 premiers dossiers et les 2 derniers
        $keepStart = 2;
        $keepEnd   = 2;
        
        if (count($parts) > ($keepStart + $keepEnd)) {
            $shortened = array_merge(
                array_slice($parts, 0, $keepStart),
                ['...'],
                array_slice($parts, -$keepEnd)
            );
            return implode(DIRECTORY_SEPARATOR, $shortened);
        }
        
        return $path;
    }
}
