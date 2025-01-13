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
use BlitzPHP\Contracts\Http\StatusCode;
use BlitzPHP\Exceptions\HttpException;
use BlitzPHP\Exceptions\RouterException;
use BlitzPHP\Http\Redirection;
use BlitzPHP\Http\Response;
use BlitzPHP\Http\UrlGenerator;
use BlitzPHP\Router\RouteCollection;
use BlitzPHP\Session\Cookie\CookieCollection;
use BlitzPHP\Spec\Mock\MockRequest;
use BlitzPHP\Spec\ReflectionHelper;

use function Kahlan\expect;

describe('Redirection', function (): void {
    beforeAll(function (): void {
        $this->routes = new RouteCollection(service('locator'), (object) config('routing'));
        Services::injectMock('routes', $this->routes);

        $this->request = new MockRequest();
        Services::injectMock('request', $this->request);
    });

    beforeEach(function (): void {
    });

    describe('Redirection simple', function (): void {
        it('Redirection vers une URL complete', function (): void {
            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            $response = $response->to('http://example.com/foo');

            expect($response->hasHeader('Location'))->toBeTruthy();
            expect($response->getHeaderLine('Location'))->toBe('http://example.com/foo');
        });

        it('Redirection vers une URL relative convertie en URL complete', function (): void {
            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            $response = $response->to('/foo');

            expect($response->hasHeader('Location'))->toBeTruthy();
            expect($response->getHeaderLine('Location'))->toBe('http://example.com/foo');
        });

        it('Redirection avec une baseURL personalisee', function (): void {
            config(['app.base_url' => 'http://example.com/test/']);

            $request  = new MockRequest();
            $response = new Redirection(new UrlGenerator($this->routes, $request));

            $response = $response->to('/foo');

            expect($response->hasHeader('Location'))->toBeTruthy();
            expect($response->getHeaderLine('Location'))->toBe('http://example.com/test/foo');

            config(['app.base_url' => BASE_URL]);
        });
    });

    describe('Redirection vers une route', function (): void {
        it('Redirection vers une route', function (): void {
            $this->routes->add('exampleRoute', 'Home::index');

            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            $response = $response->route('exampleRoute');

            expect($response->hasHeader('Location'))->toBeTruthy();
            expect($response->getHeaderLine('Location'))->toBe('http://example.com/exampleRoute');

            $this->routes->add('exampleRoute2', 'Home::index', ['as' => 'homepage']);

            $response = $response->route('homepage');

            expect($response->hasHeader('Location'))->toBeTruthy();
            expect($response->getHeaderLine('Location'))->toBe('http://example.com/exampleRoute2');
        });

        it('Redirection vers un mauvais nom de route', function (): void {
            $this->routes->add('exampleRoute', 'Home::index');

            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            expect(static fn () => $response->route('differentRoute'))
                ->toThrow(new HttpException());
        });

        it('Redirection vers une mauvaise methode de controleur', function (): void {
            $this->routes->add('exampleRoute', 'Home::index');

            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            expect(static fn () => $response->route('Bad::badMethod'))
                ->toThrow(new HttpException());
        });

        it('Redirection vers une route nommee et avec une baseURL personalisee', function (): void {
            config(['app.base_url' => 'http://example.com/test/']);

            $request  = new MockRequest();
            $response = new Redirection(new UrlGenerator($this->routes, $request));

            $this->routes->add('exampleRoute', 'Home::index');

            $response = $response->route('exampleRoute');

            expect($response->hasHeader('Location'))->toBeTruthy();
            expect($response->getHeaderLine('Location'))->toBe('http://example.com/test/exampleRoute');

            config(['app.base_url' => BASE_URL]);
        });

        it('Redirection vers une route avec parametres', function (): void {
            $this->routes->add('users/(:num)', 'Home::index', ['as' => 'users.profile']);

            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            $response = $response->route('users.profile', [123]);

            expect($response->hasHeader('Location'))->toBeTruthy();
            expect($response->getHeaderLine('Location'))->toBe('http://example.com/users/123');

            expect(static fn () => $response->route('users.profile'))->toThrow(new InvalidArgumentException());
            expect(static fn () => $response->route('users.profile', ['user']))->toThrow(new RouterException('A parameter does not match the expected type.'));
        });
    });

    describe('With', function (): void {
        it('WithInput', function (): void {
            $_SESSION = [];
            $_GET     = ['foo' => 'bar'];
            $_POST    = ['bar' => 'baz'];

            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            $returned = $response->withInput();

            expect($response)->toBe($returned);
            expect($_SESSION)->toContainKey('_blitz_old_input');
            expect($_SESSION['_blitz_old_input']['get']['foo'])->toBe('bar');
            expect($_SESSION['_blitz_old_input']['post']['bar'])->toBe('baz');
        });

        it('With', function (): void {
            $_SESSION = [];

            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            $returned = $response->with('foo', 'bar');

            expect($response)->toBe($returned);
            expect($_SESSION)->toContainKey('foo');
        });

        it('WithCookies', function (): void {
            Services::set(
                Response::class,
                service('response')->cookie('foo', 'bar')
            );

            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            expect($response->hasCookie('foo'))->toBeFalsy();

            $response = $response->withCookies();

            expect($response->hasCookie('foo'))->toBeTruthy();
            expect($response->getCookie('foo'))->toContainKey('value');
            expect($response->getCookie('foo')['value'])->toBe('bar');

            $response = service('response');

            ReflectionHelper::setPrivateProperty($response, '_cookies', new CookieCollection());
            Services::set(Response::class, $response);
        });
        it('WithCookies vides', function (): void {
            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            $response = $response->withCookies();

            expect($response->getCookies())->toBe([]);
        });

        it('WithHeaders', function (): void {
            Services::set(
                Response::class,
                $baseResponse = service('response')->header('foo', 'bar')
            );

            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            expect($response->hasHeader('foo'))->toBeFalsy();

            $response = $response->withHeaders();

            foreach ($baseResponse->getHeaders() as $name => $value) {
                expect($response->hasHeader($name))->toBeTruthy();
                expect($value)->toBe($response->getHeader($name));
            }
        });

        it('WithHeaders vide', function (): void {
            $baseResponse = service('response');

            foreach (array_keys($baseResponse->getHeaders()) as $key) {
                $baseResponse = $baseResponse->withoutHeader($key);
            }
            Services::set(Response::class, $baseResponse);

            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            $response = $response->withHeaders();

            expect(count($response->getHeaders()))->toBe(1);
        });

        it('WithErrors', function (): void {
            $_SESSION = [];

            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            $returned = $response->withErrors('login failed');

            expect($response)->toBe($returned);
            expect($_SESSION)->toContainKey('errors');
            expect($_SESSION['errors'])->toContainKey('default');
            expect($_SESSION['errors']['default'])->toBe('login failed');
        });
    });

    describe('Redirect back', function (): void {
        it('back', function (): void {
            $_SERVER['HTTP_REFERER'] = 'http://somewhere.com';

            $this->request = new MockRequest();
            Services::injectMock('request', $this->request);

            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            $response = $response->back();

            expect($response->getHeaderLine('Location'))->toBe('http://somewhere.com');
        });

        it('HTTP REFERER manquant', function (): void {
            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            $returned = $response->back();

            expect($response)->toBeAnInstanceOf($returned::class);
        });
    });

    describe('Methodes raccourcies', function (): void {
        it('home', function (): void {
            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            $response = $response->home();

            expect($response->hasHeader('Location'))->toBeTruthy();
            expect($response->getStatusCode())->toBe(StatusCode::FOUND);
            expect($response->getHeaderLine('Location'))->toBe('http://example.com');

            $this->routes->add('exampleRouteHome', 'Home::index', ['as' => 'home']);
            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            $response = $response->home();
            expect($response->hasHeader('Location'))->toBeTruthy();
            expect($response->getHeaderLine('Location'))->toBe('http://example.com/exampleRouteHome');
        });

        it('action', function (): void {
            $this->routes->add('action', 'Controller::index');
            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            $response = $response->action(['Controller', 'index']);
            expect($response->hasHeader('Location'))->toBeTruthy();
            expect($response->getHeaderLine('Location'))->toBe('http://example.com/action');

            $response = $response->action('Controller::index');
            expect($response->hasHeader('Location'))->toBeTruthy();
            expect($response->getHeaderLine('Location'))->toBe('http://example.com/action');

            $this->routes->add('action/(:slug)', 'Action::index/$1');
            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            $response = $response->action(['Action', 'index'], ['une-action']);
            expect($response->hasHeader('Location'))->toBeTruthy();
            expect($response->getHeaderLine('Location'))->toBe('http://example.com/action/une-action');

            expect(static fn () => $response->action('fackeAction::method'))->toThrow(new RouterException('Action fackeAction::method not defined.'));
        });

        it('away', function (): void {
            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            $response = $response->away('http://google.com');

            expect($response->hasHeader('Location'))->toBeTruthy();
            expect($response->getHeaderLine('Location'))->toBe('http://google.com');
        });

        it('secure', function (): void {
            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            $response = $response->secure('foo');

            expect($response->hasHeader('Location'))->toBeTruthy();
            expect($response->getHeaderLine('Location'))->toBe('https://example.com/foo');
        });

        it('refresh', function (): void {
            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            $response = $response->refresh();

            expect($response->hasHeader('Location'))->toBeTruthy();
            expect($response->getHeaderLine('Location'))->toBe(trim(BASE_URL, '/'));
        });

        it('guest', function (): void {
            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            $response = $response->guest('home');

            expect($response->hasHeader('Location'))->toBeTruthy();
            expect($response->getHeaderLine('Location'))->toBe('http://example.com/home');
        });

        it('intended', function (): void {
            $response = new Redirection(new UrlGenerator($this->routes, $this->request));

            $response = $response->intended();
            expect($response->hasHeader('Location'))->toBeTruthy();
            expect($response->getHeaderLine('Location'))->toBe('http://example.com');

            $response->setIntendedUrl('home');

            $response = $response->intended();
            expect($response->hasHeader('Location'))->toBeTruthy();
            expect($response->getHeaderLine('Location'))->toBe('http://example.com/home');
        });
    });
});
