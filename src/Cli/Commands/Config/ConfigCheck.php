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
use BlitzPHP\Exceptions\ConfigException;
use BlitzPHP\Utilities\Iterable\Arr;

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
        $name = $this->argument('config', '');

        if ($name === '' || $name === '0') {
            $this->fail('Vous devez spécifier la configuration à utiliser pour la vérification.')->eol();
            $this->write('Exemple: config:check app')->eol();
            $this->write('         config:check auth');

            return EXIT_ERROR;
        }

        try {
            $data = config()->get($name);
        } catch (ConfigException) {
            $this->fail('Aucune configuration trouvée pour: ' . $name);

            return EXIT_ERROR;
        }

        if (! is_array($data)) {
            $this->title($name, $this->formatValue($data));

            return EXIT_SUCCESS;
        }

        $this->title($name);

        foreach (Arr::dot($data) as $key => $value) {
            if (is_array($value = $this->formatValue($value))) {
                $options = $value[1];
                $value   = $value[0];
            } else {
                $options = ['fg' => Color::CYAN];
            }

            if (str_contains($key = $this->formatKey($key), '->')) {
                $options['fg'] = Color::PURPLE;
            }

            $this->justify($key, $this->formatValue($value), ['second' => $options]);
        }

        return EXIT_SUCCESS;
    }

    /**
     * Rendu du titre.
     */
    public function title(string $title, mixed $subtitle = ''): void
    {
        if (is_array($subtitle)) {
            $options  = $subtitle[1];
            $subtitle = $subtitle[0];
        }

        $this->justify($title, $subtitle, [
            'first'  => ['fg' => Color::GREEN, 'bold' => 1],
            'second' => $options ?? [],
        ]);
    }

    /**
     * Formate la clé de configuration donnée.
     */
    protected function formatKey(string $key): string
    {
        return preg_replace_callback(
            '/(.*)\.(.*)$/',
            static fn ($matches) => sprintf(
                '%s -> %s',
                str_replace('.', ' ⇁ ', $matches[1]),
                $matches[2]
            ),
            $key
        );
    }

    /**
     * Formate la valeur de configuration donnée.
     *
     * @return array|string
     */
    protected function formatValue(mixed $value)
    {
        return match (true) {
            is_bool($value)                           => [$value ? 'Enabled' : 'Disabled', ['fg' => $value ? Color::GREEN : Color::YELLOW]],
            null === $value                           => 'NULL',
            $value === ''                             => ['Empty value', ['fg' => Color::RED]],
            is_numeric($value)                        => $value,
            is_array($value) && array_is_list($value) => [implode(', ', $value), ['fg' => Color::PURPLE]],
            is_array($value)                          => '[]',
            is_object($value)                         => get_class($value),
            is_string($value)                         => $value,
            default                                   => print_r($value, true),
        };
    }
}
