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
use BlitzPHP\Publisher\Publisher;

/**
 * Découvre toutes les classes Publisher à partir du répertoire « Publishers/ » dans les espaces de noms.
 * Exécute `publish()` à partir de chaque instance, en analysant chaque résultat.
 */
class Publish extends Command
{
    protected string $group = 'BlitzPHP';

    protected string $name = 'publish';

    protected string $description = 'Découvre et exécute toutes les classes Publisher prédéfinies.';

    protected array $arguments = [
        'directory' => ['[Optionel] Le répertoire à analyser dans chaque namespace.'],
    ];

    protected array $options = [
        '-n|--namespace' => ['Le namespace à partir duquel on devra chercher les fichiers à publier. Par défaut, tous les namespaces sont analysés.'],
    ];

    /**
     * {@inheritDoc}
     */
    public function handle()
    {
        $directory = $this->argument('directory', 'Publishers');
        $namespace = $this->option('namespace', '');

        if ([] === $publishers = Publisher::discover($directory, $namespace)) {
            if ($namespace === '') {
                $this->write(lang('Publisher.publishMissing', [$directory]));
            } else {
                $this->write(lang('Publisher.publishMissingNamespace', [$directory, $namespace]));
            }

            return;
        }

        foreach ($publishers as $publisher) {
            if ($publisher->publish()) {
                $this->ok(lang('Publisher.publishSuccess', [
                    $publisher::class,
                    count($publisher->getPublished()),
                    $publisher->getDestination(),
                ]))->eol();
            } else {
                $this->fail(lang('Publisher.publishFailure', [
                    $publisher::class,
                    $publisher->getDestination(),
                ]))->eol();

                foreach ($publisher->getErrors() as $file => $exception) {
                    $this->write($file);
                    $this->fail($exception->getMessage());
                    $this->newLine();
                }
            }
        }
    }
}
