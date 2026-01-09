<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Controllers\BaseController;
use BlitzPHP\Validation\Validation;
use Dimtrovich\Validation\ValidatedInput;
use Kahlan\Plugin\Double;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\NullLogger;

use function Kahlan\expect;
use function Kahlan\allow;

describe('Controllers / BaseController', function (): void {
    beforeEach(function (): void {
        // Mocks
        $this->mockRequest = Double::instance([
			'implements' => [ServerRequestInterface::class]
		]);

		$this->mockResponse = Double::instance([
			'implements' => [ResponseInterface::class]
		]);

        $this->logger = new NullLogger();

        // Mock du container de service
        $mockContainer = new class {
            public array $services = [];
            public function set($key, $value) {
                $this->services[$key] = $value;
                return $this;
            }
        };

        allow('service')->toBeCalled()->with('container')->andReturn($mockContainer);
        allow('service')->toBeCalled()->with('responsecache')->andReturn(new class {
            public function setTtl($time) {}
        });

        // Mock de force_https
        allow('force_https')->toBeCalled()->andReturn(null);
        allow('helper')->toBeCalled()->andReturn(null);

        // Classe de test
        $this->controller = new class extends BaseController {
            protected array $helpers = ['scl', 'url'];
            protected $forceHTTPS = 0;

            // Méthodes accessibles pour les tests
            public function testInitialize($request, $response, $logger) {
                return $this->initialize($request, $response, $logger);
            }

            public function testValidate($rules, $messages = []) {
                return $this->validate($rules, $messages);
            }

            public function testValidateData($data, $rules, $messages = []) {
                return $this->validateData($data, $rules, $messages);
            }

            public function testValidation($rules, $messages = []) {
                return $this->validation($rules, $messages);
            }

            public function testForceHTTPS($duration = 31536000) {
                return $this->forceHTTPS($duration);
            }

            public function testCachePage($time) {
                return $this->cachePage($time);
            }

            // Accesseurs pour les propriétés protégées
            public function getRequest() {
                return $this->request;
            }

            public function getResponse() {
                return $this->response;
            }

            public function getLogger() {
                return $this->logger;
            }
        };
		$this->controller->initialize(
			service('request')->withParsedBody(['name' => 'test']),
			service('response'),
			$this->logger
		);
    });

    describe('Initialisation', function (): void {
        it('Doit initialiser les propriétés de base', function (): void {
            $this->controller->testInitialize($this->mockRequest, $this->mockResponse, $this->logger);

            expect($this->controller->getRequest())->toBe($this->mockRequest);
            expect($this->controller->getResponse())->toBe($this->mockResponse);
            expect($this->controller->getLogger())->toBe($this->logger);
        });

        it('Doit forcer HTTPS si configuré', function (): void {
            $controller = new class extends BaseController {
                protected $forceHTTPS = 3600;

                public function testInitialize($request, $response, $logger) {
                    return $this->initialize($request, $response, $logger);
                }
            };

            $controller->testInitialize(service('request'), $this->mockResponse, $this->logger);

            allow('force_https')->toBeCalled()->with(3600, $this->mockRequest, $this->mockResponse);
        });
    });

    describe('Validation', function (): void {
        it('Doit valider les données de la requête', function (): void {
			$result = $this->controller->testValidate(
				['name' => 'required'],
				['name:required' => 'Le nom est requis']
			);

            expect($result)->toBeAnInstanceOf(ValidatedInput::class);
        });

        it('Doit valider des données arbitraires', function (): void {
            $result = $this->controller->testValidateData(
                ['email' => 'test@example.com'],
                ['email' => 'required|email'],
                ['email:email' => 'Email invalide']
            );

            expect($result)->toBeAnInstanceOf(ValidatedInput::class);
        });

        it('Doit créer un validateur', function (): void {
            $result = $this->controller->testValidation(['name' => 'required']);

            expect($result)->toBeAnInstanceOf(Validation::class);
        });
    });
});
