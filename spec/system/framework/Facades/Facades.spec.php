<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Cache\Cache as CacheCache;
use BlitzPHP\Config\Config as ConfigConfig;
use BlitzPHP\Container\Container as ContainerContainer;
use BlitzPHP\Debug\Logger;
use BlitzPHP\Facades\Cache;
use BlitzPHP\Facades\Config;
use BlitzPHP\Facades\Container;
use BlitzPHP\Facades\Facade;
use BlitzPHP\Facades\Fs;
use BlitzPHP\Facades\Log;
use BlitzPHP\Facades\Route;
use BlitzPHP\Facades\Storage;
use BlitzPHP\Facades\View;
use BlitzPHP\Filesystem\Filesystem;
use BlitzPHP\Filesystem\FilesystemManager;
use BlitzPHP\Router\RouteBuilder;
use BlitzPHP\Spec\ReflectionHelper;
use BlitzPHP\View\View as ViewView;
use DI\NotFoundException;

describe('Facades', function () {
	describe('Facade', function () {
		it('Accessor retourne un objet', function () {
			$class = new class() extends Facade {
				protected static function accessor(): object
				{
					return new stdClass();
				}
			};

			expect(ReflectionHelper::getPrivateMethodInvoker($class, 'accessor')())->toBeAnInstanceOf(stdClass::class);
		});

		it('Accessor retourne un string', function () {
			$class = new class() extends Facade {
				protected static function accessor(): string
				{
					return 'fs';
				}
			};

			expect(ReflectionHelper::getPrivateMethodInvoker($class, 'accessor')())->toBe('fs');
		});

		it('__call et __callStatic fonctionnent', function () {
			$class = new class() extends Facade {
				protected static function accessor(): string
				{
					return 'fs';
				}
			};

			expect(ReflectionHelper::getPrivateMethodInvoker($class, 'accessor')())->toBe('fs');
			expect($class->exists(__FILE__))->toBeTruthy();
			expect($class::exists(__FILE__))->toBeTruthy();
		});

		it('__callStatic genere une erreur si accessor renvoie une chaine qui ne peut pas etre resourdre par le fournisseur de service', function () {
			$class = new class() extends Facade {
				protected static function accessor(): string
				{
					return 'fss';
				}
			};

			expect(ReflectionHelper::getPrivateMethodInvoker($class, 'accessor')())->toBe('fss');
			expect(fn() => $class::exists(__FILE__))->toThrow(new NotFoundException("No entry or class found for 'fss'"));
		});

		it('__callStatic genere une erreur si accessor renvoie une chaine qui peut etre resourdre par le fournisseur de service mais n\'est pas un objet', function () {
			Container::set('fss', __FILE__);
			$class = new class() extends Facade {
				protected static function accessor(): string
				{
					return 'fss';
				}
			};

			expect(ReflectionHelper::getPrivateMethodInvoker($class, 'accessor')())->toBe('fss');
			expect(fn() => $class::test())->toThrow(new InvalidArgumentException());
		});

		it('__callStatic fonctionne normalement si accessor renvoie une chaine qui peut etre resourdre par le fournisseur de service', function () {
			Container::set('fss', new class() {
				public function test() {
					return true;
				}
			});
			$class = new class() extends Facade {
				protected static function accessor(): string
				{
					return 'fss';
				}
			};

			expect(ReflectionHelper::getPrivateMethodInvoker($class, 'accessor')())->toBe('fss');
			expect($class::test())->toBeTruthy();
			expect(fn() => $class::test())->not->toThrow(new InvalidArgumentException());
		});

		it('__callStatic genere une erreur normale si la methode n\'existe pas ou qu\'il y\'a une incompatibilite de parametre', function () {
			Container::set('fss', new class() {
				public function test() {
					return true;
				}
				public function hello(string $name) {
					return 'Hello ' . $name;
				}
			});
			$class = new class() extends Facade {
				protected static function accessor(): string
				{
					return 'fss';
				}
			};

			expect(ReflectionHelper::getPrivateMethodInvoker($class, 'accessor')())->toBe('fss');
			expect($class::test())->toBeTruthy();
			expect(fn() => $class::test())->not->toThrow(new InvalidArgumentException());
			expect(fn() => $class::testons())->toThrow(new Error('Call to undefined method class@anonymous::testons()'));
			expect(fn() => $class::hello())->toThrow(new ArgumentCountError());
			expect($class->hello('BlitzPHP'))->toBe('Hello BlitzPHP');
		});
	});

    describe('Cache', function () {
        it('Cache', function () {
            $accessor = ReflectionHelper::getPrivateMethodInvoker(Cache::class, 'accessor');

            expect($accessor())->toBeAnInstanceOf(CacheCache::class);
        });

        it('Execution d\'une methode', function () {
			expect(Cache::set('foo', 'bar'))->toBeTruthy();
			expect(Cache::get('foo'))->toBe('bar');
			expect(Cache::delete('foo'))->toBeTruthy();
			expect(Cache::has('foo'))->toBeFalsy();
        });
    });

	describe('Config', function () {
        it('Container', function () {
            $accessor = ReflectionHelper::getPrivateMethodInvoker(Config::class, 'accessor');

            expect($accessor())->toBeAnInstanceOf(ConfigConfig::class);
        });

        it('Execution d\'une methode', function () {
			$lang = config('app.language');

			expect(Config::has('app.language'))->toBeTruthy();
			expect(Config::get('app.language'))->toBe($lang);

			Config::set('app.language', 'jp');
			expect(Config::get('app.language'))->toBe('jp');

			Config::reset();
			expect(Config::get('app.language'))->toBe($lang);
		});
    });

	describe('Container', function () {
        it('Container', function () {
            $accessor = ReflectionHelper::getPrivateMethodInvoker(Container::class, 'accessor');

            expect($accessor())->toBeAnInstanceOf(ContainerContainer::class);
        });

        it('Execution d\'une methode', function () {
            expect(Container::has(ContainerContainer::class))->toBeTruthy();
        });
    });

    describe('Fs', function () {
        it('FS', function () {
            $accessor = ReflectionHelper::getPrivateMethodInvoker(Fs::class, 'accessor');

            expect($accessor())->toBeAnInstanceOf(Filesystem::class);
        });

        it('Execution d\'une methode', function () {
            expect(Fs::exists(__FILE__))->toBeTruthy();
        });
    });

    describe('Log', function () {
        it('Log', function () {
            $accessor = ReflectionHelper::getPrivateMethodInvoker(Log::class, 'accessor');

            expect($accessor())->toBeAnInstanceOf(Logger::class);
        });

        it('Execution d\'une methode', function () {
			skipIf(true); // skip execution because we don't have permission on github and scrutinizer
			Log::debug('test file ' . __FILE__);

			/** @var \Symfony\Component\Finder\SplFileInfo $file */
			$file = last(Fs::files(storage_path('logs')));
			expect($file->getContents())->toMatch(fn($actual) => str_contains($actual, 'test file ' . __FILE__));
        });
    });

    describe('Route', function () {
        it('Route', function () {
            $accessor = ReflectionHelper::getPrivateMethodInvoker(Route::class, 'accessor');

            expect($accessor())->toBeAnInstanceOf(RouteBuilder::class);
        });

        it('Execution d\'une methode', function () {
            $routeBuilder = Route::setDefaultController('TestController');

            expect(ReflectionHelper::getPrivateProperty($routeBuilder, 'collection')->getDefaultController())
                ->toBe('TestController');
        });
    });

    describe('Storage', function () {
        it('Storage', function () {
            $accessor = ReflectionHelper::getPrivateMethodInvoker(Storage::class, 'accessor');

            expect($accessor())->toBeAnInstanceOf(FilesystemManager::class);
        });

        it('Execution d\'une methode', function () {
            expect(Storage::exists(__FILE__))->toBeFalsy();
        });
    });

    describe('View', function () {
        it('View', function () {
            $accessor = ReflectionHelper::getPrivateMethodInvoker(View::class, 'accessor');

            expect($accessor())->toBeAnInstanceOf(ViewView::class);
        });

        it('Execution d\'une methode', function () {
            expect(View::exists(__FILE__))->toBeFalsy();
            expect(View::exists('simple'))->toBeTruthy();
        });
    });
});
