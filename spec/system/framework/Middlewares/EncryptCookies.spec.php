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
use BlitzPHP\Contracts\Session\CookieInterface;
use BlitzPHP\Contracts\Session\CookieManagerInterface;
use BlitzPHP\Http\Request;
use BlitzPHP\Http\Response;
use BlitzPHP\Session\Cookie\CookieCollection;
use BlitzPHP\Session\Cookie\CookieValuePrefix;
use BlitzPHP\Middlewares\EncryptCookies;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spec\BlitzPHP\Middlewares\TestRequestHandler;

use function Kahlan\expect;

describe('Middleware / EncryptCookies', function (): void {
    beforeAll(function (): void {
        $this->getEncrypter = function () {
            $encrypter = Mockery::mock(EncrypterInterface::class, [
				'getKey' => 'test-key',
			]);

			$encrypter->shouldReceive('encrypt')->andReturnUsing(fn($value) => 'encrypted:' . $value);
			$encrypter->shouldReceive('decrypt')->andReturnUsing(fn($value) => str_replace('encrypted:', '', $value));

			return $encrypter;
        };

        $this->getCookieManager = function () {
			$cookieManager = Mockery::mock(CookieManagerInterface::class);
			$cookieManager->shouldReceive('make')->andReturnUsing(function($name, $value) {
				$cookie = Mockery::mock(CookieInterface::class, [
					'getName' => $name,
					'getValue' => $value,
				]);
				$cookie->shouldReceive('withValue')->andReturnUsing(function($newValue) use ($cookie) {
					$cookie->value = $newValue;

					return $cookie;
				});

				return $cookie;
			});

			return $cookieManager;
        };
    });

    it("devrait décrypter les cookies entrant", function (): void {
        $encrypter = $this->getEncrypter();
        $cookieManager = $this->getCookieManager();

        $request = Mockery::mock(Request::class, [
            'getCookieParams' => [
				'session' => 'encrypted:' . CookieValuePrefix::create('session', 'test-key') . 'session-data',
			],
		]);
		$request->shouldReceive('withCookieCollection')->andReturnUsing(function ($collection) use($request) {
			$request->cookieCollection = $collection;
			return $request;
		});

        $handler = new TestRequestHandler();
        $middleware = new EncryptCookies($encrypter, $cookieManager);
        $result = $middleware->process($request, $handler);

        expect($result)->toBeAnInstanceOf(ResponseInterface::class);
    });

    it("devrait crypter les cookies sortants", function (): void {
        $encrypter = $this->getEncrypter();
        $cookieManager = $this->getCookieManager();

		$cookie = Mockery::mock(CookieInterface::class, [
			'getName' => 'session',
			'getValue' => 'session-data',
			'getId' => 'session-id',
		]);
		$cookie->shouldReceive('withValue')->andReturnUsing(function($value) use ($cookie) {
			$cookie->encryptedValue = $value;

			return $cookie;
		});

		$response = Mockery::mock(Response::class);
		$response->shouldReceive('getCookieCollection')->andReturnUsing(function() use ($cookie) {
			$collection = new CookieCollection();
			$collection->add($cookie);
			return $collection;
		});
		$response->shouldReceive('withCookie')->andReturnUsing(function($cookie) use ($response) {
			return $response;
		});

        $handler = Mockery::mock(RequestHandlerInterface::class, [
            'handle' => $response,
        ]);

		$request = Mockery::mock(Request::class, [
            'getCookieParams' => [
				'session' => 'encrypted:' . CookieValuePrefix::create('session', 'test-key') . 'session-data',
			],
		]);
		$request->shouldReceive('withCookieCollection')->andReturn($request);

        $middleware = new EncryptCookies($encrypter, $cookieManager);
        $result = $middleware->process($request, $handler);

        expect($result)->toBeAnInstanceOf(Response::class);
    });

    it("devrait ignorer les cookies dans la liste 'except'", function (): void {
        $encrypter = $this->getEncrypter();
        $cookieManager = $this->getCookieManager();

        $request = Mockery::mock(Request::class, [
            'getCookieParams' => ['analytics' => 'plain-value'],
		]);
		$request->shouldReceive('withCookieCollection')->andReturn($request);

        $middleware = new EncryptCookies($encrypter, $cookieManager);
        $middleware->disableFor(['analytics']);

        expect($middleware->isDisabled('analytics'))->toBeTruthy();
        expect($middleware->isDisabled('session'))->toBeFalsy();

        $handler = new TestRequestHandler();
        $result = $middleware->process($request, $handler);

        expect($result)->toBeAnInstanceOf(ResponseInterface::class);
    });

    it("devrait valider les valeurs de cookie avec préfixe", function (): void {
        $encrypter = $this->getEncrypter();

        $value = CookieValuePrefix::create('test-cookie', 'test-key') . 'cookie-value';
        $encryptedValue = 'encrypted:' . $value;

        $request = Mockery::mock(Request::class, [
            'getCookieParams' => ['test-cookie' => $encryptedValue],
		]);
		$request->shouldReceive('withCookieCollection')->andReturnUsing(function($collection) use($request) {
			$request->cookieCollection = $collection;
			return $request;
		});

        $cookieManager = $this->getCookieManager();
        $middleware = new EncryptCookies($encrypter, $cookieManager);
        $handler = new TestRequestHandler();

        $result = $middleware->process($request, $handler);

        expect($result)->toBeAnInstanceOf(ResponseInterface::class);
    });
});
