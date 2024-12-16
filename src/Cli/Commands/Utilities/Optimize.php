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

use BlitzPHP\Autoloader\Locator;
use BlitzPHP\Autoloader\LocatorCached;
use BlitzPHP\Cache\Handlers\FileVarExportHandler;
use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Publisher\Publisher;
use RuntimeException;

/**
 * Optimisation pour la production.
 */
final class Optimize extends Command
{
    /**
     * @var string Groupe
     */
    protected $group = 'BlitzPHP';

    /**
     * @var string Nom
     */
    protected $name = 'optimize';

    /**
     * @var string Description
     */
    protected $description = 'Optimise l\'application pour la production.';

    protected $service = 'Service de configuration';

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        try {
            $this->enableCaching();
            $this->clearCache();
            $this->removeDevPackages();
        } catch (RuntimeException) {
            $this->fail('La commande "klinge optimize" a échouée.')->eol();

            return EXIT_ERROR;
        }

        return EXIT_SUCCESS;
    }

	private function clearCache(): void
    {
		$locator = new LocatorCached(new Locator(service('autoloader')), new FileVarExportHandler());

        $locator->deleteCache();

		$this->ok('Suppression de FileLocatorCache.')->eol();

        $this->removeFile(FRAMEWORK_STORAGE_PATH . 'cache/FactoriesCache_config');
    }

    private function removeFile(string $cache): void
    {
        if (is_file($cache)) {
            $result = unlink($cache);

            if ($result) {
                $this->ok('"' . clean_path($cache) . '" supprimé.')->eol();

                return;
            }

            $this->fail('Erreur lors de la suppression du fichier: ' . clean_path($cache));

            throw new RuntimeException(__METHOD__);
        }
    }

    private function enableCaching(): void
    {
        $publisher = new Publisher(APP_PATH, APP_PATH);

        $config = APP_PATH . 'Config/optimize.php';

        $result = $publisher->replace(
            $config,
            [
                "'config_cache_enabled' => false,"  => "'config_cache_enabled' => true,",
                "'locator_cache_enabled' => false," => "'locator_cache_enabled' => true,",
            ]
        );

        if ($result) {
            $this->ok(
                'Les options Config Caching et FileLocator Caching sont activées dans "app/Config/optimize.php".',
            )->eol();

            return;
        }

        $this->fail('Erreur dans la mise à jour du fichier: ' . clean_path($config))->eol();

        throw new RuntimeException(__METHOD__);
    }

    private function removeDevPackages(): void
    {
        if (! defined('VENDOR_PATH')) {
            return;
        }

        chdir(ROOTPATH);
        passthru('composer install --no-dev', $status);

        if ($status === 0) {
            $this->ok('Suppression des paquets de développement Composer.')->eol();

            return;
        }

        $this->fail('Erreur lors de la suppression des paquets de développement Composer.')->eol();

        throw new RuntimeException(__METHOD__);
    }
}
