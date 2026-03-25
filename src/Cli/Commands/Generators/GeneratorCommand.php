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
}
