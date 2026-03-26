<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Generators;

use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Cli\Traits\GeneratorTrait;

abstract class GeneratorCommand extends Command
{
	use GeneratorTrait;

	/**
	 * {@inheritDoc}
	 */
	protected string $group   = 'Générateurs';

	/**
	 * {@inheritDoc}
	 */
	protected string $service = 'Service de génération de code';

	/**
     * {@inheritDoc}
     */
    public function handle()
    {
        $this->process($this->parameters());
    }

	/**
	 * Destinné à être surchagée si besoin
	 */
	protected function process(array $parameters)
	{
		$this->generateClass($parameters);

		return EXIT_SUCCESS;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function displayFileCreated(string $file)
	{
		$this->badge()->info(sprintf('%s [%s] %s',
			$this->component,
			$this->color->ok($file),
			'Créé avec succès',
		));
		$this->eol();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function displayFileExists(string $file)
	{
		$this->badge()->error(sprintf('%s [%s]. %s',
			$this->component,
			$file,
			'Fichier déjà existant',
		));
		$this->eol();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function displayFileError(string $file)
	{
		$this->badge()->error(sprintf('%s [%s]. %s',
			$this->component,
			$file,
			'Erreur lors de la création du fichier',
		));
		$this->eol();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function displayFileOverwrited(string $file)
	{
		$this->badge()->warning(sprintf('%s [%s]. %s',
			$this->component,
			$file,
			'Fichier écrasé',
		));
		$this->eol();
	}
}
