<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was was distributed with this source code.
 */
use BlitzPHP\Utilities\Iterable\Collection;
use BlitzPHP\Traits\Mixins\HigherOrderTapProxy;
use BlitzPHP\Utilities\Helpers;

use function Kahlan\expect;

describe('Utilities / Helpers', function (): void {
    describe('Manipulation de classes', function (): void {
        it('Doit récupérer le basename d\'une classe', function (): void {
            expect(Helpers::classBasename('App\\Controllers\\UserController'))->toBe('UserController');
            expect(Helpers::classBasename(new stdClass()))->toBe('stdClass');
        });

        it('Doit récupérer les traits récursivement', function (): void {
            trait TraitA {}
            trait TraitB { use TraitA; }
            class TestClass { use TraitB; }

            $traits = Helpers::classUsesRecursive(TestClass::class);

            expect($traits)->toContain(TraitA::class);
            expect($traits)->toContain(TraitB::class);
        });

        it('Doit séparer le namespace', function (): void {
            expect(Helpers::namespaceSplit('App\\Controllers\\UserController'))
                ->toBe(['App\\Controllers', 'UserController']);

            expect(Helpers::namespaceSplit('NoNamespaceClass'))
                ->toBe(['', 'NoNamespaceClass']);
        });
    });

    describe('Manipulation de données', function (): void {
        it('Doit récupérer des données avec dataGet()', function (): void {
            $data = ['user' => ['name' => 'John', 'age' => 30]];

            expect(Helpers::dataGet($data, 'user.name'))->toBe('John');
            expect(Helpers::dataGet($data, 'user.nonexistent', 'default'))->toBe('default');
            expect(Helpers::dataGet($data, null))->toBe($data);
        });

        it('Doit définir des données avec dataSet()', function (): void {
            $data = [];
            Helpers::dataSet($data, 'user.name', 'John');
            Helpers::dataSet($data, 'user.age', 30);

            expect($data)->toBe(['user' => ['name' => 'John', 'age' => 30]]);
        });

        it('Doit vérifier l\'existence avec dataHas()', function (): void {
            $data = ['user' => ['name' => 'John']];

            expect(Helpers::dataHas($data, 'user.name'))->toBe(true);
            expect(Helpers::dataHas($data, 'user.age'))->toBe(false);
        });

        it('Doit remplir des données avec dataFill()', function (): void {
            $data = ['user' => ['name' => 'John']];
            Helpers::dataFill($data, 'user.age', 30);

            expect($data['user']['age'])->toBe(30);
        });

        it('Doit supprimer des données avec dataForget()', function (): void {
            $data = ['user' => ['name' => 'John', 'age' => 30]];
            Helpers::dataForget($data, 'user.age');

            expect($data)->toBe(['user' => ['name' => 'John']]);
        });
    });

    describe('URLs et chemins', function (): void {
        it('Doit nettoyer une URL', function (): void {
            $url = 'http://example.com/path/../to/./resource';
            $cleaned = Helpers::cleanUrl($url);

            expect($cleaned)->toBe('http://example.com/to/resource');
        });

        it('Doit vérifier si un chemin est absolu', function (): void {
            expect(Helpers::isAbsolutePath('/absolute/path'))->toBe(true);
            expect(Helpers::isAbsolutePath('relative/path'))->toBe(false);
            // expect(Helpers::isAbsolutePath('C:\\Windows\\Path'))->toBe(true);
        });

        it('Doit vérifier si une URL est absolue', function (): void {
            expect(Helpers::isAbsoluteUrl('http://example.com'))->toBe(true);
            expect(Helpers::isAbsoluteUrl('https://example.com'))->toBe(true);
            expect(Helpers::isAbsoluteUrl('//cdn.example.com'))->toBe(true);
            // expect(Helpers::isAbsoluteUrl('/relative/path'))->toBe(false);
            // expect(Helpers::isAbsoluteUrl('relative/path'))->toBe(false);
        });

        it('Doit trouver l\'URL de base', function (): void {
            $_SERVER['HTTP_HOST'] = 'example.com';
            $_SERVER['SCRIPT_NAME'] = '/index.php';
            $_SERVER['SCRIPT_FILENAME'] = '/var/www/index.php';
            $_SERVER['SERVER_ADDR'] = '127.0.0.1';

            $baseUrl = Helpers::findBaseUrl();

            expect($baseUrl)->toBe('http://example.com');
        });
    });

    describe('Validation et vérification', function (): void {
        it('Doit vérifier si en ligne', function (): void {
            $_SERVER['HTTP_HOST'] = 'localhost';
            expect(Helpers::isOnline())->toBe(false);

            $_SERVER['HTTP_HOST'] = 'example.com';
            expect(Helpers::isOnline())->toBe(true);
        });

        it('Doit vérifier si la requête est AJAX', function (): void {
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
            expect(Helpers::isAjaxRequest())->toBe(true);

            unset($_SERVER['HTTP_X_REQUESTED_WITH']);
            expect(Helpers::isAjaxRequest())->toBe(false);
        });

        it('Doit vérifier si en CLI', function (): void {
            // Ce test dépend de l'environnement d'exécution
            expect(Helpers::isCli())->toBe(true); // Kahlan s'exécute en CLI
        });

        it('Doit vérifier la version PHP', function (): void {
            expect(Helpers::isPhp('7.4.0'))->toBe(true); // Supposant PHP 7.4+
            expect(Helpers::isPhp('10.0.0'))->toBe(false);
        });

        it('Doit vérifier l\'encodage Base64', function (): void {
            $encoded = base64_encode('test string');
            expect(Helpers::isBase64Encoded($encoded))->toBe(true);
            expect(Helpers::isBase64Encoded('not base64!@#'))->toBe(false);
        });
    });

    if (class_exists('\Laminas\Escaper\Escaper')) {
		describe('Sécurité et échappement', function (): void {
			it('Doit échapper du HTML', function (): void {
				$html = '<script>alert("xss")</script>';
				$escaped = Helpers::esc($html, 'html');

				expect($escaped)->not->toContain('<script>');
				expect($escaped)->toContain('&lt;script&gt;');
			});

			it('Doit échapper du JavaScript', function (): void {
				$js = "alert('test');";
				$escaped = Helpers::esc($js, 'js');

				expect($escaped)->toBeA('string');
			});

			it('Doit échapper des attributs', function (): void {
				$attr = '" onclick="alert(\'xss\')';
				$escaped = Helpers::esc($attr, 'attr');

				expect($escaped)->not->toContain('"');
			});

			it('Doit lever une exception pour un contexte invalide', function (): void {
				expect(function (): void {
					Helpers::esc('test', 'invalid');
				})->toThrow(new InvalidArgumentException('Invalid escape context provided.'));
			});

			it('Doit purifier du HTML', function (): void {
				skipIf(! class_exists('HTMLPurifier'));

				$dirty = '<script>alert("xss")</script><p>Safe text</p>';
				$clean = Helpers::purify($dirty);

				expect($clean)->not->toContain('<script>');
				expect($clean)->toContain('<p>Safe text</p>');
			});
		});
	}

    describe('Utilitaires divers', function (): void {
        it('Doit créer une collection', function (): void {
            $collection = Helpers::collect([1, 2, 3]);

            expect($collection)->toBeAnInstanceOf(Collection::class);
            expect($collection->toArray())->toBe([1, 2, 3]);
        });

        it('Doit récupérer la première valeur d\'un tableau', function (): void {
            expect(Helpers::head([1, 2, 3]))->toBe(1);
            expect(Helpers::head([]))->toBe(false);
        });

        it('Doit récupérer la dernière valeur d\'un tableau', function (): void {
            expect(Helpers::last([1, 2, 3]))->toBe(3);
            expect(Helpers::last([]))->toBe(false);
        });

        it('Doit obtenir une variable d\'environnement', function (): void {
            $_SERVER['TEST_ENV'] = 'test_value';

            expect(Helpers::env('TEST_ENV'))->toBe('test_value');
            expect(Helpers::env('NONEXISTENT', 'default'))->toBe('default');

            unset($_SERVER['TEST_ENV']);
        });

        it('Doit obtenir l\'adresse IP', function (): void {
            $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
            $ip = Helpers::ipAddress();

            expect($ip)->toBe('192.168.1.1');
        });

        it('Doit vérifier la connexion internet', function (): void {
            // Ce test peut échouer si pas de connexion
            $connected = Helpers::isConnected(['8.8.8.8:53'], 1);
            expect($connected)->toBeA('boolean');
        });
    });

    describe('Exécution et retry', function (): void {
        it('Doit exécuter une fonction avec retry', function (): void {
            $attempts = 0;
            $result = Helpers::retry(3, function () use (&$attempts): string {
                $attempts++;
                if ($attempts < 3) {
                    throw new Exception('Temporary failure');
                }
                return 'success';
            });

            expect($result)->toBe('success');
            expect($attempts)->toBe(3);
        });

        it('Doit propager l\'exception après tous les retry', function (): void {
            expect(function (): void {
                Helpers::retry(2, function (): void {
                    throw new Exception('Always fails');
                });
            })->toThrow(new Exception('Always fails'));
        });

        it('Doit exécuter tap() avec callback', function (): void {
            $value = 'test';
            $tapped = Helpers::tap($value, function (&$v): void {
                $v = 'modified';
            });

            expect($tapped)->toBe('modified');
        });

        it('Doit exécuter tap() sans callback', function (): void {
            $value = new stdClass();
            $proxy = Helpers::tap($value);

            expect($proxy)->toBeAnInstanceOf(HigherOrderTapProxy::class);
        });
    });

    describe('Gestion des erreurs', function (): void {
        xit('Doit déclencher un avertissement de dépréciation', function (): void {
            allow('error_reporting')->toBeCalled()->andReturn(E_ALL);
            allow('trigger_error')->toBeCalled();

            Helpers::deprecationWarning('Test deprecation');

            expect('trigger_error')->toHaveBeenCalled();
        });

        xit('Doit déclencher un avertissement', function (): void {
            allow('trigger_error')->toBeCalled();

            Helpers::triggerWarning('Test warning');

            expect('trigger_error')->toHaveBeenCalled();
        });

        it('Doit lever une exception avec throwIf()', function (): void {
            expect(function (): void {
                Helpers::throwIf(true, 'RuntimeException', 'Test exception');
            })->toThrow(new RuntimeException('Test exception'));

            $result = Helpers::throwIf(false, 'Exception', 'Not thrown');
            expect($result)->toBe(false);
        });
    });
});
