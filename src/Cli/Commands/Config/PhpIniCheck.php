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
use BlitzPHP\Security\CheckPhpIni;

/**
 * Check php.ini values.
 */
final class PhpIniCheck extends Command
{
    protected string $group = 'BlitzPHP';

    protected string $name = 'phpini:check';

    protected string $description = 'Vérifiez les valeurs de votre php.ini dans l\'environnement de production.';

    protected string $service   = 'Service de configuration';

    protected array $arguments = [
        'opcache' => ['Vérifier les valeurs détaillées de l\'opcache dans l\'environnement de production.'],
    ];

    /**
     * {@inheritDoc}
     */
    public function handle()
    {
        $opcache = $this->argument('opcache', $this->parameter(0));

        if (! empty($opcache) && ! in_array($opcache, array_keys($this->arguments), true)) {
            $this->fail('Vous devez indiquer un argument correct.')->eol();
            $this->fail('  Example: phpini:check opcache')->eol();
            $this->fail('Arguments:')->eol();

            $length = max(array_map(strlen(...), array_keys($this->arguments)));

            foreach ($this->arguments as $argument => [$description]) {
                $this->write($this->color->ok($this->pad($argument, $length, 2, 2)) . $description)->eol();
            }

            return EXIT_ERROR;
        }

        /** @var array $data */
        $data = CheckPhpIni::run(argument: $opcache);

        $this->table($data);

        return EXIT_SUCCESS;
    }
}
