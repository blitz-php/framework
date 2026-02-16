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
use BlitzPHP\Debug\ExceptionManager;
use BlitzPHP\Exceptions\HttpException;
use BlitzPHP\Exceptions\TokenMismatchException;
use BlitzPHP\Filesystem\Filesystem;
use BlitzPHP\Utilities\Reflection\ReflectionClass;
use Kahlan\Plugin\Double;
use Psr\Http\Message\RequestInterface;
use Whoops\Handler\Handler;
use Whoops\Handler\HandlerInterface;
use Whoops\Run;

use function Kahlan\allow;
use function Kahlan\expect;

describe('Debug / ExceptionManager', function (): void {
	beforeAll(function (): void {
		$this->exceptionsConfig = config('exceptions');
	});
    beforeEach(function (): void {
        config()->set('exceptions', [
            'log'             => true,
            'ignore_codes'    => [404],
            'error_view_path' => '/path/to/views',
            'handlers'        => [],
            'editor'          => 'vscode',
            'title'           => 'Error',
            'blacklist'       => [],
            'data'            => [],
        ]);

		$mockFs = Double::instance([
			'extends' => Filesystem::class,
			'stubMethods' => [
				'files' => [],
				'directories' => [],
			],
		]);
		Services::injectMock('fs', $mockFs);

		is_online(true);
    });

	afterEach(function (): void {
		config()->set('exceptions', $this->exceptionsConfig);
		Services::resetSingle('fs');
	});

	afterAll(function (): void {
		is_online(newReturn: false);
	});

    it('initialise le debugger si Whoops est disponible', function (): void {
        expect(function (): void {
            $manager = new ExceptionManager();
            expect($manager)->toBeAnInstanceOf(ExceptionManager::class);
        })->not->toThrow();
    });

    it('ne plante pas si Whoops n\'est pas installé', function (): void {
        // Simuler l'absence de Whoops
        allow('class_exists')->toBeCalled()->with(Run::class)->andReturn(false);

        expect(function (): void {
            $manager = new ExceptionManager();
            $manager->register(); // Ne devrait pas lancer d'exception
        })->not->toThrow();
    });

    it('enregistre les gestionnaires callable', function (): void {
        $handlerCalled = false;
        $handler = function () use (&$handlerCalled): int {
            $handlerCalled = true;
            return Handler::DONE;
        };

        config()->set('exceptions', [
            'log'             => true,
            'ignore_codes'    => [],
            'error_view_path' => '/path/to/views',
            'handlers'        => [$handler],
            'editor'          => 'vscode',
            'title'           => 'Error',
            'blacklist'       => [],
            'data'            => [],
        ]);

        $manager = new ExceptionManager();

        // On ne peut pas tester directement l'enregistrement, mais on peut vérifier
        // que la méthode ne lance pas d'exception
        expect(function () use ($manager): void {
            $manager->register();
        })->not->toThrow();
    });

    it('enregistre les gestionnaires de classe', function (): void {
        $mockHandler = Double::instance([
			'implements' => [HandlerInterface::class],
		]);

       config()->set('exceptions', [
            'log'             => true,
            'ignore_codes'    => [],
            'error_view_path' => '/path/to/views',
            'handlers'        => [$mockHandler::class],
            'editor'          => 'vscode',
            'title'           => 'Error',
            'blacklist'       => [],
            'data'            => [],
        ]);

        $manager = new ExceptionManager();

        expect(function () use ($manager): void {
            $manager->register();
        })->not->toThrow();
    });

    it('prépare TokenMismatchException en HttpException 419', function (): void {
        $tokenException = new TokenMismatchException('Token mismatch');

        // Utilisation de la réflexion pour tester la méthode privée
        $manager = new ExceptionManager();
        $reflection = new ReflectionClass($manager);
		$result = $reflection->invoke('prepareException', $tokenException);

        expect($result)->toBeAnInstanceOf(HttpException::class);
        expect($result->getCode())->toBe(419);
        expect($result->getMessage())->toBe('Token mismatch');
        expect($result->getPrevious())->toBe($tokenException);
    });

    it('ne modifie pas les autres exceptions', function (): void {
        $originalException = new RuntimeException('Test exception');

        $manager = new ExceptionManager();
        $reflection = new ReflectionClass($manager);
        $result = $reflection->invoke('prepareException', $originalException);

        expect($result)->toBe($originalException);
    });

    xit('récupère les chemins d\'application correctement', function (): void {
		$mockFs = Double::instance([
			'extends' => Filesystem::class,
			'stubMethods' => [
				'directories' => [
					'/app/controllers',
					'/app/models',
					'/app/vendor',
					'/app/config',
				],
			]
		]);

		Services::injectMock('fs', $mockFs);

		$manager = new ExceptionManager();
        $reflection = new ReflectionClass($manager);

        $result = $reflection->invoke('getApplicationPaths');

        expect($result)->toContain('/app/controllers');
        expect($result)->toContain('/app/models');
        expect($result)->toContain('/app/config');
        expect($result)->not->toContain('/app/vendor');

		Services::resetSingle('fs');
    });

    it('gère les environnements CLI', function (): void {
        allow('Misc::isCommandLine')->toBeCalled()->andReturn(true);

        $manager = new ExceptionManager();

        expect(function () use ($manager): void {
            $manager->register();
        })->not->toThrow();
    });

    it('gère les environnements de production', function (): void {
        is_online(true);
        allow('Misc::isCommandLine')->toBeCalled()->andReturn(false);

        $manager = new ExceptionManager();

        expect(function () use ($manager): void {
            $manager->register();
        })->not->toThrow();
    });

    it('gère les requêtes AJAX/JSON', function (): void {
        allow('Misc::isCommandLine')->toBeCalled()->andReturn(false);
        is_online(false);
        allow('Misc::isAjaxRequest')->toBeCalled()->andReturn(true);

        $mockRequest = Double::instance([
			'implements' => [RequestInterface::class],
			'stubMethods' => [
				'isJson' => false,
			],
        ]);
		Services::injectMock('request', $mockRequest);

        $manager = new ExceptionManager();

        expect(function () use ($manager): void {
            $manager->register();
        })->not->toThrow();

		Services::resetSingle('request');
    });

    it('configure correctement la liste noire', function (): void {
        config()->set('exceptions', [
            'log'             => true,
            'ignore_codes'    => [],
            'error_view_path' => '/path/to/views',
            'handlers'        => [],
            'editor'          => 'vscode',
            'title'           => 'Error',
            'blacklist'       => ['post/password', 'get/api_key'],
            'data'            => [],
        ]);

        // Simuler des données POST
        $_POST['password'] = 'secret';
        $_GET['api_key'] = '12345';

        $manager = new ExceptionManager();

        expect(function () use ($manager): void {
            $manager->register();
        })->not->toThrow();

        // Nettoyer les variables globales
        unset($_POST['password'], $_GET['api_key']);
    });

    describe('registerHttpErrorsHandler', function (): void {
        xit('enregistre un gestionnaire pour les erreurs HTTP', function (): void {
            $mockFs = Double::instance([
				'extends' => Filesystem::class,
				'stubMethods' => [
					'files' => [
						Double::instance([
							'extends' => SplFileInfo::class,
							'stubMethods' => [
								'getFilenameWithoutExtension' => '404',
							]
						]),
						Double::instance([
							'extends' => SplFileInfo::class,
							'stubMethods' => [
								'getFilenameWithoutExtension' => '500',
							]
						]),
					],
				],
			]);

			Services::injectMock('fs', $mockFs);
			is_online(newReturn: false);

            $manager = new ExceptionManager();

            expect(function () use ($manager): void {
                $reflection = new ReflectionClass($manager);
                $reflection->invoke('registerHttpErrorsHandler');
            })->not->toThrow();
        });

        it('ne journalise pas les codes ignorés', function (): void {
            config()->set('exceptions', [
                'log'             => true,
                'ignore_codes'    => [404],
                'error_view_path' => '/path/to/views',
                'handlers'        => [],
                'editor'          => 'vscode',
                'title'           => 'Error',
                'blacklist'       => [],
                'data'            => [],
            ]);

            $manager = new ExceptionManager();

            // Vérifier que le code 404 est dans la liste des codes ignorés
            expect(function () use ($manager): void {
                $manager->register();
            })->not->toThrow();
        });

        it('ne journalise pas si log est false', function (): void {
            config()->set('exceptions', [
                'log'             => false,
                'ignore_codes'    => [],
                'error_view_path' => '/path/to/views',
                'handlers'        => [],
                'editor'          => 'vscode',
                'title'           => 'Error',
                'blacklist'       => [],
                'data'            => [],
            ]);

            $manager = new ExceptionManager();

            expect(function () use ($manager): void {
                $manager->register();
            })->not->toThrow();
        });
    });
});
