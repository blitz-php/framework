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

use BlitzPHP\Contracts\Autoloader\LocatorInterface;
use BlitzPHP\Contracts\Event\EventListenerInterface;
use BlitzPHP\Contracts\Event\EventManagerInterface;

/**
 * Decouvre et inclus tous les ecouteurs d'evenement
 */
class EventDiscover
{
    protected LocatorInterface $locator;

    public function __construct(protected EventManagerInterface $manager)
    {
        $this->locator = service('locator');
    }

    public function discove()
    {
        $files = array_merge(
            $this->locator->listFiles('Events/'), // @deprecated just use for compatibility
            $this->locator->listFiles('Listeners/')
        );

        foreach ($files as $file) {
            $className = $this->locator->getClassname($file);

            if ($className === '' || ! class_exists($className) || ! is_a($className, EventListenerInterface::class, true)) {
                continue;
            }

            service('factory', $className)->listen($this->manager);
        }
    }
}
