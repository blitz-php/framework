<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Console;

use Ahc\Cli\IO\Interactor;
use Dimtrovich\Console\Console as BaseConsole;
use Dimtrovich\Console\Overrides\Command as BaseCommand;

/**
 * Classe de base utilisée pour créer des commandes pour la console
 *
 * @method Interactor io()
 * @method array      required()
 */
abstract class Command extends \Dimtrovich\Console\Command
{
    /**
     * Le nom du service de la commande
     */
    protected string $service = '';

    /**
     * Liste des packets requis pour le fonctionnement d'une commande
     * Par exemple, toutes le commande du groupe Database ont besoin de blitz/database
     *
     * @example
     * `[
     *      'vendor/package', 'vendor/package:version'
     * ]`
     */
    protected array $required = [];

    /**
     * Defini si on doit supprimer les information du header (nom/version du framework) ou pas
     */
    protected bool $suppress = false;

	/**
     * {@inheritDoc}
     */
    public function initialize(BaseConsole $app): BaseCommand
    {
		if (! is_cli()) {
			$input  = config('klinge.interactor.input', FRAMEWORK_TEMP_PATH . 'klinge' . DS . 'input');
			$output = config('klinge.interactor.output', FRAMEWORK_TEMP_PATH . 'klinge' . DS . 'output');

			foreach ([$input, $output] as $file) {
				if (! is_file($file)) {
					if (! is_dir($dir = dirname($file))) {
						mkdir($dir, 0777, true);
					}
					file_put_contents($file, '');
				}
			}

			$app = $app->io(new Interactor($input, $output));
		}

		return parent::initialize($app);
    }
}
