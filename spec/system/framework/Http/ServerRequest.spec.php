<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Filesystem\Files\UploadedFile;
use BlitzPHP\Http\ServerRequest;

describe('Http / ServerRequest', function () {
    describe('Detector', function () {
		it('Custom detector avec des arguments personnalises', function () {
			$request = new ServerRequest();
			$request->addDetector('controller', function ($request, $name) {
				return $request->getParam('controller') === $name;
			});

			$request = $request->withParam('controller', 'blitz');

			expect($request->is('controller', 'blitz'))->toBeTruthy();
			expect($request->is('controller', 'nonExistingController'))->toBeFalsy();
			expect($request->isController('blitz'))->toBeTruthy();
			expect($request->isController('nonExistingController'))->toBeFalsy();
		});

		it("Header detector", function () {
			$request = new ServerRequest();
			$request->addDetector('host', ['header' => ['host' => 'blitzphp.com']]);

			$request = $request->withEnv('HTTP_HOST', 'blitzphp.com');
			expect($request->is('host'))->toBeTruthy();

			$request = $request->withEnv('HTTP_HOST', 'php.net');
			expect($request->is('host'))->toBeFalsy();
		});

		it("Extension detector", function () {
			$request = new ServerRequest();
			$request = $request->withParam('_ext', 'json');

			expect($request->is('json'))->toBeTruthy();

			$request = new ServerRequest();
			$request = $request->withParam('_ext', 'xml');

			expect($request->is('xml'))->toBeTruthy();
			expect($request->is('json'))->toBeFalsy();
		});

		it("Accept Header detector", function () {
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
	});

	describe('Constructeur', function () {
		it('construction avec les données de la requête', function () {
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

		it('construction avec une chaine URL', function () {
			$request = new ServerRequest([
				'url' => '/articles/view/1',
				'environment' => ['REQUEST_URI' => '/some/other/path'],
			]);
			expect($request->getUri()->getPath())->toBe('/articles/view/1');

			$request = new ServerRequest(['url' => '/']);
			expect($request->getUri()->getPath())->toBe('/');
		});

		it('Teste que les arguments de la chaîne de requête fournis dans la chaîne de l\'URL sont analysés.', function () {
			$request = new ServerRequest(['url' => 'some/path?one=something&two=else']);
			$expected = ['one' => 'something', 'two' => 'else'];

			expect($request->getQueryParams())->toEqual($expected);
			expect($request->getUri()->getPath())->toBe('/some/path');
			expect($request->getUri()->getQuery())->toBe('one=something&two=else');
		});

		xit('Tester que les chaînes de requête sont gérées correctement.', function () {
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

		xit("Tester que l'URL dans le chemin d'accès est traité correctement.", function () {
			$config = ['environment' => ['REQUEST_URI' => '/jump/http://blitzphp.com']];
			$request = new ServerRequest($config);
			expect($request->getRequestTarget())->toBe('/jump/http://blitzphp.com');

			$config = ['environment' => [
				'REQUEST_URI' => config('app.base_url') . '/jump/http://blitzphp.com',
			]];
			$request = new ServerRequest($config);
			expect($request->getRequestTarget())->toBe('/jump/http://blitzphp.com');

		});

		it('getPath', function () {
			$request = new ServerRequest(['url' => '/']);
			expect($request->getPath())->toBe('/');

			$request = new ServerRequest(['url' => 'some/path?one=something&two=else']);
			expect($request->getPath())->toBe('/some/path');

			$request = $request->withRequestTarget('/foo/bar?x=y');
			expect($request->getPath())->toBe('/foo/bar');
		});
	});

	describe('Parsing', function () {
		it("Test d'analyse des données POST dans l'objet.", function () {
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

	describe('Uploaded files', function () {
		it("Tester que le constructeur utilise les objets fichiers téléchargés s'ils sont présents.", function () {
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

		it("Liste de fichiers vide.", function () {
			$request = new ServerRequest(['files' => []]);
        	expect($request->getUploadedFiles())->toBeEmpty();
        	expect($request->getData())->toBeEmpty();
		});

		it("Remplacement de fichiers.", function () {
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

		it("Recuperation d'un fichier.", function () {
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

		it("Remplacement de fichiers avec un fichier invalide.", function () {
			$request = new ServerRequest();

			expect(fn() => $request->withUploadedFiles(['avatar' => 'picture']))
				->toThrow(new InvalidArgumentException('Fichier invalide à `avatar`'));
		});

		it("Remplacement de fichiers avec un fichier invalide imbriquer.", function () {
			$request = new ServerRequest();

			expect(fn() => $request->withUploadedFiles(['user' => ['avatar' => 'not a file']]))
				->toThrow(new InvalidArgumentException('Fichier invalide à `user.avatar`'));
		});
	});
});
