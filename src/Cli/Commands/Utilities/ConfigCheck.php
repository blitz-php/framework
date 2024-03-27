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
use Kint\Kint;

/**
 * Verifie les valeurs d'une configuartion.
 */
class ConfigCheck extends Command
{
    /**
     * @var string Groupe
     */
    protected $group = 'BlitzPHP';

    protected $service = 'Service de configuration';

    /**
     * @var string Nom
     */
    protected $name = 'config:check';

    /**
     * @var string Description
     */
    protected $description = 'Vérifie les valeurs d\'un fichier de configuration.';

    /**
     * Arguments de la commande
     *
     * @var array<string, string>
     */
    protected $arguments = [
        'config' => 'La configuration dont on souhaite vérifier les valeurs.',
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        if (empty($file = strtolower($this->argument('config', '')))) {
            $this->fail('Vous devez spécifier la configuration à utiliser pour la vérification.')->eol();
            $this->write('  Usage: ' . $this->usage)->eol();
            $this->write('Exemple: config:check app')->eol();
            $this->write('         config:check \'BlitzPHP\Schild\Config\auth\'');

            return EXIT_ERROR;
        }

        if (null === $config = config()->get($file)) {
            $this->fail('Aucune configuration trouvée pour: ' . $file);
        }

        $this->writer->warn('Valeurs de la configuration ' . $this->color->ok($file));
        $this->eol()->border()->eol();

        if (defined('KINT_DIR') && Kint::$enabled_mode !== false) {
            $this->write($this->getKintDump($config));
        } else {
            $this->colorize($this->getVarDump($config), 'cyan');
        }

        return EXIT_SUCCESS;
    }

    /**
     * Obtiens le dump de la config via la function d() de Kint
     */
    private function getKintDump(array $config): string
    {
        ob_start();
        d($config);
        $output = ob_get_clean();

        $output = trim($output);

        $lines = explode("\n", $output);
        array_splice($lines, 0, 3);
        array_splice($lines, -3);

        return implode("\n", $lines);
    }

    /**
     * Obtiens le dump de la config via la function var_dump() de PHP
     */
    private function getVarDump(array $config): string
    {
        ob_start();
        var_dump($config);
        $output = ob_get_clean();

        return preg_replace(
            '!.*Commands/Utilities/ConfigCheck.php.*\n!u',
            '',
            $output
        );
    }
}
