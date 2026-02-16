<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
use BlitzPHP\Exceptions\HttpException;
use BlitzPHP\Filesystem\Files\UploadedFile;
use BlitzPHP\Http\ServerRequest;

use function Kahlan\expect;

describe('Http / ServerRequest', function (): void {
   	describe('Detector', function (): void {
        it('Custom detector avec des arguments personnalises', function (): void {
            $request = new ServerRequest();
            $request->addDetector('controller', fn($request, $name): bool => $request->getParam('controller') === $name);

            $request = $request->withParam('controller', 'blitz');

            expect($request->is('controller', 'blitz'))->toBeTruthy();
            expect($request->is('controller', 'nonExistingController'))->toBeFalsy();
            expect($request->isController('blitz'))->toBeTruthy();
            expect($request->isController('nonExistingController'))->toBeFalsy();
        });

        it("Header detector", function (): void {
            $request = new ServerRequest();
            $request->addDetector('host', ['header' => ['host' => 'blitzphp.com']]);

            $request = $request->withEnv('HTTP_HOST', 'blitzphp.com');
            expect($request->is('host'))->toBeTruthy();

            $request = $request->withEnv('HTTP_HOST', 'php.net');
            expect($request->is('host'))->toBeFalsy();
        });

        it("Extension detector", function (): void {
            $request = new ServerRequest();
            $request = $request->withParam('_ext', 'json');

            expect($request->is('json'))->toBeTruthy();

            $request = new ServerRequest();
            $request = $request->withParam('_ext', 'xml');

            expect($request->is('xml'))->toBeTruthy();
            expect($request->is('json'))->toBeFalsy();
        });

        it("Accept Header detector", function (): void {
            $request = new ServerRequest();
            $request = $request->withEnv('HTTP_ACCEPT', 'application/json, text/plain, */*');
            expect($request->is('json'))->toBeTruthy();

            $request = new ServerRequest();
            $request = $request->withEnv('HTTP_ACCEPT', 'text/plain, */*');
            expect($request->is('json'))->toBeFalsy();

            $request = new ServerRequest();
            $request = $request->withEnv('HTTP_ACCEPT', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8');
            expect($request->is('json'))->toBeFalsy();
            expect($request->is('xml'))->toBeFalsy();
        });

        it('AJAX detector', function (): void {
            $request = new ServerRequest();
			$request->addDetector('ajax', ['header' => ['X-Requested-With' => 'XMLHttpRequest']]);

            $request = $request->withEnv('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest');
            expect($request->is('ajax'))->toBeTruthy();

            $request = new ServerRequest();
            $request->addDetector('ajax', ['header' => ['X-Requested-With' => 'XMLHttpRequest']]);
            expect($request->is('ajax'))->toBeFalsy();
        });

        it('Detector avec paramètre vide', function (): void {
            $request = new ServerRequest();
            $request->addDetector('empty', fn(): bool => true);
            expect($request->is('empty'))->toBeTruthy();
        });

        it('Detector inexistant', function (): void {
            $request = new ServerRequest();
            expect(fn(): bool => $request->is('nonexistent'))
				->toThrow(new InvalidArgumentException("Aucun détecteur défini pour le type `nonexistent`."));
        });

        it('Ajout multiple detectors', function (): void {
            $request = new ServerRequest();
            $request->addDetector('custom1', fn(): bool => true);
            $request->addDetector('custom2', fn(): bool => false);
            expect($request->is('custom1'))->toBeTruthy();
            expect($request->is('custom2'))->toBeFalsy();
        });
    });

	describe('Constructeur', function (): void {
		it('construction avec les données de la requête', function (): void {
			$data = [
				'query' => [
					'one' => 'param',
					'two' => 'banana',
				],
				'url' => 'some/path',
			];
			$request = new ServerRequest($data);

			expect($request->getQuery('one'))->toBe('param');
			expect($request->getQueryParams())->toEqual($data['query']);
			expect($request->getRequestTarget())->toEqual('/some/path');
		});

		it('construction avec une chaine URL', function (): void {
			$request = new ServerRequest([
				'url' => '/articles/view/1',
				'environment' => ['REQUEST_URI' => '/some/other/path'],
			]);
			expect($request->getUri()->getPath())->toBe('/articles/view/1');

			$request = new ServerRequest(['url' => '/']);
			expect($request->getUri()->getPath())->toBe('/');
		});

		it('Teste que les arguments de la chaîne de requête fournis dans la chaîne de l\'URL sont analysés.', function (): void {
			$request = new ServerRequest(['url' => 'some/path?one=something&two=else']);
			$expected = ['one' => 'something', 'two' => 'else'];

			expect($request->getQueryParams())->toEqual($expected);
			expect($request->getUri()->getPath())->toBe('/some/path');
			expect($request->getUri()->getQuery())->toBe('one=something&two=else');
		});

		xit('Tester que les chaînes de requête sont gérées correctement.', function (): void {
			$config = ['environment' => ['REQUEST_URI' => '/tasks/index?ts=123456']];
        	$request = new ServerRequest($config);
        	expect($request->getRequestTarget())->toBe('/tasks/index');

			$config = ['environment' => ['REQUEST_URI' => '/some/path?url=http://blitzphp.com']];
			$request = new ServerRequest($config);
			expect($request->getRequestTarget())->toBe('/some/path');

			$config = ['environment' => [
				'REQUEST_URI' => config('app.base_url') . '/other/path?url=http://blitzphp.com',
			]];
			$request = new ServerRequest($config);
			expect($request->getRequestTarget())->toBe('/other/path');
		});

		xit("Tester que l'URL dans le chemin d'accès est traité correctement.", function (): void {
			$config = ['environment' => ['REQUEST_URI' => '/jump/http://blitzphp.com']];
			$request = new ServerRequest($config);
			expect($request->getRequestTarget())->toBe('/jump/http://blitzphp.com');

			$config = ['environment' => [
				'REQUEST_URI' => config('app.base_url') . '/jump/http://blitzphp.com',
			]];
			$request = new ServerRequest($config);
			expect($request->getRequestTarget())->toBe('/jump/http://blitzphp.com');

		});

		it('getPath', function (): void {
			$request = new ServerRequest(['url' => '/']);
			expect($request->getPath())->toBe('/');

			$request = new ServerRequest(['url' => 'some/path?one=something&two=else']);
			expect($request->getPath())->toBe('/some/path');

			$request = $request->withRequestTarget('/foo/bar?x=y');
			expect($request->getPath())->toBe('/foo/bar');
		});
	});

	describe('Parsing', function (): void {
		it("Test d'analyse des données POST dans l'objet.", function (): void {
			$post = [
				'Article' => ['title'],
			];
			$request = new ServerRequest(compact('post'));
			expect($post)->toEqual($request->getData());

			$post = ['one' => 1, 'two' => 'three'];
			$request = new ServerRequest(compact('post'));
			expect($post)->toEqual($request->getData());

			$post = [
				'Article' => ['title' => 'Testing'],
				'action' => 'update',
			];
			$request = new ServerRequest(compact('post'));
			expect($post)->toEqual($request->getData());
		});
	});

    describe('Environment', function (): void {
        it('Récupère une variable d\'environnement', function (): void {
            $request = new ServerRequest();
            $request = $request->withEnv('SERVER_NAME', 'example.com');
            expect($request->env('SERVER_NAME'))->toBe('example.com');
            expect($request->env('NON_EXISTENT', 'default'))->toBe('default');
        });

        it('Récupère toutes les variables d\'environnement', function (): void {
            $request = new ServerRequest();
            $request = $request->withEnv('SERVER_NAME', 'example.com')->withEnv('REQUEST_METHOD', 'GET');
            $env = $request->env();
            expect($env)->toContainKey('SERVER_NAME');
            expect($env['SERVER_NAME'])->toBe('example.com');
        });

        it('Détermine si une variable d\'environnement existe', function (): void {
            $request = new ServerRequest();
            $request = $request->withEnv('SERVER_NAME', 'example.com');
            expect($request->hasEnv('SERVER_NAME'))->toBeTruthy();
            expect($request->hasEnv('NON_EXISTENT'))->toBeFalsy();
        });

        it('Env avec valeur null', function (): void {
            $request = new ServerRequest();
            $request = $request->withEnv('NULL_VAR', null);
            expect($request->env('NULL_VAR'))->toBeNull();
        });

        it('Env avec array', function (): void {
            $request = new ServerRequest();
            $request = $request->withEnv('ARRAY_VAR', ['a' => 1, 'b' => 2]);
            expect($request->env('ARRAY_VAR'))->toEqual('1, 2');
        });
    });

    describe('Params', function (): void {
        it('Récupère un paramètre', function (): void {
            $request = new ServerRequest(['params' => ['controller' => 'home']]);
            expect($request->getParam('controller'))->toBe('home');
            expect($request->getParam('nonexistent', 'default'))->toBe('default');
        });

        it('Détermine si un paramètre existe', function (): void {
            $request = new ServerRequest(['params' => ['action' => 'index']]);
            expect($request->hasParam('action'))->toBeTruthy();
            expect($request->hasParam('nonexistent'))->toBeFalsy();
        });

        it('Récupère tous les paramètres', function (): void {
            $request = new ServerRequest(['params' => ['id' => 1, 'name' => 'test']]);
            expect($request->getAttribute('params'))->toEqual(['id' => 1, 'name' => 'test']);
        });

        it('Paramètre nested', function (): void {
            $request = new ServerRequest(['params' => ['user' => ['id' => 1]]]);
            expect($request->getParam('user.id'))->toBe(1);
        });

        it('Paramètre manquant avec dot notation', function (): void {
            $request = new ServerRequest(['params' => ['user' => []]]);
            expect($request->getParam('user.name', 'default'))->toBe('default');
        });
    });

    describe('Negotiation', function (): void {
        it('Négocie le type de média', function (): void {
            $request = new ServerRequest();
            $request = $request->withEnv('HTTP_ACCEPT', 'application/json, text/html');
            expect($request->negotiate('media', ['application/json', 'text/html']))->toBe('application/json');
        });

        it('Négocie le charset', function (): void {
            $request = new ServerRequest();
            $request = $request->withEnv('HTTP_ACCEPT_CHARSET', 'utf-8, iso-8859-1;q=0.5');
            expect($request->negotiate('charset', ['utf-8', 'iso-8859-1']))->toBe('utf-8');
        });

        it('Négocie l\'encoding', function (): void {
            $request = new ServerRequest();
            $request = $request->withEnv('HTTP_ACCEPT_ENCODING', 'gzip, deflate');
            expect($request->negotiate('encoding', ['gzip', 'deflate']))->toBe('gzip');
        });

        it('Négocie la langue', function (): void {
            $request = new ServerRequest();
            $request = $request->withEnv('HTTP_ACCEPT_LANGUAGE', 'fr-FR, en-US;q=0.8');
            expect($request->negotiate('language', ['fr-FR', 'en-US']))->toBe('fr-FR');
        });

        it('Lève une exception pour type invalide', function (): void {
            $request = new ServerRequest();
            expect(fn(): string => $request->negotiate('invalid', []))
                ->toThrow(new HttpException('invalid is not a valid negotiation type. Must be one of: media, charset, encoding, language.'));
        });

        it('Négociation sans header', function (): void {
            $request = new ServerRequest();
            expect($request->negotiate('media', ['text/html']))->toBe('text/html'); // Fallback
        });

        it('Négociation avec q-values', function (): void {
            $request = new ServerRequest();
            $request = $request->withEnv('HTTP_ACCEPT', 'text/html;q=0.5, application/json;q=0.9');
            expect($request->negotiate('media', ['text/html', 'application/json']))->toBe('application/json');
        });
    });

    describe('Locale', function (): void {
        it('Définit et récupère la locale', function (): void {
            $request = new ServerRequest();
            $newRequest = $request->withLocale('fr');
            expect($request->getLocale())->toBe('en'); // Default
            expect($newRequest->getLocale())->toBe('fr');
        });

        it('Fallback sur locale par défaut si invalide', function (): void {
            $request = new ServerRequest();
            $newRequest = $request->withLocale('invalid');
            expect($newRequest->getLocale())->toBe('en'); // Assume config default
        });

        it('Locale depuis attribute', function (): void {
            $request = new ServerRequest();
            $request = $request->withAttribute('locale', 'de');
            expect($request->getLocale())->toBe('de');
        });

        it('Locale depuis lang attribute legacy', function (): void {
            $request = new ServerRequest();
            $request = $request->withAttribute('lang', 'es');
            expect($request->getLocale())->toBe('es');
        });
    });

    describe('Uploaded files', function (): void {
        it("Tester que le constructeur utilise les objets fichiers téléchargés s'ils sont présents.", function (): void {
			$file = new UploadedFile(
				__FILE__,
				123,
				UPLOAD_ERR_OK,
				'test.php',
				'text/plain'
			);
        	$request = new ServerRequest(['files' => ['avatar' => $file]]);
        	expect($request->getUploadedFiles())->toBe(['avatar' => $file]);
		});

		it("Liste de fichiers vide.", function (): void {
			$request = new ServerRequest(['files' => []]);
        	expect($request->getUploadedFiles())->toBeEmpty();
        	expect($request->getData())->toBeEmpty();
		});

		it("Remplacement de fichiers.", function (): void {
			$file = new UploadedFile(
				__FILE__,
				123,
				UPLOAD_ERR_OK,
				'test.php',
				'text/plain'
			);
			$request = new ServerRequest();
			$new = $request->withUploadedFiles(['picture' => $file]);

			expect($request->getUploadedFiles())->toBe([]);
			expect($request)->not->toBe($new);
			expect($new->getUploadedFiles())->toBe(['picture' => $file]);
		});

		it("Recuperation d'un fichier.", function (): void {
			$file = new UploadedFile(
				__FILE__,
				123,
				UPLOAD_ERR_OK,
				'test.php',
				'text/plain'
			);
			$request = new ServerRequest();
			$new = $request->withUploadedFiles(['picture' => $file]);

			expect($new->getUploadedFile(''))->toBeNull();
			expect($new->getUploadedFile('picture'))->toEqual($file);

			$new = $request->withUploadedFiles([
				'pictures' => [
					[
						'image' => $file,
					],
				],
			]);

			expect($new->getUploadedFile('pictures'))->toBeNull();
			expect($new->getUploadedFile('pictures.0'))->toBeAn('array');
			expect($new->getUploadedFile('pictures.1'))->toBeNull();
			expect($new->getUploadedFile('pictures.0.image'))->toEqual($file);
		});

		it("Remplacement de fichiers avec un fichier invalide.", function (): void {
			$request = new ServerRequest();

			expect(fn(): ServerRequest => $request->withUploadedFiles(['avatar' => 'picture']))
				->toThrow(new InvalidArgumentException('Fichier invalide à `avatar`.'));
		});

		it("Remplacement de fichiers avec un fichier invalide imbriquer.", function (): void {
			$request = new ServerRequest();

			expect(fn(): ServerRequest => $request->withUploadedFiles(['user' => ['avatar' => 'not a file']]))
				->toThrow(new InvalidArgumentException('Fichier invalide à `user.avatar`.'));
		});

        it('Fichier avec erreur', function (): void {
            $file = new UploadedFile(__FILE__, 0, UPLOAD_ERR_INI_SIZE, 'test.txt', 'text/plain');
            $request = new ServerRequest(['files' => ['upload' => $file]]);
            expect($request->getUploadedFile('upload')->getError())->toBe(UPLOAD_ERR_INI_SIZE);
        });

        it('Nested files array', function (): void {
            $file1 = new UploadedFile(__FILE__, 0, UPLOAD_ERR_OK, '1.txt', 'text/plain');
            $file2 = new UploadedFile(__FILE__, 0, UPLOAD_ERR_OK, '2.txt', 'text/plain');
            $request = new ServerRequest(['files' => ['uploads' => [$file1, $file2]]]);
            expect($request->getUploadedFile('uploads.0'))->toEqual($file1);
            expect($request->getUploadedFile('uploads.1'))->toEqual($file2);
        });
    });

    describe('Trust Proxy', function (): void {
        it('Détermine l\'IP client derrière proxy', function (): void {
            $request = new ServerRequest();
            $request->trustProxy = true;
            $request = $request->withEnv('HTTP_X_FORWARDED_FOR', '203.0.113.195');
            expect($request->clientIp())->toBe('203.0.113.195');

            $request = new ServerRequest();
            $request->trustProxy = false;
            $request = $request->withEnv('REMOTE_ADDR', '198.51.100.178');
            expect($request->clientIp())->toBe('198.51.100.178');
        });

        it('IP avec multiple proxies', function (): void {
            $request = new ServerRequest();
            $request->trustProxy = true;
            $request = $request->withEnv('HTTP_X_FORWARDED_FOR', '203.0.113.195, 198.51.100.178');
            expect($request->clientIp())->toBe('198.51.100.178'); // derniere adresse

			// si on a defini les proxies de confiances, la premiere ip est prise
			$request->setTrustedProxies(['203.0.113.195']);
            expect($request->clientIp())->toBe('203.0.113.195'); // First one
        });

        it('Pas de proxy, fallback REMOTE_ADDR', function (): void {
            $request = new ServerRequest();
            $request->trustProxy = true;
            $request = $request->withEnv('REMOTE_ADDR', '127.0.0.1');
            expect($request->clientIp())->toBe('127.0.0.1');
        });
    });
});
