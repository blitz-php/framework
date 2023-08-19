<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Event;

use BlitzPHP\Autoloader\Locator;
use BlitzPHP\Container\Services;
use BlitzPHP\Contracts\Event\EventListenerInterface;
use BlitzPHP\Contracts\Event\EventManagerInterface;

/**
 * Decouvre et inclus tous les ecouteurs d'evenement
 */
class EventDiscover
{
	protected Locator $locator;

	public function __construct(protected EventManagerInterface $event)
	{
		$this->locator = Services::locator();
	}

	public function discove()
	{
		$files = $this->locator->listFiles('Events/');

		foreach ($files as $file) {
            $className = $this->locator->getClassname($file);

            if ($className === '' || ! class_exists($className) || ! is_a($className, EventListenerInterface::class, true)) {
                continue;
            }

			Services::factory($className)->listen($this->event);
        }
	}
}