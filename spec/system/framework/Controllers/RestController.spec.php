<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */


use BlitzPHP\Validation\ErrorBag;
use Spec\BlitzPHP\App\Controllers\RestController;
use BlitzPHP\Contracts\Http\StatusCode;
use BlitzPHP\Exceptions\ValidationException;
use BlitzPHP\Http\Response;
use Psr\Http\Message\ResponseInterface;

use function Kahlan\expect;
use function Kahlan\allow;

describe('Controllers / RestController', function (): void {
    beforeAll(function (): void {
		$this->request = service('request');
		$this->response = service('response');
		$this->logger = service('logger');
	});

    beforeEach(function (): void {
        // Création du contrôleur
        $this->controller = new RestController();
		$this->controller->initialize($this->request, $this->response, $this->logger);
    });

    describe('Méthode _remap()', function (): void {
        it('Doit gérer une méthode existante', function (): void {
            allow($this->controller)->toReceive('before')->andReturn(null);
            allow($this->controller)->toReceive('after')->andReturn(null);
            allow($this->controller)->toReceive('validateRequest')->andReturn(true);

            $result = $this->controller->testRemap('testMethod');

            expect($result)->toBeAnInstanceOf(ResponseInterface::class);
        });

        it('Doit retourner une erreur pour une méthode inexistante', function (): void {
            $result = $this->controller->testRemap('nonExistentMethod');

            expect($result->getStatusCode())->toBe(501); // Not Implemented
        });

        it('Doit exécuter le hook before', function (): void {
			$mockResponse = new Response(['status' => 201]);
			$this->controller->before = $mockResponse;

            $result = $this->controller->testRemap('testMethod');

            expect($result)->toBe($mockResponse);
        });

        it('Doit gérer les exceptions', function (): void {
            $controller = new class extends RestController {
                public function testMethod(): never {
                    throw new Exception('Test exception');
                }
            };
			$controller->initialize($this->request, $this->response, $this->logger);

			// on force l'environnement a etre sur 'dev' car la fonction 'on_dev' ne gere pas le mode test
			config()->set('app.environment', 'dev');

            $result = $controller->testRemap('testMethod');
			config()->set('app.environment', 'testing');

            expect($result->getStatusCode())->toBe(StatusCode::INTERNAL_ERROR);
        });
    });

    describe('Gestion des exceptions', function (): void {
        it('Doit gérer ValidationException', function (): void {
            $ex = new ValidationException('Validation failed');
            $ex->setErrors(new ErrorBag(['field' => ['Error message']]));

            $result = $this->controller->testHandleException($ex);

            expect($result->getStatusCode())->toBe(StatusCode::BAD_REQUEST);
        });

        it('Doit gérer les autres exceptions en mode développement', function (): void {
            // on force l'environnement a etre sur 'dev' car la fonction 'on_dev' ne gere pas le mode test
			config()->set('app.environment', 'dev');

            $ex = new Exception('Generic error', 1001);

            $result = $this->controller->testHandleException($ex);
			config()->set('app.environment', 'testing');

            expect($result->getStatusCode())->toBe(StatusCode::INTERNAL_ERROR);
        });

        it('Doit gérer les autres exceptions en mode production', function (): void {
            $ex = new Exception('Generic error');

            $result = $this->controller->testHandleException($ex);

            expect($result->getStatusCode())->toBe(StatusCode::BAD_REQUEST);
        });
    });

    describe('Validation de requête', function (): void {
        it('Doit valider les requêtes AJAX uniquement', function (): void {
			config()->set('rest.ajax_only', true);

			$controller = new class extends RestController { };
			$controller->initialize(
				$this->request->withHeader('X-Requested-With', 'XMLHttpRequest'),
				$this->response,
				$this->logger
			);

            $result = $controller->testValidateAjaxOnly();

            expect($result)->toBe(true);
			config()->set('rest.ajax_only', false);
        });

        it('Doit rejeter les requêtes non-AJAX quand requis', function (): void {
            config()->set('rest.ajax_only', true);

            $controller = new class extends RestController {
                public function testValidateAjaxOnly() {
                    return $this->validateAjaxOnly();
                }
            };
			$controller->initialize($this->request, $this->response, $this->logger);

            $result = $controller->testValidateAjaxOnly();

            expect($result)->toBeAnInstanceOf(ResponseInterface::class);
			config()->set('rest.ajax_only', false);
        });

        it('Doit valider HTTPS', function (): void {
			$controller = new class extends RestController { };
			$controller->initialize($this->request, $this->response, $this->logger);

            $result = $controller->testValidateHttps();

            expect($result)->toBe(true);

			config()->set('rest.force_https', true);

			$controller = new class extends RestController { };
			$controller->initialize($this->request, $this->response, $this->logger);

			$result = $controller->testValidateHttps();

            expect($result)->toBeAnInstanceOf(ResponseInterface::class);
			expect($result->getStatusCode())->toBe(StatusCode::FORBIDDEN);

			config()->set('rest.force_https', false);
        });

        it('Doit valider la liste noire d\'IP', function (): void {
			config()->set('rest.ip_blacklist', ['192.168.1.1']);

			$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

			$controller = new class extends RestController { };
			$controller->initialize($this->request, $this->response, $this->logger);

			$result = $controller->testValidateIpBlacklist();

            expect($result)->toBe(true);
			config()->set('rest.ip_blacklist', []);
        });

        it('Doit valider la liste blanche d\'IP', function (): void {
			config()->set('rest.ip_whitelist', ['192.168.1.100']);

			$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

            $controller = new class extends RestController {};
			$controller->initialize($this->request, $this->response, $this->logger);

            $result = $controller->testValidateIpWhitelist();

            expect($result)->toBe(true);
			config()->set('rest.ip_whitelist', []);
        });
    });

    describe('Interface fluide de configuration', function (): void {
        it('Doit restreindre aux requêtes AJAX', function (): void {
            $result = $this->controller->testAjaxOnly();

            expect($result)->toBe($this->controller);
        });

        it('Doit définir le format de retour', function (): void {
            $result = $this->controller->testReturnFormat('xml');

            expect($result)->toBe($this->controller);
        });

        it('Doit forcer HTTPS', function (): void {
            $result = $this->controller->testRequireHttps();

            expect($result)->toBe($this->controller);
        });

        it('Doit définir la liste noire d\'IP', function (): void {
            $result = $this->controller->testIpBlacklist('192.168.1.1', '10.0.0.1');

            expect($result)->toBe($this->controller);
        });

        it('Doit définir la liste blanche d\'IP', function (): void {
            $result = $this->controller->testIpWhitelist('192.168.1.100', '10.0.0.100');

            expect($result)->toBe($this->controller);
        });
    });

    describe('Méthodes de réponse', function (): void {
        it('Doit formater une réponse réussie', function (): void {
            $result = $this->controller->testRespondSuccess('Operation successful', ['id' => 1]);

            expect($result)->toBeAnInstanceOf(ResponseInterface::class);
			expect($result->getStatusCode())->toBe(StatusCode::OK);
			$body = json_decode($result->getBody()->getContents());
			expect($body->result->id)->toBe(1);
			expect($body->message)->toBe('Operation successful');
        });

        it('Doit formater une réponse d\'échec', function (): void {
            $result = $this->controller->testRespondFail('Operation failed', 400, 'ERR_001', ['field' => 'error']);

            expect($result)->toBeAnInstanceOf(ResponseInterface::class);
			expect($result->getStatusCode())->toBe(400);
			$body = json_decode($result->getBody()->getContents());
			expect($body->message)->toBe('Operation failed');
        });

        it('Doit formater le résultat', function (): void {
            $data = ['name' => 'John', 'age' => 30];
            $result = $this->controller->testFormatResult($data);

            expect($result)->toBe($data);
        });

        it('Doit formater une entité avec toArray()', function (): void {
            $entity = new class {
                public function toArray() {
                    return ['id' => 1, 'name' => 'Test'];
                }
            };

            $result = $this->controller->testFormatEntity($entity);

            expect($result)->toBe(['id' => 1, 'name' => 'Test']);
        });
    });

    describe('Internationalisation', function (): void {
        it('Doit traduire une chaîne', function (): void {
            $result = $this->controller->testLang('Rest.notImplemented', ['method']);

            expect($result)->toBeA('string');
        });

        it('Doit traduire avec préfixe REST', function (): void {
            $result = $this->controller->testTranslate('notImplemented', ['method']);

            expect($result)->toBeA('string');
        });
    });

    describe('Hooks', function (): void {
        it('Doit exécuter le hook before', function (): void {
            $result = $this->controller->testBefore('testMethod', []);

            expect($result)->toBeNull();
        });

        it('Doit exécuter le hook after', function (): void {
            $response = new Response(['status' => 201]);

            $this->controller->testAfter('testMethod', [], $response);

            // Juste vérifier qu'il n'y a pas d'exception
            expect(true)->toBeTruthy();
        });
    });
});
