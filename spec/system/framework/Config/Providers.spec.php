<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
use BlitzPHP\Contracts\Autoloader\LocatorInterface;
use BlitzPHP\Autoloader\Autoloader;
use BlitzPHP\Config\Providers;
use BlitzPHP\Spec\ReflectionHelper;

describe('Config / Providers', function (): void {
	it('Providers', function (): void {
		$definitions = Providers::definitions();

		$classes    = ReflectionHelper::getPrivateMethodInvoker(Providers::class, 'classes');
		$interfaces = ReflectionHelper::getPrivateMethodInvoker(Providers::class, 'interfaces');

		$classes    = array_keys($classes());
		$interfaces = array_keys($interfaces());

		expect($definitions)->toBeA('array');
		expect($interfaces)->toBeA('array');
		expect($classes)->toBeA('array');

		expect($definitions)->toContainKeys($classes + $interfaces);
		expect($interfaces)->toContain(LocatorInterface::class);
		expect($classes)->toContain(Autoloader::class);
	});
});
