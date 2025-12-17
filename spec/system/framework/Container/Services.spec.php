<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Container\Services;
use BlitzPHP\Http\Request;
use BlitzPHP\Http\Response;

use function Kahlan\expect;
use function Kahlan\allow;

describe('Container / Services', function (): void {
    beforeAll(function () {
        // Services::reset(true);
    });

    beforeEach(function () {
        // Services::reset(true);
    });

    afterEach(function () {
        // Services::reset(true);
    });

    describe('Services de base', function () {
        it('cache retourne instance', function () {
            $cache = Services::cache([], false);
            expect($cache)->toBeAnInstanceOf('BlitzPHP\Contracts\Cache\CacheInterface');
        });

        it('config retourne instance', function () {
            $config = Services::config(false);
            expect($config)->toBeAnInstanceOf('BlitzPHP\Config\Config');
        });

        it('container retourne instance', function () {
            $container = Services::container(false);
            expect($container)->toBeAnInstanceOf('BlitzPHP\Contracts\Container\ContainerInterface');
        });

        it('cookie retourne instance', function () {
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
            expect($cookie)->toBeAnInstanceOf('BlitzPHP\Contracts\Session\CookieManagerInterface');
        });

        it('emitter retourne instance', function () {
            $emitter = Services::emitter(false);
            expect($emitter)->toBeAnInstanceOf('BlitzPHP\Http\ResponseEmitter');
        });

        it('event retourne instance', function () {
            $event = Services::event(false);
            expect($event)->toBeAnInstanceOf('BlitzPHP\Contracts\Event\EventManagerInterface');
        });

        it('fs retourne instance', function () {
            $fs = Services::fs(false);
            expect($fs)->toBeAnInstanceOf('BlitzPHP\Filesystem\Filesystem');
        });
    });

    describe('Services avec configuration', function () {
        it('encrypter retourne instance', function () {
			$config = config('encryption');
			config()->set('encryption', ['key' => 'test', 'driver' => 'OpenSSL']);

            $encrypter = Services::encrypter([], false);
            expect($encrypter)->toBeAnInstanceOf('BlitzPHP\Contracts\Security\EncrypterInterface');

			config()->set('encryption', $config);
        });

        it('hashing retourne instance', function () {
            allow('config')->toBeCalled()->andReturn([
                'hashing' => ['driver' => 'bcrypt']
            ]);
            $hasher = Services::hashing([], false);
            expect($hasher)->toBeAnInstanceOf('BlitzPHP\Contracts\Security\HasherInterface');
        });

        it('mail retourne instance', function () {
            allow('config')->toBeCalled()->andReturn([
                'mail' => ['driver' => 'log']
            ]);
            $mail = Services::mail([], false);
            expect($mail)->toBeAnInstanceOf('BlitzPHP\Contracts\Mail\MailerInterface');
        });
    });

    describe('Services HTTP', function () {
        it('request retourne instance', function () {
            $request = Services::request(false);
            expect($request)->toBeAnInstanceOf(Request::class);
        });

        it('response retourne instance', function () {
            $response = Services::response(false);
            expect($response)->toBeAnInstanceOf(Response::class);
        });

        it('router retourne instance', function () {
            $router = Services::router(null, null, false);
            expect($router)->toBeAnInstanceOf('BlitzPHP\Contracts\Router\RouterInterface');
        });

        it('routes retourne instance', function () {
            $routes = Services::routes(false);
            expect($routes)->toBeAnInstanceOf('BlitzPHP\Contracts\Router\RouteCollectionInterface');
        });

        it('negotiator retourne instance', function () {
            $negotiator = Services::negotiator(null, false);
            expect($negotiator)->toBeAnInstanceOf('BlitzPHP\Http\Negotiator');
        });

        it('redirection retourne instance', function () {
            $redirection = Services::redirection(false);
            expect($redirection)->toBeAnInstanceOf('BlitzPHP\Http\Redirection');
        });

        it('uri retourne instance', function () {
            $uri = Services::uri('https://example.com', false);
            expect($uri)->toBeAnInstanceOf('Psr\Http\Message\UriInterface');
        });

        it('urlGenerator retourne instance via factory', function () {
            $urlGenerator = Services::factory('BlitzPHP\Http\UrlGenerator');
            expect($urlGenerator)->toBeAnInstanceOf('BlitzPHP\Http\UrlGenerator');
        });
    });

    describe('Services de session et stockage', function () {
        it('session retourne instance', function () {
            allow('config')->toBeCalled()->andReturn([
                'session' => [],
                'cookie' => []
            ]);
            allow('Helpers::ipAddress')->toBeCalled()->andReturn('127.0.0.1');

            $session = Services::session(false);
            expect($session)->toBeAnInstanceOf('BlitzPHP\Contracts\Session\SessionInterface');
        });

        it('storage retourne instance', function () {
            allow('config')->toBeCalled()->andReturn([
                'filesystems' => []
            ]);
            $storage = Services::storage(false);
            expect($storage)->toBeAnInstanceOf('BlitzPHP\Filesystem\FilesystemManager');
        });
    });

    describe('Services de débogage et utilitaires', function () {
        it('logger retourne instance', function () {
            $logger = Services::logger(false);
            expect($logger)->toBeAnInstanceOf('Psr\Log\LoggerInterface');
        });

        it('timer retourne instance', function () {
            $timer = Services::timer(false);
            expect($timer)->toBeAnInstanceOf('BlitzPHP\Debug\Timer');
        });

        it('toolbar retourne instance', function () {
            allow('config')->toBeCalled()->andReturn([
                'toolbar' => []
            ]);
            $toolbar = Services::toolbar(null, false);
            expect($toolbar)->toBeAnInstanceOf('BlitzPHP\Debug\Toolbar');
        });

        it('translator retourne instance', function () {
            $translator = Services::translator('fr', false);
            expect($translator)->toBeAnInstanceOf('BlitzPHP\Translator\Translate');
        });

        it('viewer retourne instance', function () {
            $viewer = Services::viewer(false);
            expect($viewer)->toBeAnInstanceOf('BlitzPHP\View\View');
        });

        it('componentLoader retourne instance', function () {
            $loader = Services::componentLoader(false);
            expect($loader)->toBeAnInstanceOf('BlitzPHP\View\Components\ComponentLoader');
        });

        it('responsecache retourne instance', function () {
            $responseCache = Services::responsecache(null, false, false);
            expect($responseCache)->toBeAnInstanceOf('BlitzPHP\Cache\ResponseCache');
        });
    });

    describe('Instances partagées', function () {
        it('services partagés retournent même instance', function () {
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

        it('services non partagés retournent nouvelles instances', function () {
            $request1 = Services::request(false);
            $request2 = Services::request(false);
            expect($request1)->not->toBe($request2);
            expect($request1)->toBeAnInstanceOf(Request::class);
            expect($request2)->toBeAnInstanceOf(Request::class);
        });
    });

    describe('Méthodes héritées', function () {
        it('peut utiliser get pour récupérer un service', function () {
            Services::request(); // Crée une instance partagée
            $request = Services::get('request');
            expect($request)->toBeAnInstanceOf(Request::class);
        });

        it('peut utiliser set pour définir un service', function () {
            $custom = new stdClass();
            $custom->name = 'custom';
            Services::set('custom', $custom);
            expect(Services::get('custom'))->toBe($custom);
        });

        it('peut utiliser singleton', function () {
            $instance1 = Services::singleton('custom');
            $instance2 = Services::singleton('custom');
            expect($instance1)->toBe($instance2);
        });
    });
});
