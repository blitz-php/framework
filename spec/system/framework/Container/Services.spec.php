<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
use BlitzPHP\Contracts\Cache\CacheInterface;
use BlitzPHP\Config\Config;
use BlitzPHP\Contracts\Container\ContainerInterface;
use BlitzPHP\Contracts\Session\CookieManagerInterface;
use BlitzPHP\Http\ResponseEmitter;
use BlitzPHP\Contracts\Event\EventManagerInterface;
use BlitzPHP\Filesystem\Filesystem;
use BlitzPHP\Contracts\Security\EncrypterInterface;
use BlitzPHP\Contracts\Security\HasherInterface;
use BlitzPHP\Contracts\Mail\MailerInterface;
use BlitzPHP\Contracts\Router\RouterInterface;
use BlitzPHP\Contracts\Router\RouteCollectionInterface;
use BlitzPHP\Http\Negotiator;
use BlitzPHP\Http\Redirection;
use Psr\Http\Message\UriInterface;
use BlitzPHP\Http\UrlGenerator;
use BlitzPHP\Contracts\Session\SessionInterface;
use BlitzPHP\Filesystem\FilesystemManager;
use Psr\Log\LoggerInterface;
use BlitzPHP\Debug\Timer;
use BlitzPHP\Debug\Toolbar;
use BlitzPHP\Translator\Translate;
use BlitzPHP\View\View;
use BlitzPHP\View\Components\ComponentLoader;
use BlitzPHP\Cache\ResponseCache;
use BlitzPHP\Container\Services;
use BlitzPHP\Http\Request;
use BlitzPHP\Http\Response;

use function Kahlan\expect;
use function Kahlan\allow;

describe('Container / Services', function (): void {
    beforeAll(function (): void {
        // Services::reset(true);
    });

    beforeEach(function (): void {
        // Services::reset(true);
    });

    afterEach(function (): void {
        // Services::reset(true);
    });

    describe('Services de base', function (): void {
        it('cache retourne instance', function (): void {
            $cache = Services::cache([], false);
            expect($cache)->toBeAnInstanceOf(CacheInterface::class);
        });

        it('config retourne instance', function (): void {
            $config = Services::config(false);
            expect($config)->toBeAnInstanceOf(Config::class);
        });

        it('container retourne instance', function (): void {
            $container = Services::container(false);
            expect($container)->toBeAnInstanceOf(ContainerInterface::class);
        });

        it('cookie retourne instance', function (): void {
            allow('config')->toBeCalled()->andReturn([
                'cookie' => [
                    'path' => '/',
                    'domain' => '',
                    'secure' => false,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            ]);
            $cookie = Services::cookie(false);
            expect($cookie)->toBeAnInstanceOf(CookieManagerInterface::class);
        });

        it('emitter retourne instance', function (): void {
            $emitter = Services::emitter(false);
            expect($emitter)->toBeAnInstanceOf(ResponseEmitter::class);
        });

        it('event retourne instance', function (): void {
            $event = Services::event(false);
            expect($event)->toBeAnInstanceOf(EventManagerInterface::class);
        });

        it('fs retourne instance', function (): void {
            $fs = Services::fs(false);
            expect($fs)->toBeAnInstanceOf(Filesystem::class);
        });
    });

    describe('Services avec configuration', function (): void {
        it('encrypter retourne instance', function (): void {
			$config = config('encryption');
			config()->set('encryption', ['key' => 'test', 'driver' => 'OpenSSL']);

            $encrypter = Services::encrypter([], false);
            expect($encrypter)->toBeAnInstanceOf(EncrypterInterface::class);

			config()->set('encryption', $config);
        });

        it('hashing retourne instance', function (): void {
            allow('config')->toBeCalled()->andReturn([
                'hashing' => ['driver' => 'bcrypt']
            ]);
            $hasher = Services::hashing([], false);
            expect($hasher)->toBeAnInstanceOf(HasherInterface::class);
        });

        it('mail retourne instance', function (): void {
            allow('config')->toBeCalled()->andReturn([
                'mail' => ['driver' => 'log']
            ]);
            $mail = Services::mail([], false);
            expect($mail)->toBeAnInstanceOf(MailerInterface::class);
        });
    });

    describe('Services HTTP', function (): void {
        it('request retourne instance', function (): void {
            $request = Services::request(false);
            expect($request)->toBeAnInstanceOf(Request::class);
        });

        it('response retourne instance', function (): void {
            $response = Services::response(false);
            expect($response)->toBeAnInstanceOf(Response::class);
        });

        it('router retourne instance', function (): void {
            $router = Services::router(null, null, false);
            expect($router)->toBeAnInstanceOf(RouterInterface::class);
        });

        it('routes retourne instance', function (): void {
            $routes = Services::routes(false);
            expect($routes)->toBeAnInstanceOf(RouteCollectionInterface::class);
        });

        it('negotiator retourne instance', function (): void {
            $negotiator = Services::negotiator(null, false);
            expect($negotiator)->toBeAnInstanceOf(Negotiator::class);
        });

        it('redirection retourne instance', function (): void {
            $redirection = Services::redirection(false);
            expect($redirection)->toBeAnInstanceOf(Redirection::class);
        });

        it('uri retourne instance', function (): void {
            $uri = Services::uri('https://example.com', false);
            expect($uri)->toBeAnInstanceOf(UriInterface::class);
        });

        it('urlGenerator retourne instance via factory', function (): void {
            $urlGenerator = Services::factory(UrlGenerator::class);
            expect($urlGenerator)->toBeAnInstanceOf(UrlGenerator::class);
        });
    });

    describe('Services de session et stockage', function (): void {
        it('session retourne instance', function (): void {
            allow('config')->toBeCalled()->andReturn([
                'session' => [],
                'cookie' => []
            ]);
            allow('Helpers::ipAddress')->toBeCalled()->andReturn('127.0.0.1');

            $session = Services::session(false);
            expect($session)->toBeAnInstanceOf(SessionInterface::class);
        });

        it('storage retourne instance', function (): void {
            allow('config')->toBeCalled()->andReturn([
                'filesystems' => []
            ]);
            $storage = Services::storage(false);
            expect($storage)->toBeAnInstanceOf(FilesystemManager::class);
        });
    });

    describe('Services de débogage et utilitaires', function (): void {
        it('logger retourne instance', function (): void {
            $logger = Services::logger(false);
            expect($logger)->toBeAnInstanceOf(LoggerInterface::class);
        });

        it('timer retourne instance', function (): void {
            $timer = Services::timer(false);
            expect($timer)->toBeAnInstanceOf(Timer::class);
        });

        it('toolbar retourne instance', function (): void {
            allow('config')->toBeCalled()->andReturn([
                'toolbar' => []
            ]);
            $toolbar = Services::toolbar(null, false);
            expect($toolbar)->toBeAnInstanceOf(Toolbar::class);
        });

        it('translator retourne instance', function (): void {
            $translator = Services::translator('fr', false);
            expect($translator)->toBeAnInstanceOf(Translate::class);
        });

        it('viewer retourne instance', function (): void {
            $viewer = Services::viewer(false);
            expect($viewer)->toBeAnInstanceOf(View::class);
        });

        it('componentLoader retourne instance', function (): void {
            $loader = Services::componentLoader(false);
            expect($loader)->toBeAnInstanceOf(ComponentLoader::class);
        });

        it('responsecache retourne instance', function (): void {
            $responseCache = Services::responsecache(null, false, false);
            expect($responseCache)->toBeAnInstanceOf(ResponseCache::class);
        });
    });

    describe('Instances partagées', function (): void {
        it('services partagés retournent même instance', function (): void {
            $request1 = Services::request();
            $request2 = Services::request();
            expect($request1)->toBe($request2);

            $response1 = Services::response();
            $response2 = Services::response();
            expect($response1)->toBe($response2);

            $config1 = Services::config();
            $config2 = Services::config();
            expect($config1)->toBe($config2);
        });

        it('services non partagés retournent nouvelles instances', function (): void {
            $request1 = Services::request(false);
            $request2 = Services::request(false);
            expect($request1)->not->toBe($request2);
            expect($request1)->toBeAnInstanceOf(Request::class);
            expect($request2)->toBeAnInstanceOf(Request::class);
        });
    });

    describe('Méthodes héritées', function (): void {
        it('peut utiliser get pour récupérer un service', function (): void {
            Services::request(); // Crée une instance partagée
            $request = Services::get('request');
            expect($request)->toBeAnInstanceOf(Request::class);
        });

        it('peut utiliser set pour définir un service', function (): void {
            $custom = new stdClass();
            $custom->name = 'custom';
            Services::set('custom', $custom);
            expect(Services::get('custom'))->toBe($custom);
        });

        it('peut utiliser singleton', function (): void {
            $instance1 = Services::singleton('custom');
            $instance2 = Services::singleton('custom');
            expect($instance1)->toBe($instance2);
        });
    });
});
