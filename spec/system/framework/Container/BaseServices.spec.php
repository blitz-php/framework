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
            expect(BaseServices::serviceName('locator'))->toBe('locator');
            expect(BaseServices::serviceName(LocatorInterface::class))->toBe('locator');
            expect(BaseServices::serviceName('request'))->toBe('request');
            expect(BaseServices::serviceName(ServerRequestInterface::class))->toBe('request');
        });

        it('serviceName retourne nom original si pas d\'alias', function () {
            expect(BaseServices::serviceName('custom'))->toBe('custom');
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

	describe('Méthode resolveServiceAliases', function () {
		it('resolveServiceAliases pour nom canonique retourne tous les aliases', function () {
			$aliases = BaseServices::resolveServiceAliases('locator');

			expect($aliases)->toBeAn('array');
			expect($aliases)->toHaveLength(3); // locator + Locator::class + LocatorInterface::class
			expect($aliases)->toContain('locator');
			expect($aliases)->toContain('BlitzPHP\Autoloader\Locator');
			expect($aliases)->toContain('BlitzPHP\Contracts\Autoloader\LocatorInterface');
		});

		it('resolveServiceAliases pour alias FQCN retourne tous les aliases', function () {
			$aliases = BaseServices::resolveServiceAliases('BlitzPHP\Contracts\Autoloader\LocatorInterface');

			expect($aliases)->toBeAn('array');
			expect($aliases)->toHaveLength(3);
			expect($aliases)->toContain('locator'); // Le nom canonique
			expect($aliases)->toContain('BlitzPHP\Autoloader\Locator');
			expect($aliases)->toContain('BlitzPHP\Contracts\Autoloader\LocatorInterface');
		});

		it('resolveServiceAliases pour service sans alias retourne uniquement le nom', function () {
			$aliases = BaseServices::resolveServiceAliases('service_sans_alias');

			expect($aliases)->toBeAn('array');
			expect($aliases)->toHaveLength(1);
			expect($aliases)->toContain('service_sans_alias');
		});

		it('resolveServiceAliases pour request retourne tous les aliases', function () {
			$aliases = BaseServices::resolveServiceAliases('request');

			expect($aliases)->toBeAn('array');
			expect($aliases)->toHaveLength(4); // request + 3 aliases
			expect($aliases)->toContain('request');
			expect($aliases)->toContain('BlitzPHP\Http\Request');
			expect($aliases)->toContain('BlitzPHP\Http\ServerRequest');
			expect($aliases)->toContain('Psr\Http\Message\ServerRequestInterface');
		});

		it('resolveServiceAliases élimine les doublons', function () {
			// Tester avec le nom canonique et vérifier qu'il n'y a pas de doublons
			$aliases = BaseServices::resolveServiceAliases('locator');

			$uniqueAliases = array_unique($aliases);
			expect($aliases)->toHaveLength(count($uniqueAliases));
		});

		it('resolveServiceAliases conserve l\'ordre original', function () {
			$aliases = BaseServices::resolveServiceAliases('locator');

			// Le premier élément doit être le nom passé
			expect($aliases[0])->toBe('locator');

			// Les suivants doivent être les aliases
			expect($aliases)->toContain('BlitzPHP\Autoloader\Locator');
			expect($aliases)->toContain('BlitzPHP\Contracts\Autoloader\LocatorInterface');
		});

		it('resolveServiceAliases pour alias intermédiaire retourne tous les aliases', function () {
			// Utilise un alias qui n'est pas le canonique
			$aliases = BaseServices::resolveServiceAliases('BlitzPHP\Http\ServerRequest');

			expect($aliases)->toBeAn('array');
			expect($aliases)->toHaveLength(4);
			expect($aliases)->toContain('request'); // Le nom canonique
			expect($aliases)->toContain('BlitzPHP\Http\Request');
			expect($aliases)->toContain('BlitzPHP\Http\ServerRequest');
			expect($aliases)->toContain('Psr\Http\Message\ServerRequestInterface');
		});

		it('resolveServiceAliases pour différents services', function () {
			// Test router
			$routerAliases = BaseServices::resolveServiceAliases('router');
			expect($routerAliases)->toContain('router');
			expect($routerAliases)->toContain('BlitzPHP\Router\Router');
			expect($routerAliases)->toContain('BlitzPHP\Contracts\Router\RouterInterface');

			// Test response
			$responseAliases = BaseServices::resolveServiceAliases('response');
			expect($responseAliases)->toContain('response');
			expect($responseAliases)->toContain('BlitzPHP\Http\Response');
			expect($responseAliases)->toContain('Psr\Http\Message\ResponseInterface');

			// Test routes
			$routesAliases = BaseServices::resolveServiceAliases('routes');
			expect($routesAliases)->toContain('routes');
			expect($routesAliases)->toContain('BlitzPHP\Router\RouteCollection');
			expect($routesAliases)->toContain('BlitzPHP\Contracts\Router\RouteCollectionInterface');
		});

		it('resolveServiceAliases avec cache intégration', function () {
			// Test l'intégration avec le cache de serviceName
			// Premier appel
			$aliases1 =  BaseServices::resolveServiceAliases('locator');

			// Deuxième appel (devrait utiliser le cache de serviceName)
			$aliases2 =  BaseServices::resolveServiceAliases('locator');

			expect($aliases1)->toBe($aliases2);
			expect($aliases1)->toHaveLength(3);
		});

		it('resolveServiceAliases insensible à la casse pour noms canoniques', function () {
			// serviceName() convertit en lowercase, donc "LOCATOR" devrait devenir "locator"
			$aliases =  BaseServices::resolveServiceAliases('LOCATOR');

			expect($aliases)->toContain('locator');
			expect($aliases)->toContain('BlitzPHP\Autoloader\Locator');
			expect($aliases)->toContain('BlitzPHP\Contracts\Autoloader\LocatorInterface');
		});
	});
});
