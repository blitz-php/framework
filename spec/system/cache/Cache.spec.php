<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Cache\Cache;
use BlitzPHP\Cache\Handlers\Dummy;
use BlitzPHP\Cache\InvalidArgumentException;
use BlitzPHP\Spec\ReflectionHelper;

describe('Cache / Cache Factory', function (): void {

	describe('Erreur de gestionnaire de cache', function (): void {
		it('Renvoie Dummy lorsque le cache est désactivé', function (): void {
			$cache = new Cache();
			Cache::disable();

			$factory = ReflectionHelper::getPrivateMethodInvoker($cache, 'factory');

			expect(call_user_func($factory))->toBeAnInstanceOf(Dummy::class);

			Cache::enable();
		});

		it('Leve une exception lorsque les gestionnaires valides ne sont pas definis', function (): void {
			$cache = new Cache();

			expect(ReflectionHelper::getPrivateProperty($cache, 'config'))->toBe([]);

			$factory = ReflectionHelper::getPrivateMethodInvoker($cache, 'factory');

			expect(fn() => call_user_func($factory))->toThrow(new InvalidArgumentException());
		});

		it('Leve une exception lorsque le gestionnaire principal n\'est pas defini ou ne fait pas partir des gestionnaires valides', function (): void {
			$config                  = [];
			$config['valid_handlers'] = config('cache.valid_handlers');
			$cache                   = new Cache($config);

			$factory = ReflectionHelper::getPrivateMethodInvoker($cache, 'factory');
			expect(fn() => call_user_func($factory))->toThrow(new InvalidArgumentException());


			$config['handler'] = 'fake_handler';
			$cache             = new Cache($config);

			$factory = ReflectionHelper::getPrivateMethodInvoker($cache, 'factory');
			expect(fn() => call_user_func($factory))->toThrow(new InvalidArgumentException());
		});

		it('Utilise Dummy si le gestionnaire principal fait partir des gestionnaires valides mais n\'herite pas de BaseHandler', function (): void {
			$config                   = [];
			$config['valid_handlers'] = ['fake_handler' => stdClass::class];
			$config['handler']        = 'fake_handler';
			$cache                    = new Cache($config);

			$factory = ReflectionHelper::getPrivateMethodInvoker($cache, 'factory');
			expect(call_user_func($factory))->toBeAnInstanceOf(Dummy::class);
		});
	});
});
