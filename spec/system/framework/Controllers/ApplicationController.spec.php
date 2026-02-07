<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Controllers\ApplicationController;
use BlitzPHP\Exceptions\ViewException;
use BlitzPHP\Utilities\Reflection\ReflectionClass;
use BlitzPHP\View\View;
use Psr\Http\Message\ResponseInterface;

use function Kahlan\expect;

describe('Controllers / ApplicationController', function (): void {
    beforeEach(function (): void {
		// Création de la classe de test
        $this->controller = new class extends ApplicationController {
            // Méthodes d'accès pour tester les méthodes protected
            public function testView(string $view, array $data = [], array $options = []): View {
                return $this->view($view, $data, $options);
            }

            public function testRender(array|string $view = '', ?array $data = [], ?array $options = []): ResponseInterface {
                return $this->render($view, $data, $options);
            }

            public function testAddData(array|string $key, $value = null): self {
                return $this->addData($key, $value);
            }
        };
		$this->controller->initialize(service('request'), service('response'), service('logger'));
    });

    describe('Méthode view()', function (): void {
        it('Doit charger une vue avec chemin relatif', function (): void {
            $result = $this->controller->testView('test_view', ['key' => 'value']);

            expect($result)->toBeAnInstanceOf(View::class);
        });

        it('Doit ajouter les données partagées', function (): void {
            $this->controller->testAddData('shared', 'shared_value');
            $this->controller->testView('test_view', ['specific' => 'specific_value']);

			$viewer = service('viewer');
			$data = $viewer->getData();

            // Vérifie que le viewer a reçu les données combinées
            expect($data)->toContainKey('shared');
            expect($data['shared'])->toBe('shared_value');
        });

        it('Doit définir un titre par défaut si non fourni', function (): void {
            $this->controller->testView('test_view');

			$viewer = service('viewer');
			$data = $viewer->getData();

			// Vérifie que le viewer a reçu les données combinées
            expect($data)->toContainKey('title');
            expect($data['title'])->toMatch('/ - index$/');
        });

        it('Doit utiliser le titre fourni dans les données', function (): void {
            $this->controller->testView('test_view', ['title' => 'Custom Title']);

			$viewer = service('viewer');
			$data = $viewer->getData();

            expect($data['title'])->toBe('Custom Title');
        });

        it('Doit appliquer un layout si défini', function (): void {
			$controller = new class extends ApplicationController {
                protected string $layout = 'default';

                public function testView(string $view, array $data = [], array $options = []): View {
                    return $this->view($view, $data, $options);
                }
            };

            $result = $controller->testView('test_view');

			$viewer = service('viewer');
			$reflection = new ReflectionClass($viewer->getAdapter());
			$layout = $reflection->getValue('layout');

            expect($layout)->toBe('default');
			$reflection->setValue('layout', null);
        });
    });

    describe('Méthode render()', function (): void {
        it('Doit rendre une vue avec nom spécifié', function (): void {
           try {
				$this->controller->testRender('test_view', ['key' => 'value']);
			} catch (ViewException $e) {
				// en temps normal, on va chercher à charger le fichier "spec\\system\\framework\\Views\\test_view"
				// or ce fichier n'existe pas bien evidement.
				// l'important pour nous à ce niveau est que le dossier et le nom du fichier soient bien déterminés
				expect($e->getMessage())
					->toMatch(fn($actual) => str_ends_with($actual, 'Views\test_view.php'));
			}
        });

        it('Doit déduire le nom de la vue depuis la méthode appelante', function (): void {
            try {
				$this->controller->testRender();
			} catch (ViewException $e) {
				// en temps normal, on va chercher à charger le fichier "spec\\system\\framework\\Views\\test_view"
				// or ce fichier n'existe pas bien evidement.
				// l'important pour nous à ce niveau est que le dossier et le nom du fichier soient bien déterminés
				expect($e->getMessage())
					->toMatch(fn($actual) => str_ends_with($actual, 'Views\\testRender.php'));
			}
        });

        it('Doit gérer le tableau en premier paramètre', function (): void {
            try {
				$this->controller->testRender(['key' => 'value']);
			} catch (ViewException $e) {
				// en temps normal, on va chercher à charger le fichier "spec\\system\\framework\\Views\\test_view"
				// or ce fichier n'existe pas bien evidement.
				// l'important pour nous à ce niveau est que le dossier et le nom du fichier soient bien déterminés
				expect($e->getMessage())
					->toMatch(fn($actual) => str_ends_with($actual, 'Views\\testRender.php'));
			}
        });
    });

    describe('Méthode addData()', function (): void {
        it('Doit ajouter une seule paire clé-valeur', function (): void {
            $result = $this->controller->testAddData('key', 'value');

            expect($result)->toBe($this->controller);

            // Tester que les données sont bien ajoutées aux données partagées
            $reflection = new ReflectionClass($this->controller);

            expect($reflection->getValue('viewDatas'))->toContainKey('key');
            expect($reflection->getValue('viewDatas')['key'])->toBe('value');
        });

        it('Doit ajouter un tableau de données', function (): void {
            $this->controller->testAddData(['key1' => 'value1', 'key2' => 'value2']);

            $reflection = new ReflectionClass($this->controller);
            $data = $reflection->getValue('viewDatas');

            expect($data)->toContainKey('key1');
            expect($data)->toContainKey('key2');
            expect($data['key1'])->toBe('value1');
            expect($data['key2'])->toBe('value2');
        });

        it('Doit fusionner les données existantes', function (): void {
            $this->controller->testAddData(['key1' => 'value1']);
            $this->controller->testAddData(['key2' => 'value2']);

            $reflection = new ReflectionClass($this->controller);
            $data = $reflection->getValue('viewDatas');

            expect($data)->toContainKey('key1');
            expect($data)->toContainKey('key2');
        });
    });
});
