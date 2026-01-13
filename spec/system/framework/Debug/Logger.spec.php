<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Debug\Logger;
use BlitzPHP\Utilities\Reflection\ReflectionClass;
use Monolog\Handler\AbstractProcessingHandler;
use Psr\Log\LogLevel;

use function Kahlan\expect;

describe('Debug / Logger', function (): void {
	beforeAll(function (): void {
		$this->logConfig = config('log');
	});

    beforeEach(function (): void {
        // Configuration par défaut pour les tests
		config()->set('log', [
            'name' => 'Test Application',
            'date_format' => 'Y-m-d H:i:s',
            'handlers' => [
                'file' => [
                    'active' => true,
                    'path' => '/tmp/logs',
                    'extension' => '.log',
                    'dayly_rotation' => true,
                    'max_files' => 5,
                    'level' => LogLevel::DEBUG,
                    'permissions' => 0644,
                    'format' => 'json',
                ],
            ],
            'processors' => ['web', 'psr'],
        ]);

        allow('BLITZ_DEBUG')->toBe(true);
    });

    afterEach(function (): void {
		config()->set('log', $this->logConfig);
    });

    it('initialise correctement avec la configuration', function (): void {
        expect(function (): void {
            $logger = new Logger();
            expect($logger)->toBeAnInstanceOf(Logger::class);
        })->not->toThrow();
    });

    it('utilise le nom d\'application par défaut si non spécifié', function (): void {
        config()->set('log', [
            'handlers' => [],
            'processors' => [],
        ]);

        $logger = new Logger();

        // Vérifier via réflexion
        $reflection = new ReflectionClass($logger);
        $monolog = $reflection->getValue('monolog');

        expect($monolog->getName())->toBe('application');
    });

    it('remplace les espaces par des tirets dans le nom', function (): void {
        config()->set('log', [
            'name' => 'My Test App',
            'handlers' => [],
            'processors' => [],
        ]);

        $logger = new Logger();

        $reflection = new ReflectionClass($logger);
        $monolog = $reflection->getValue('monolog');

        expect($monolog->getName())->toBe('my-test-app');
    });

    it('ignore les handlers inactifs', function (): void {
        config()->set('log', [
            'handlers' => [
                'file' => [
                    'active' => false,
                    'path' => '/tmp/logs',
                ],
                'error' => [
                    'active' => true,
                    'type' => 0,
                    'level' => LogLevel::DEBUG,
                    'format' => 'json',
                ],
            ],
            'processors' => [],
        ]);

        $logger = new Logger();

        // Le handler 'file' devrait être ignoré car actif=false
        // On ne peut pas vérifier directement, mais on peut vérifier
        // que la construction ne lance pas d'exception
        expect($logger)->toBeAnInstanceOf(Logger::class);
    });

    it('journalise avec différents niveaux', function (): void {
        $logger = new Logger();

        // Tester chaque niveau de log
        $levels = [
            'emergency',
            'alert',
            'critical',
            'error',
            'warning',
            'notice',
            'info',
            'debug',
        ];

        foreach ($levels as $level) {
            expect(function () use ($logger, $level): void {
                $logger->{$level}("Test {$level} message");
            })->not->toThrow();
        }
    });

    it('met en cache les logs en mode debug', function (): void {
        $logger = new Logger(true);

        $logger->info('Test message');
        $logger->error('Error message');

        expect($logger->logCache)->toHaveLength(2);
        expect($logger->logCache[0]['level'])->toBe(LogLevel::INFO);
        expect($logger->logCache[0]['msg'])->toBe('Test message');
        expect($logger->logCache[1]['level'])->toBe(LogLevel::ERROR);
        expect($logger->logCache[1]['msg'])->toBe('Error message');
    });

    it('ne met pas en cache les logs en mode production', function (): void {
        $logger = new Logger(false);

        $logger->info('Test message');

        expect($logger->logCache)->toBeEmpty();
    });

    it('pousse les processeurs correctement', function (): void {
        config()->set('log', [
            'handlers' => [],
            'processors' => ['web', 'introspection', 'hostname', 'process_id', 'uid', 'memory_usage', 'psr'],
        ]);

        $logger = new Logger();

        // La construction ne doit pas lancer d'exception
        expect($logger)->toBeAnInstanceOf(Logger::class);
    });

    it('lance une exception pour un processeur invalide', function (): void {
        config()->set('log', [
            'handlers' => [],
            'processors' => ['invalid_processor'],
        ]);

        expect(function (): void {
            new Logger();
        })->toThrow(new InvalidArgumentException());
    });

    describe('Handlers', function (): void {
        it('configure le handler de fichier avec rotation quotidienne', function (): void {
            $logger = new Logger();

            // Utiliser la réflexion pour tester la méthode pushFileHandler
            $reflection = new ReflectionClass($logger);

            $options = (object) [
                'path' => '/custom/logs',
                'extension' => '.txt',
                'dayly_rotation' => true,
                'max_files' => 10,
                'level' => LogLevel::WARNING,
                'permissions' => 0755,
                'format' => 'line',
            ];

            expect(function () use ($reflection, $options): void {
                $reflection->invoke('pushFileHandler', $options);
            })->not->toThrow();
        });

        it('configure le handler de fichier sans rotation', function (): void {
            $logger = new Logger();

            $reflection = new ReflectionClass($logger);

            $options = (object) [
                'path' => '/custom/logs',
                'dayly_rotation' => false,
                'level' => LogLevel::DEBUG,
                'format' => 'json',
            ];

            expect(function () use ($reflection, $options): void {
                $reflection->invoke('pushFileHandler', $options);
            })->not->toThrow();
        });

        it('configure le handler error_log', function (): void {
            $logger = new Logger();

            $reflection = new ReflectionClass($logger);

            $options = (object) [
                'type' => 0,
                'level' => LogLevel::ERROR,
                'format' => 'json',
            ];

            expect(function () use ($reflection, $options): void {
                $reflection->invoke('pushErrorHandler', $options);
            })->not->toThrow();
        });

        it('configure le handler email', function (): void {
            $logger = new Logger();

            $reflection = new ReflectionClass($logger);

            $options = (object) [
                'to' => 'admin@example.com',
                'subject' => 'Error Report',
                'from' => 'system@example.com',
                'level' => LogLevel::CRITICAL,
                'format' => 'html',
            ];

            expect(function () use ($reflection, $options): void {
                $reflection->invoke('pushEmailHandler', $options);
            })->not->toThrow();
        });

        it('configure le handler Telegram', function (): void {
            $logger = new Logger();

            $reflection = new ReflectionClass($logger);

            $options = (object) [
                'api_key' => 'test_api_key',
                'channel' => '@test_channel',
                'level' => LogLevel::INFO,
                'format' => 'json',
            ];

            expect(function () use ($reflection, $options): void {
                $reflection->invoke('pushTelegramHandler', $options);
            })->not->toThrow();
        });

        it('configure le handler Chrome', function (): void {
            $logger = new Logger();

            $reflection = new ReflectionClass($logger);

            $options = (object) [
                'level' => LogLevel::DEBUG,
                'format' => 'json',
            ];

            expect(function () use ($reflection, $options): void {
                $reflection->invoke('pushChromeHandler', $options);
            })->not->toThrow();
        });

        it('configure le handler Firebug', function (): void {
            $logger = new Logger();

            $reflection = new ReflectionClass($logger);

            $options = (object) [
                'format' => 'json',
            ];

            expect(function () use ($reflection, $options): void {
                $reflection->invoke('pushFirebugHandler', $options);
            })->not->toThrow();
        });

        it('configure le handler Browser', function (): void {
            $logger = new Logger();

            $reflection = new ReflectionClass($logger);

            $options = (object) [
                'format' => 'json',
            ];

            expect(function () use ($reflection, $options): void {
                $reflection->invoke('pushBrowserHandler', $options);
            })->not->toThrow();
        });
    });

    describe('Formatters', function (): void {
        it('applique le formateur JSON', function (): void {
            $logger = new Logger();

            $reflection = new ReflectionClass($logger);

			$handler = Mockery::mock(AbstractProcessingHandler::class);
            $handler->shouldReceive('setFormatter')->once();

            $result = $reflection->invoke('setFormatter', $handler, ['json', 'line'], 'json');

            expect($result)->toBe($handler);
        });

        it('applique le formateur Line', function (): void {
            $logger = new Logger();

            $reflection = new ReflectionClass($logger);

            $handler = Mockery::mock(AbstractProcessingHandler::class);
            $handler->shouldReceive('setFormatter')->once();

            $result = $reflection->invoke('setFormatter', $handler, ['json', 'line'], 'line');

            expect($result)->toBe($handler);
        });

        it('applique le formateur HTML', function (): void {
            $logger = new Logger();

            $reflection = new ReflectionClass($logger);

            $handler = Mockery::mock(AbstractProcessingHandler::class);
            $handler->shouldReceive('setFormatter')->once();

            $result = $reflection->invoke('setFormatter', $handler, ['html', 'json'], 'html');

            expect($result)->toBe($handler);
        });

        it('utilise JSON par défaut si format est null', function (): void {
            $logger = new Logger();

            $reflection = new ReflectionClass($logger);

			$handler = Mockery::mock(AbstractProcessingHandler::class);
            $handler->shouldReceive('setFormatter')->once();

            $result = $reflection->invoke('setFormatter', $handler, ['json', 'line'], null);

            expect($result)->toBe($handler);
        });

        it('lance une exception pour un format non autorisé', function (): void {
            $logger = new Logger();

            $reflection = new ReflectionClass($logger);

			$handler = Mockery::mock(AbstractProcessingHandler::class);

            expect(function () use ($reflection, $handler): void {
                $reflection->invoke('setFormatter', $handler, ['json', 'line'], 'html');
            })->toThrow(new InvalidArgumentException());
        });

        it('retourne le handler tel quel si setFormatter n\'existe pas', function (): void {
            $logger = new Logger();

            $reflection = new ReflectionClass($logger);

            $handler = new stdClass();

            $result = $reflection->invoke('setFormatter', $handler, ['json', 'line'], 'json');

            expect($result)->toBe($handler);
        });
    });

    it('gère le log avec contexte', function (): void {
        $logger = new Logger();

        $context = [
            'user_id' => 123,
            'action' => 'login',
            'ip' => '192.168.1.1',
        ];

        expect(function () use ($logger, $context): void {
            $logger->info('User action', $context);
        })->not->toThrow();
    });

    it('utilise LoggerTrait pour les méthodes de log', function (): void {
        $logger = new Logger();

        // Vérifier que les méthodes de la trait sont disponibles
        expect(method_exists($logger, 'emergency'))->toBeTruthy();
        expect(method_exists($logger, 'alert'))->toBeTruthy();
        expect(method_exists($logger, 'critical'))->toBeTruthy();
        expect(method_exists($logger, 'error'))->toBeTruthy();
        expect(method_exists($logger, 'warning'))->toBeTruthy();
        expect(method_exists($logger, 'notice'))->toBeTruthy();
        expect(method_exists($logger, 'info'))->toBeTruthy();
        expect(method_exists($logger, 'debug'))->toBeTruthy();
        expect(method_exists($logger, 'log'))->toBeTruthy();
    });
});
