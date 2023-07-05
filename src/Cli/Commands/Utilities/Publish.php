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
    /**
     * @var string Groupe
     */
    protected $group = 'BlitzPHP';

    /**
     * @var string Nom
     */
    protected $name = 'publish';

    /**
     * @var string Description
     */
    protected $description = 'Découvre et exécute toutes les classes Publisher prédéfinies.';

    /**
     * The Command's arguments
     *
     * @var array<string, string>
     */
    protected $arguments = [
        '[directory:Publishers]' => "[Facultatif] Le répertoire à analyser dans chaque espace de noms. Par défaut\u{a0}: \"Publishers\".",
    ];

    /**
     * Affiche l'aide du script klinge cli lui-même.
     */
    public function execute(array $params)
    {
        $directory = array_shift($params) ?? 'Publishers';

        if ([] === $publishers = Publisher::discover($directory)) {
            $this->write(lang('Publisher.publishMissing', [$directory]));

            return;
        }

        foreach ($publishers as $publisher) {
            if ($publisher->publish()) {
                $this->ok(lang('Publisher.publishSuccess', [
                    get_class($publisher),
                    count($publisher->getPublished()),
                    $publisher->getDestination(),
                ]));
            } else {
                $this->fail(lang('Publisher.publishFailure', [
                    get_class($publisher),
                    $publisher->getDestination(),
                ]));

                foreach ($publisher->getErrors() as $file => $exception) {
                    $this->write($file);
                    $this->fail($exception->getMessage());
                    $this->newLine();
                }
            }
        }
    }
}
