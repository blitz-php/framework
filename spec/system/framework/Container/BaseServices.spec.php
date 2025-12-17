<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Container\BaseServices;
use BlitzPHP\Contracts\Autoloader\LocatorInterface;
use BlitzPHP\Http\Request;
use BlitzPHP\Http\UrlGenerator;
use BlitzPHP\Spec\ReflectionHelper;
use DI\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;

use function Kahlan\expect;

describe('Container / BaseServices', function (): void {
    beforeAll(function () {
        $this->baseInstances = ReflectionHelper::getPrivateProperty(BaseServices::class, 'instances');
    });

    beforeEach(function () {
        // BaseServices::reset(true);
		BaseServices::resetSingle('test');
    });

    afterEach(function () {
		ReflectionHelper::setPrivateProperty(
			BaseServices::class,
			'instances',
			$this->baseInstances
		);
        // BaseServices::reset(true);
    });

    describe('Méthodes statiques de base', function () {
        it('autoloader retourne une instance', function () {
            $autoloader = BaseServices::autoloader(false);
            expect($autoloader)->toBeAnInstanceOf('BlitzPHP\Autoloader\Autoloader');
        });

        it('autoloader partagé retourne même instance', function () {
            $autoloader1 = BaseServices::autoloader(true);
            $autoloader2 = BaseServices::autoloader(true);
            expect($autoloader1)->toBe($autoloader2);
        });

        it('locator retourne une instance', function () {
            $locator = BaseServices::locator(false);
            expect($locator)->toBeAnInstanceOf(LocatorInterface::class);
        });

        it('locator partagé retourne même instance', function () {
            $locator1 = BaseServices::locator(true);
            $locator2 = BaseServices::locator(true);
            expect($locator1)->toBe($locator2);
        });

        xit('locator avec cache si configuré', function () {
			$initial = config()->get('optimize.locator_cache_enabled');
			BaseServices::resetSingle('locator');

            $locator = BaseServices::locator(true);
            expect($locator)->toBeAnInstanceOf('BlitzPHP\Autoloader\LocatorCached');

			config()->set('optimize.locator_cache_enabled', $initial);
			BaseServices::resetSingle('locator');
        });
    });

    describe('Gestion des instances', function () {
        it('get leve une exception si non trouvé', function () {
            expect(fn() => BaseServices::get('inexistant'))->toThrow(new DI\NotFoundException());
        });

        it('set définit une entrée', function () {
            $obj = new stdClass();
            $obj->name = 'test';
            BaseServices::set('test', $obj);
            expect(BaseServices::get('test'))->toBe($obj);
        });

        it('set échoue si déjà défini', function () {
            $obj1 = new stdClass();
            $obj2 = new stdClass();
            BaseServices::set('test', $obj1);
            expect(fn() => BaseServices::set('test', $obj2))
                ->toThrow(new InvalidArgumentException("L'entrée pour 'test' est déjà définie."));
        });

        it('override remplace une entrée existante', function () {
            $obj1 = new stdClass();
            $obj1->name = 'old';
            $obj2 = new stdClass();
            $obj2->name = 'new';

            BaseServices::set('test', $obj1);
            BaseServices::override('test', $obj2);

            expect(BaseServices::get('test'))->toBe($obj2);
            expect(BaseServices::get('test')->name)->toBe('new');
        });

        it('singleton crée et retourne une même instance', function () {
            $instance1 = BaseServices::singleton('test');
            $instance2 = BaseServices::singleton('test');
            expect($instance1)->toBe($instance2);
        });

        it('singleton avec arguments', function () {
            $instance = BaseServices::singleton('request');
            expect($instance)->toBeAnInstanceOf(Request::class);
        });

        it('factory crée nouvelle instance', function () {
            $generator1 = BaseServices::factory(UrlGenerator::class);
            $generator2 = BaseServices::factory(UrlGenerator::class);
            expect($generator1)->not->toBe($generator2);
            expect($generator1)->toBeAnInstanceOf(UrlGenerator::class);
            expect($generator2)->toBeAnInstanceOf(UrlGenerator::class);
        });
    });

    describe('Gestion des mocks et reset', function () {
        it('injectMock définit un mock', function () {
            $mock = new stdClass();
            $mock->id = 'mock';
            BaseServices::injectMock('test', $mock);
            expect(BaseServices::get('test'))->toBe($mock);
        });

        it('sharedInstance retourne mock si présent', function () {
            $mock = new stdClass();
            $mock->name = 'mock';
            BaseServices::injectMock('request', $mock);

            $result = BaseServices::sharedInstance('request');
            expect($result)->toBe($mock);

			BaseServices::resetSingle('request');
        });

        it('resetSingle réinitialise un service spécifique', function () {
            $obj1 = new stdClass();
            $obj2 = new stdClass();

            BaseServices::set('service1', $obj1);
            BaseServices::set('service2', $obj2);

            BaseServices::resetSingle('service1');

            expect(BaseServices::get('service2'))->toBe($obj2);
            expect(fn() => BaseServices::get('service1'))->toThrow(new NotFoundException());
        });
    });

    describe('Découverte des services', function () {
        it('serviceExists trouve un service existant', function () {
            $result = BaseServices::serviceExists('httpclient');
            expect($result)->toBe('BlitzPHP\HttpClient\Config\Services');
        });

        it('serviceExists retourne null pour service inexistant', function () {
            $result = BaseServices::serviceExists('inexistant');
            expect($result)->toBeNull();
        });

        it('__callStatic appelle méthode de service', function () {
            $request = BaseServices::request();
            expect($request)->toBeAnInstanceOf(Request::class);
        });

        it('__callStatic gère les factories', function () {
            $request = BaseServices::request(false);
            expect($request)->toBeAnInstanceOf(Request::class);
        });

        it('__callStatic gère les singletons via discoverServices', function () {
            $request = BaseServices::request(true);
            expect($request)->toBeAnInstanceOf(Request::class);
        });
    });

    describe('Alias et noms de service', function () {
        it('serviceName normalise via alias', function () {
            $method = new ReflectionMethod(BaseServices::class, 'serviceName');
            $method->setAccessible(true);

            expect($method->invoke(null, 'locator'))->toBe('locator');
            expect($method->invoke(null, LocatorInterface::class))->toBe('locator');
            expect($method->invoke(null, 'request'))->toBe('request');
            expect($method->invoke(null, ServerRequestInterface::class))->toBe('request');
        });

        it('serviceName retourne nom original si pas d\'alias', function () {
            $method = new ReflectionMethod(BaseServices::class, 'serviceName');
            $method->setAccessible(true);

            expect($method->invoke(null, 'custom'))->toBe('custom');
        });
    });

    describe('cacheServices', function () {
        it('cacheServices découvre et cache les services', function () {
			$cacheService = ReflectionHelper::getPrivateMethodInvoker(BaseServices::class,'cacheServices');
            ReflectionHelper::setPrivateProperty(BaseServices::class, 'discovered', false);

			$cacheService();

			expect(ReflectionHelper::getPrivateProperty(BaseServices::class, 'discovered'))->toBeTruthy();
        });

        it('cacheServices ne fait rien si déjà découvert', function () {
           $cacheService = ReflectionHelper::getPrivateMethodInvoker(BaseServices::class,'cacheServices');
            ReflectionHelper::setPrivateProperty(BaseServices::class, 'discovered', true);

			$cacheService();

			expect(ReflectionHelper::getPrivateProperty(BaseServices::class, 'discovered'))->toBeTruthy();
        });
    });
});
