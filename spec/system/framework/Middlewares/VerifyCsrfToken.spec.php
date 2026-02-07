<?php
/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Contracts\Security\EncrypterInterface;
use BlitzPHP\Exceptions\TokenMismatchException;
use BlitzPHP\Http\Request;
use BlitzPHP\Session\Store;
use BlitzPHP\Middlewares\VerifyCsrfToken;
use BlitzPHP\Utilities\Reflection\ReflectionClass;
use Spec\BlitzPHP\Middlewares\TestRequestHandler;

use function Kahlan\expect;

describe('Middleware / VerifyCsrfToken', function (): void {
    beforeAll(function (): void {
        $this->getEncrypter = function ($decryptValue = null) {
			$encrypter = Mockery::mock(EncrypterInterface::class);
			$encrypter->shouldReceive('decrypt')->andReturnUsing(fn($value) => $decryptValue ?? $value);
			$encrypter->shouldReceive('decrypt')->andReturn('test-key');

			return $encrypter;
		};

        $this->getSession = function ($token = 'valid-token') {
			return Mockery::mock(Store::class, ['token' => $token]);
        };
    });

    it("devrait autoriser les requêtes de lecture (GET, HEAD, OPTIONS)", function (): void {
        $encrypter = $this->getEncrypter();
        $session = $this->getSession();

        $request = Mockery::mock(Request::class, [
            'method'  => 'GET',
            'session' => $session,
		]);

        $handler = new TestRequestHandler();
        $middleware = new VerifyCsrfToken($encrypter);

        expect(function () use ($middleware, $request, $handler) {
            $middleware->process($request, $handler);
        })->not->toThrow();
    });

    it("devrait vérifier le jeton CSRF dans les paramètres POST", function (): void {
        $encrypter = $this->getEncrypter();
        $session = $this->getSession('valid-token');

        $request = Mockery::mock(Request::class, [
            'method' => 'POST',
            'session' => $session,
			'header' => null,
		]);
		$request->shouldReceive('input')->andReturnUsing(fn($key) => $key === '_token' ? 'valid-token' : null);

        $handler = new TestRequestHandler();
        $middleware = new VerifyCsrfToken($encrypter);

        expect(function () use ($middleware, $request, $handler) {
            $middleware->process($request, $handler);
        })->not->toThrow();
    });

    it("devrait vérifier le jeton CSRF dans l'en-tête X-CSRF-TOKEN", function (): void {
        $encrypter = $this->getEncrypter();
        $session = $this->getSession('valid-token');

        $request = Mockery::mock(Request::class, [
            'method' => 'POST',
            'session' => $session,
			'input' => null,
		]);
		$request->shouldReceive('header')->andReturnUsing(fn($key) => $key === 'X-CSRF-TOKEN' ? 'valid-token' : null);

        $handler = new TestRequestHandler();
        $middleware = new VerifyCsrfToken($encrypter);

        expect(function () use ($middleware, $request, $handler) {
            $middleware->process($request, $handler);
        })->not->toThrow();
    });

    it("devrait lancer une exception lorsque les jetons ne correspondent pas", function (): void {
        $encrypter = $this->getEncrypter();
        $session = $this->getSession('valid-token');

        $request = Mockery::mock(Request::class, [
            'method' => 'POST',
            'session' => $session,
			'header' => null,
			'fullUrlIs' => false,
			'pathIs' => false,
		]);
		$request->shouldReceive('input')->andReturnUsing(fn($key) => $key === '_token' ? 'invalid-token' : null);

        $handler = new TestRequestHandler();
        $middleware = new VerifyCsrfToken($encrypter);

		$_SERVER['ENVIRONMENT'] = 'development';

        expect(function () use ($middleware, $request, $handler) {
			$middleware->process($request, $handler);
		})->toThrow(new TokenMismatchException('Erreur de jeton CSRF.'));

		$_SERVER['ENVIRONMENT'] = 'testing';
    });

    it("devrait ignorer la vérification pour les URI dans la liste 'except'", function (): void {
        $encrypter = $this->getEncrypter();
        $session = $this->getSession();

		$request = Mockery::mock(Request::class, [
            'method' => 'POST',
            'session' => $session,
		]);
		$request->shouldReceive('fullUrlIs')->andReturnUsing(fn($url) => $url === '/api/webhook');
		$request->shouldReceive('pathIs')->andReturnUsing(fn($path) => $path === '/api/webhook');

        $handler = new TestRequestHandler();
        $middleware = new VerifyCsrfToken($encrypter);
		ReflectionClass::make($middleware)->setValue('except', ['/api/webhook']);

        expect(function () use ($middleware, $request, $handler) {
            $middleware->process($request, $handler);
        })->not->toThrow();
    });
});
