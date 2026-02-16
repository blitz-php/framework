<?php
/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Http\Request;
use BlitzPHP\Middlewares\ShareErrorsFromSession;
use BlitzPHP\Session\Store;
use BlitzPHP\Utilities\Helpers;
use BlitzPHP\View\View;
use BlitzPHP\Validation\ErrorBag;
use Spec\BlitzPHP\Middlewares\TestRequestHandler;

use function Kahlan\expect;

describe('Middleware / ShareErrorsFromSession', function (): void {
    beforeAll(function (): void {
        $this->getSession = (fn($flashdata = []) => new class($flashdata) extends Store {
				public function __construct(private array $flashdata)
				{
					parent::__construct(config('session'), config('cookie'), '127.0.0.1');
				}

				public function getFlashdata(?string $key = null): mixed
				{
					return $this->flashdata[$key] ?? null;
				}
			});

        $this->getView = (fn() => new class extends View {
				public static array $sharedData = [];

				public static function share(array|Closure|string $key, mixed $value = null): void
				{
					$key = Helpers::value($key);

					if (is_string($key)) {
						$key = [$key => $value];
					}
					foreach ($key as $k => $v) {
						self::$sharedData[$k] = $v;
					}
				}
			});

		$this->request = function($session) {
			$request = Mockery::mock(Request::class);
			$request->shouldReceive('session')->andReturn($session);
			return $request;
		};
    });

    it("devrait partager un ErrorBag vide lorsque la session n'a pas d'erreurs", function (): void {
        $session = $this->getSession();
        $view = $this->getView();

        $request = $this->request($session);

        $handler = new TestRequestHandler();
        $middleware = new ShareErrorsFromSession($view);
        $middleware->process($request, $handler);

        expect(isset($view::$sharedData['errors']))->toBeTruthy();
        expect($view::$sharedData['errors'])->toBeAnInstanceOf(ErrorBag::class);
        expect($view::$sharedData['errors']->empty())->toBeTruthy();
    });

    it("devrait partager les erreurs de la session flash", function (): void {
        $sessionErrors = [
            'email'    => ['email' => 'L\'email est invalide'],
            'password' => ['required' => 'Le mot de passe est requis'],
        ];

        $session = $this->getSession(['errors' => $sessionErrors]);
        $view = $this->getView();

        $request = $this->request($session);

        $handler = new TestRequestHandler();
        $middleware = new ShareErrorsFromSession($view);
        $middleware->process($request, $handler);

        expect(isset($view::$sharedData['errors']))->toBeTruthy();
        expect($view::$sharedData['errors'])->toBeAnInstanceOf(ErrorBag::class);
        expect($view::$sharedData['errors']->has('email'))->toBeTruthy();
        expect($view::$sharedData['errors']->first('email'))->toBe('L\'email est invalide');
        expect($view::$sharedData['errors']->has('password'))->toBeTruthy();
    });

    it("devrait gérer les données flash vides", function (): void {
        $session = $this->getSession(['errors' => []]);
        $view = $this->getView();

        $request = $this->request($session);

        $handler = new TestRequestHandler();
        $middleware = new ShareErrorsFromSession($view);
        $middleware->process($request, $handler);

        expect(isset($view::$sharedData['errors']))->toBeTruthy();
        expect($view::$sharedData['errors']->empty())->toBeTruthy();
    });
});
