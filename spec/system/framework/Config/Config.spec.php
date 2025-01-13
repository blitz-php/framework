<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Config\Config;
use BlitzPHP\Exceptions\ConfigException;
use BlitzPHP\Spec\ReflectionHelper;
use Nette\Schema\Schema;

use function Kahlan\expect;

describe('Config / Config', function (): void {
	beforeEach(function(): void {
		$this->config = service('config');
	});

    describe('Initialisation', function (): void {
        it('La config est toujours initialisee', function (): void {
            $initialized  = ReflectionHelper::getPrivateProperty(Config::class, 'initialized');
            $configurator = ReflectionHelper::getPrivateProperty($this->config, 'configurator');
            $finalConfig  = ReflectionHelper::getPrivateProperty($configurator, 'finalConfig');

            expect($initialized)->toBeTruthy();
            expect($finalConfig)->not->toBeNull();
        });

        it('La config charge bien les fichiers', function (): void {
			$loaded  = ReflectionHelper::getPrivateProperty(Config::class, 'loaded');

			expect($loaded)->toBeA('array');
			expect($loaded)->toContainKey('app');
			expect($loaded['app'])->toBe(config_path('app'));
        });

        it('La methode load charge belle et bien le fichier de config', function (): void {
			$loaded  = ReflectionHelper::getPrivateProperty(Config::class, 'loaded');

			// Soyons sur que seul les fichiers necessaires sont charges
			expect($loaded)->not->toContainKeys(['toolbar', 'publisher']);

			$this->config->load(['publisher']);
			$loaded  = ReflectionHelper::getPrivateProperty(Config::class, 'loaded');

			expect($loaded)->not->toContainKey('toolbar');
			expect($loaded)->toContainKey('publisher');

			expect($this->config->get('publisher.restrictions'))->toContainKeys([ROOTPATH, WEBROOT]);
		});
    });

    describe('Getters et setters', function (): void {
        it('has, exists, missing', function (): void {
			expect($this->config->has('appl'))->toBeFalsy();
			expect($this->config->has('app'))->toBeTruthy();

			expect($this->config->exists('app'))->toBeTruthy();
			expect($this->config->missing('app'))->toBeFalsy();
        });

        it('get', function (): void {
            expect($this->config->get('app.environment'))->toBe('testing');
            expect(fn() => $this->config->get('app.environement'))->toThrow(new ConfigException());
            expect($this->config->get('app.environement', 'default'))->toBe('default');
        });

        it('set', function (): void {
			$env = $this->config->get('app.environment');

			$this->config->set('app.environement', 'dev');
            expect($this->config->get('app.environement'))->toBe('dev');

			$this->config->set('app.environement', $env);
            expect($this->config->get('app.environement'))->toBe('testing');
        });

        it('set d\'une config abscente', function (): void {
			$this->config->set('appl.environement', 'dev');
			expect(fn() => $this->config->get('appl.environement'))->toThrow(new ConfigException());

			$this->config->ghost('appl');
			$this->config->set('appl.environement', 'dev');
            expect($this->config->get('appl.environement'))->toBe('dev');
        });
    });

	describe('Autres', function (): void {
		it('path', function (): void {
			expect(Config::path('app'))->toBe(config_path('app'));
			expect(Config::path('appl'))->toBeEmpty();
		});

		it('schema', function (): void {
			expect(Config::schema('app'))->toBeAnInstanceOf(Schema::class);
			expect(Config::schema('appl'))->toBeNull();
		});

		it('reset', function (): void {
			expect($this->config->get('app.environment'))->toBe('testing');
			expect($this->config->get('app.negotiate_locale'))->toBeTruthy();

			$this->config->set('app.environment', 'production');
			expect($this->config->get('app.environment'))->toBe('production');
			$this->config->set('app.negotiate_locale', false);
			expect($this->config->get('app.negotiate_locale'))->toBeFalsy();

			$this->config->reset('app');
			expect($this->config->get('app.environment'))->toBe('testing');
			expect($this->config->get('app.negotiate_locale'))->toBeTruthy();
		});

		it('reset multple', function (): void {
			expect($this->config->get('app.environment'))->toBe('testing');
			expect($this->config->get('publisher.restrictions'))->toContainKeys([ROOTPATH, WEBROOT]);
			expect($this->config->get('app.negotiate_locale'))->toBeTruthy();

			$this->config->set('app.environment', 'production');
			expect($this->config->get('app.environment'))->toBe('production');

			$this->config->set('publisher.restrictions', [WEBROOT => '*']);
			expect($this->config->get('publisher.restrictions'))->toBe([WEBROOT => '*']);

			$this->config->set('app.negotiate_locale', false);
			expect($this->config->get('app.negotiate_locale'))->toBeFalsy();

			$this->config->reset(['app.environment', 'publisher']);
			expect($this->config->get('app.environment'))->toBe('testing');
			expect($this->config->get('publisher.restrictions'))->toContainKeys([ROOTPATH, WEBROOT]);

			// On a pas reset negotiate_locale, donc on conserve la valeur modifiee
			expect($this->config->get('app.negotiate_locale'))->toBeFalsy();
		});
	});
});
