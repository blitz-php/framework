<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
use BlitzPHP\Autoloader\Autoloader;
use BlitzPHP\Autoloader\LocatorCached;
use BlitzPHP\HttpClient\Config\Services;
use BlitzPHP\Autoloader\Locator;
use BlitzPHP\Http\ServerRequest;
use BlitzPHP\Router\Router;
use BlitzPHP\Contracts\Router\RouterInterface;
use BlitzPHP\Http\Response;
use Psr\Http\Message\ResponseInterface;
use BlitzPHP\Router\RouteCollection;
use BlitzPHP\Contracts\Router\RouteCollectionInterface;
use BlitzPHP\Container\BaseServices;
use BlitzPHP\Contracts\Autoloader\LocatorInterface;
use BlitzPHP\Http\Request;
use BlitzPHP\Http\UrlGenerator;
use BlitzPHP\Spec\ReflectionHelper;
use DI\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;

use function Kahlan\expect;

describe('Container / BaseServices', function (): void {
    beforeAll(function (): void {
        $this->baseInstances = ReflectionHelper::getPrivateProperty(BaseServices::class, 'instances');
    });

    beforeEach(function (): void {
        // BaseServices::reset(true);
		BaseServices::resetSingle('test');
    });

    afterEach(function (): void {
		ReflectionHelper::setPrivateProperty(
			BaseServices::class,
			'instances',
			$this->baseInstances
		);
        // BaseServices::reset(true);
    });

    describe('Méthodes statiques de base', function (): void {
        it('autoloader retourne une instance', function (): void {
            $autoloader = BaseServices::autoloader(false);
            expect($autoloader)->toBeAnInstanceOf(Autoloader::class);
        });

        it('autoloader partagé retourne même instance', function (): void {
            $autoloader1 = BaseServices::autoloader(true);
            $autoloader2 = BaseServices::autoloader(true);
            expect($autoloader1)->toBe($autoloader2);
        });

        it('locator retourne une instance', function (): void {
            $locator = BaseServices::locator(false);
            expect($locator)->toBeAnInstanceOf(LocatorInterface::class);
        });

        it('locator partagé retourne même instance', function (): void {
            $locator1 = BaseServices::locator(true);
            $locator2 = BaseServices::locator(true);
            expect($locator1)->toBe($locator2);
        });

        xit('locator avec cache si configuré', function (): void {
			$initial = config()->get('optimize.locator_cache_enabled');
			BaseServices::resetSingle('locator');

            $locator = BaseServices::locator(true);
            expect($locator)->toBeAnInstanceOf(LocatorCached::class);

			config()->set('optimize.locator_cache_enabled', $initial);
			BaseServices::resetSingle('locator');
        });
    });

    describe('Gestion des instances', function (): void {
        it('get leve une exception si non trouvé', function (): void {
            expect(fn(): ?object => BaseServices::get('inexistant'))->toThrow(new NotFoundException());
        });

        it('set définit une entrée', function (): void {
            $obj = new stdClass();
            $obj->name = 'test';
            BaseServices::set('test', $obj);
            expect(BaseServices::get('test'))->toBe($obj);
        });

        it('set échoue si déjà défini', function (): void {
            $obj1 = new stdClass();
            $obj2 = new stdClass();
            BaseServices::set('test', $obj1);
            expect(fn() => BaseServices::set('test', $obj2))
                ->toThrow(new InvalidArgumentException("L'entrée pour 'test' est déjà définie."));
        });

        it('override remplace une entrée existante', function (): void {
            $obj1 = new stdClass();
            $obj1->name = 'old';
            $obj2 = new stdClass();
            $obj2->name = 'new';

            BaseServices::set('test', $obj1);
            BaseServices::override('test', $obj2);

            expect(BaseServices::get('test'))->toBe($obj2);
            expect(BaseServices::get('test')->name)->toBe('new');
        });

        it('singleton crée et retourne une même instance', function (): void {
            $instance1 = BaseServices::singleton('test');
            $instance2 = BaseServices::singleton('test');
            expect($instance1)->toBe($instance2);
        });

        it('singleton avec arguments', function (): void {
            $instance = BaseServices::singleton('request');
            expect($instance)->toBeAnInstanceOf(Request::class);
        });

        it('factory crée nouvelle instance', function (): void {
            $generator1 = BaseServices::factory(UrlGenerator::class);
            $generator2 = BaseServices::factory(UrlGenerator::class);
            expect($generator1)->not->toBe($generator2);
            expect($generator1)->toBeAnInstanceOf(UrlGenerator::class);
            expect($generator2)->toBeAnInstanceOf(UrlGenerator::class);
        });
    });

    describe('Gestion des mocks et reset', function (): void {
        it('injectMock définit un mock', function (): void {
            $mock = new stdClass();
            $mock->id = 'mock';
            BaseServices::injectMock('test', $mock);
            expect(BaseServices::get('test'))->toBe($mock);
        });

        it('sharedInstance retourne mock si présent', function (): void {
            $mock = new stdClass();
            $mock->name = 'mock';
            BaseServices::injectMock('request', $mock);

            $result = BaseServices::sharedInstance('request');
            expect($result)->toBe($mock);

			BaseServices::resetSingle('request');
        });

        it('resetSingle réinitialise un service spécifique', function (): void {
            $obj1 = new stdClass();
            $obj2 = new stdClass();

            BaseServices::set('service1', $obj1);
            BaseServices::set('service2', $obj2);

            BaseServices::resetSingle('service1');

            expect(BaseServices::get('service2'))->toBe($obj2);
            expect(fn(): ?object => BaseServices::get('service1'))->toThrow(new NotFoundException());
        });
    });

    describe('Découverte des services', function (): void {
        it('serviceExists trouve un service existant', function (): void {
            $result = BaseServices::serviceExists('httpclient');
            expect($result)->toBe(Services::class);
        });

        it('serviceExists retourne null pour service inexistant', function (): void {
            $result = BaseServices::serviceExists('inexistant');
            expect($result)->toBeNull();
        });

        it('__callStatic appelle méthode de service', function (): void {
            $request = BaseServices::request();
            expect($request)->toBeAnInstanceOf(Request::class);
        });

        it('__callStatic gère les factories', function (): void {
            $request = BaseServices::request(false);
            expect($request)->toBeAnInstanceOf(Request::class);
        });

        it('__callStatic gère les singletons via discoverServices', function (): void {
            $request = BaseServices::request(true);
            expect($request)->toBeAnInstanceOf(Request::class);
        });
    });

    describe('Alias et noms de service', function (): void {
        it('serviceName normalise via alias', function (): void {
            expect(BaseServices::serviceName('locator'))->toBe('locator');
            expect(BaseServices::serviceName(LocatorInterface::class))->toBe('locator');
            expect(BaseServices::serviceName('request'))->toBe('request');
            expect(BaseServices::serviceName(ServerRequestInterface::class))->toBe('request');
        });

        it('serviceName retourne nom original si pas d\'alias', function (): void {
            expect(BaseServices::serviceName('custom'))->toBe('custom');
        });
    });

    describe('cacheServices', function (): void {
        it('cacheServices découvre et cache les services', function (): void {
			$cacheService = ReflectionHelper::getPrivateMethodInvoker(BaseServices::class,'cacheServices');
            ReflectionHelper::setPrivateProperty(BaseServices::class, 'discovered', false);

			$cacheService();

			expect(ReflectionHelper::getPrivateProperty(BaseServices::class, 'discovered'))->toBeTruthy();
        });

        it('cacheServices ne fait rien si déjà découvert', function (): void {
           $cacheService = ReflectionHelper::getPrivateMethodInvoker(BaseServices::class,'cacheServices');
            ReflectionHelper::setPrivateProperty(BaseServices::class, 'discovered', true);

			$cacheService();

			expect(ReflectionHelper::getPrivateProperty(BaseServices::class, 'discovered'))->toBeTruthy();
        });
    });

	describe('Méthode resolveServiceAliases', function (): void {
		it('resolveServiceAliases pour nom canonique retourne tous les aliases', function (): void {
			$aliases = BaseServices::resolveServiceAliases('locator');

			expect($aliases)->toBeAn('array');
			expect($aliases)->toHaveLength(3); // locator + Locator::class + LocatorInterface::class
			expect($aliases)->toContain('locator');
			expect($aliases)->toContain(Locator::class);
			expect($aliases)->toContain(LocatorInterface::class);
		});

		it('resolveServiceAliases pour alias FQCN retourne tous les aliases', function (): void {
			$aliases = BaseServices::resolveServiceAliases(LocatorInterface::class);

			expect($aliases)->toBeAn('array');
			expect($aliases)->toHaveLength(3);
			expect($aliases)->toContain('locator'); // Le nom canonique
			expect($aliases)->toContain(Locator::class);
			expect($aliases)->toContain(LocatorInterface::class);
		});

		it('resolveServiceAliases pour service sans alias retourne uniquement le nom', function (): void {
			$aliases = BaseServices::resolveServiceAliases('service_sans_alias');

			expect($aliases)->toBeAn('array');
			expect($aliases)->toHaveLength(1);
			expect($aliases)->toContain('service_sans_alias');
		});

		it('resolveServiceAliases pour request retourne tous les aliases', function (): void {
			$aliases = BaseServices::resolveServiceAliases('request');

			expect($aliases)->toBeAn('array');
			expect($aliases)->toHaveLength(4); // request + 3 aliases
			expect($aliases)->toContain('request');
			expect($aliases)->toContain(Request::class);
			expect($aliases)->toContain(ServerRequest::class);
			expect($aliases)->toContain(ServerRequestInterface::class);
		});

		it('resolveServiceAliases élimine les doublons', function (): void {
			// Tester avec le nom canonique et vérifier qu'il n'y a pas de doublons
			$aliases = BaseServices::resolveServiceAliases('locator');

			$uniqueAliases = array_unique($aliases);
			expect($aliases)->toHaveLength(count($uniqueAliases));
		});

		it('resolveServiceAliases conserve l\'ordre original', function (): void {
			$aliases = BaseServices::resolveServiceAliases('locator');

			// Le premier élément doit être le nom passé
			expect($aliases[0])->toBe('locator');

			// Les suivants doivent être les aliases
			expect($aliases)->toContain(Locator::class);
			expect($aliases)->toContain(LocatorInterface::class);
		});

		it('resolveServiceAliases pour alias intermédiaire retourne tous les aliases', function (): void {
			// Utilise un alias qui n'est pas le canonique
			$aliases = BaseServices::resolveServiceAliases(ServerRequest::class);

			expect($aliases)->toBeAn('array');
			expect($aliases)->toHaveLength(4);
			expect($aliases)->toContain('request'); // Le nom canonique
			expect($aliases)->toContain(Request::class);
			expect($aliases)->toContain(ServerRequest::class);
			expect($aliases)->toContain(ServerRequestInterface::class);
		});

		it('resolveServiceAliases pour différents services', function (): void {
			// Test router
			$routerAliases = BaseServices::resolveServiceAliases('router');
			expect($routerAliases)->toContain('router');
			expect($routerAliases)->toContain(Router::class);
			expect($routerAliases)->toContain(RouterInterface::class);

			// Test response
			$responseAliases = BaseServices::resolveServiceAliases('response');
			expect($responseAliases)->toContain('response');
			expect($responseAliases)->toContain(Response::class);
			expect($responseAliases)->toContain(ResponseInterface::class);

			// Test routes
			$routesAliases = BaseServices::resolveServiceAliases('routes');
			expect($routesAliases)->toContain('routes');
			expect($routesAliases)->toContain(RouteCollection::class);
			expect($routesAliases)->toContain(RouteCollectionInterface::class);
		});

		it('resolveServiceAliases avec cache intégration', function (): void {
			// Test l'intégration avec le cache de serviceName
			// Premier appel
			$aliases1 =  BaseServices::resolveServiceAliases('locator');

			// Deuxième appel (devrait utiliser le cache de serviceName)
			$aliases2 =  BaseServices::resolveServiceAliases('locator');

			expect($aliases1)->toBe($aliases2);
			expect($aliases1)->toHaveLength(3);
		});

		it('resolveServiceAliases insensible à la casse pour noms canoniques', function (): void {
			// serviceName() convertit en lowercase, donc "LOCATOR" devrait devenir "locator"
			$aliases =  BaseServices::resolveServiceAliases('LOCATOR');

			expect($aliases)->toContain('locator');
			expect($aliases)->toContain(Locator::class);
			expect($aliases)->toContain(LocatorInterface::class);
		});
	});
});
