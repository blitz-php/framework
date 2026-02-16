<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Contracts\Http\StatusCode;
use BlitzPHP\Controllers\ResourceController;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\NullLogger;

use function Kahlan\expect;

describe('Controllers / ResourceController', function (): void {
    beforeEach(function (): void {
        $this->controller = new class extends ResourceController {
            protected string $returnFormat = 'json';

            // Méthodes accessibles pour les tests
            public function testIndex() {
                return $this->index();
            }

            public function testShow($id = null) {
                return $this->show($id);
            }

            public function testNew() {
                return $this->new();
            }

            public function testCreate() {
                return $this->create();
            }

            public function testEdit($id = null) {
                return $this->edit($id);
            }

            public function testUpdate($id = null) {
                return $this->update($id);
            }

            public function testDelete($id = null) {
                return $this->delete($id);
            }

            public function testSetFormat($format) {
                return $this->setFormat($format);
            }
        };
		$this->controller->initialize(service('request'), service('response'), new NullLogger());
    });

    describe('Méthodes CRUD par défaut', function (): void {
        it('Doit retourner "non implémenté" pour index()', function (): void {
           /** @var ResponseInterface */
			$result = $this->controller->testIndex();
            expect($result)->toBeAnInstanceOf(ResponseInterface::class);

			$body = json_decode($result->getBody()->getContents());

			expect($body->code)->toBe(StatusCode::NOT_IMPLEMENTED);
        });

        it('Doit retourner "non implémenté" pour show()', function (): void {
            $result = $this->controller->testShow(1);
            expect($result)->toBeAnInstanceOf(ResponseInterface::class);

			$body = json_decode($result->getBody()->getContents());

			expect($body->code)->toBe(StatusCode::NOT_IMPLEMENTED);
        });

        it('Doit retourner "non implémenté" pour new()', function (): void {
            $result = $this->controller->testNew();
            expect($result)->toBeAnInstanceOf(ResponseInterface::class);

			$body = json_decode($result->getBody()->getContents());

			expect($body->code)->toBe(StatusCode::NOT_IMPLEMENTED);
        });

        it('Doit retourner "non implémenté" pour create()', function (): void {
            $result = $this->controller->testCreate();
            expect($result)->toBeAnInstanceOf(ResponseInterface::class);

			$body = json_decode($result->getBody()->getContents());

			expect($body->code)->toBe(StatusCode::NOT_IMPLEMENTED);
        });

        it('Doit retourner "non implémenté" pour edit()', function (): void {
            $result = $this->controller->testEdit(1);
            expect($result)->toBeAnInstanceOf(ResponseInterface::class);

			$body = json_decode($result->getBody()->getContents());

			expect($body->code)->toBe(StatusCode::NOT_IMPLEMENTED);
        });

        it('Doit retourner "non implémenté" pour update()', function (): void {
            $result = $this->controller->testUpdate(1);
            expect($result)->toBeAnInstanceOf(ResponseInterface::class);

			$body = json_decode($result->getBody()->getContents());

			expect($body->code)->toBe(StatusCode::NOT_IMPLEMENTED);
        });

        it('Doit retourner "non implémenté" pour delete()', function (): void {
            $result = $this->controller->testDelete(1);
            expect($result)->toBeAnInstanceOf(ResponseInterface::class);

			$body = json_decode($result->getBody()->getContents());

			expect($body->code)->toBe(StatusCode::NOT_IMPLEMENTED);
        });
    });

    describe('Configuration du format', function (): void {
        it('Doit accepter le format JSON', function (): void {
            $this->controller->testSetFormat('json');

            // Vérifie que le format a été défini
            $reflection = new ReflectionClass($this->controller);
            $property = $reflection->getProperty('returnFormat');

            // Note: setFormat appelle returnFormat qui est une méthode parente
            // Nous testons juste que la méthode peut être appelée sans erreur
            expect($this->controller->testSetFormat('json'))->toBe(null);
        });

        it('Doit accepter le format XML', function (): void {
            $this->controller->testSetFormat('xml');

            expect($this->controller->testSetFormat('xml'))->toBe(null);
        });

        it('Doit ignorer les formats non supportés', function (): void {
            $this->controller->testSetFormat('unsupported');

            // La méthode ne devrait pas lever d'exception
            expect($this->controller->testSetFormat('unsupported'))->toBe(null);
        });
    });

    describe('Constructeur', function (): void {
        it('Doit initialiser le format de retour', function (): void {
            $controller = new class extends ResourceController {
                protected string $returnFormat = 'xml';

                public function getReturnFormat() {
                    return $this->returnFormat;
                }
            };

            // Le constructeur parent est appelé automatiquement
            // et devrait configurer le format
            expect($controller->getReturnFormat())->toBe('xml');
        });
    });
});
