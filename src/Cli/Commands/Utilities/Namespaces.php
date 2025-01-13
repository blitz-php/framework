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

use Ahc\Cli\Output\Color;
use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Container\Services;

/**
 * Répertorie les namespace dans Config\autoload.php avec le chemin d'accès du serveur complet.
 * Vous aide à vérifier que vous avez la configuration des namespaces correctement.
 */
class Namespaces extends Command
{
    /**
     * @var string Groupe
     */
    protected $group = 'BlitzPHP';

    /**
     * @var string Nom
     */
    protected $name = 'namespaces';

    /**
     * @var string Description
     */
    protected $description = 'Vérifie que vos namespaces sont correctement configurés.';

    protected $service = 'Service de configuration';

    /**
     * @var array Options de la commande
     */
    protected $options = [
        '-b' => 'Afficher uniquement les namespaces de la config de BlitzPHP.',
        '-r' => 'Afficher chaînes brutes du chemin.',
        '-m' => 'Spécifiez la longueur maximale des chaînes de chemin d\'accès à la sortie. Defaut: 60.',
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $m = (int) $this->option('m', 60);

        $tbody = true === $this->option('b')
            ? $this->outputBlitzNamespaces($m)
            : $this->outputAllNamespaces($m);

        $table = [];

        foreach ($tbody as $namespace) {
            $table[] = [
                'Namespace' => $namespace[0],
                'Chemin'    => $namespace[1],
                'Trouvé?'   => $namespace[2] ? 'Oui' : 'Manque',
            ];
        }

        $this->center('Namespaces disponible dans votre application', ['fg' => Color::CYAN]);
        $this->border();

        $this->table($table);
    }

    private function outputAllNamespaces(int $maxLength): array
    {
        $autoloader = service('autoloader');

        $tbody = [];

        foreach ($autoloader->getNamespace() as $ns => $paths) {
            foreach ($paths as $path) {
                if (null !== $this->option('r')) {
                    $pathOutput = $this->truncate($path, $maxLength);
                } else {
                    $pathOutput = $this->truncate(clean_path($path), $maxLength);
                }

                $tbody[] = [
                    $ns,
                    $pathOutput,
                    is_dir($path),
                ];
            }
        }

        return $tbody;
    }

    private function truncate(string $string, int $max): string
    {
        $length = strlen($string);

        if ($length > $max) {
            return substr($string, 0, $max - 3) . '...';
        }

        return $string;
    }

    private function outputBlitzNamespaces(int $maxLength): array
    {
        $config = (object) config('autoload');

        $tbody = [];

        foreach ($config->psr4 as $ns => $paths) {
            foreach ((array) $paths as $path) {
                if (null !== $this->option('r')) {
                    $pathOutput = $this->truncate($path, $maxLength);
                } else {
                    $pathOutput = $this->truncate(clean_path($path), $maxLength);
                }

                $path = realpath($path) ?: $path;

                $tbody[] = [
                    $ns,
                    $pathOutput,
                    is_dir($path),
                ];
            }
        }

        return $tbody;
    }
}
