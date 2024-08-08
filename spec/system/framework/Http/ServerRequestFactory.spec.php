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
use BlitzPHP\Http\ServerRequestFactory;
use Psr\Http\Message\UploadedFileInterface;

describe('Http / ServerRequestFactory', function (): void {
	describe('ServerRequestFactoryInterface', function (): void {
		it('Test createServerRequest', function (): void {
			$factory = new ServerRequestFactory();
        	$request = $factory->createServerRequest('GET', 'https://blitzphp.com/team', ['foo' => 'bar']);

			expect($request)->toBeAnInstanceOf(ServerRequest::class);
			expect($request->getMethod())->toBe('GET');
			expect($request->getRequestTarget())->toBe('/team');

			$expected = ['foo' => 'bar', 'REQUEST_METHOD' => 'GET'];
			expect($request->getServerParams())->toBe($expected);
		});
	});

    describe('Superglobales', function (): void {
		it('Teste que fromGlobals lit les superglobales', function (): void {
			$post = [
				'title' => 'custom',
			];
			$files = [
				'image' => [
					'tmp_name' => __FILE__,
					'error' => 0,
					'name' => 'cats.png',
					'type' => 'image/png',
					'size' => 2112,
				],
			];
			$cookies = ['key' => 'value'];
			$query = ['query' => 'string'];
			$res = ServerRequestFactory::fromGlobals([], $query, $post, $cookies, $files);

			expect($res->getCookie('key'))->toBe($cookies['key']);
			expect($res->getQuery('query'))->toBe($query['query']);
			expect($res->getData())->toContainKeys(['title', 'image']);
			expect($res->getUploadedFiles())->toHaveLength(1);

			/** @var UploadedFileInterface $expected */
			$expected = $res->getData('image');
			expect($expected)->toBeAnInstanceOf(UploadedFileInterface::class);
			expect($expected->getSize())->toBe($files['image']['size']);
			expect($expected->getError())->toBe($files['image']['error']);
			expect($expected->getClientFilename())->toBe($files['image']['name']);
			expect($expected->getClientMediaType())->toBe($files['image']['type']);
		});

		it("Schema", function (): void {
			$server = [
				'DOCUMENT_ROOT'          => '/blitz/repo/webroot',
				'PHP_SELF'               => '/index.php',
				'REQUEST_URI'            => '/posts/add',
				'HTTP_X_FORWARDED_PROTO' => 'https',
			];
			$request = ServerRequestFactory::fromGlobals($server);

			expect($request->scheme())->toBe('http');
			expect($request->getUri()->getScheme())->toBe('http');

			$request->setTrustedProxies([]);
			// Oui, même le fait de définir une liste vide de proxies fait l'affaire.
			expect($request->scheme())->toBe('https');
			expect($request->getUri()->getScheme())->toBe('https');
		});
	});

	describe('Parsing', function (): void {
		it('test Form Url Encoded Body Parsing', function (): void {
			$data = [
				'Article' => ['title'],
			];
			$request = ServerRequestFactory::fromGlobals([
				'REQUEST_METHOD' => 'PUT',
				'CONTENT_TYPE'   => 'application/x-www-form-urlencoded; charset=UTF-8',
				'BLITZPHP_INPUT' => 'Article[]=title',
			]);
			expect($request->getData())->toBe($data);

			$data = ['one' => 1, 'two' => 'three'];
			$request = ServerRequestFactory::fromGlobals([
				'REQUEST_METHOD' => 'PUT',
				'CONTENT_TYPE'   => 'application/x-www-form-urlencoded; charset=UTF-8',
				'BLITZPHP_INPUT' => 'one=1&two=three',
			]);
			expect($request->getData())->toEqual($data);

			$request = ServerRequestFactory::fromGlobals([
				'REQUEST_METHOD' => 'DELETE',
				'CONTENT_TYPE'   => 'application/x-www-form-urlencoded; charset=UTF-8',
				'BLITZPHP_INPUT' => 'Article[title]=Testing&action=update',
			]);
			$expected = [
				'Article' => ['title' => 'Testing'],
				'action' => 'update',
			];
			expect($request->getData())->toBe($expected);

			$data = [
				'Article' => ['title'],
				'Tag' => ['Tag' => [1, 2]],
			];
			$request = ServerRequestFactory::fromGlobals([
				'REQUEST_METHOD' => 'PATCH',
				'CONTENT_TYPE' => 'application/x-www-form-urlencoded; charset=UTF-8',
				'BLITZPHP_INPUT' => 'Article[]=title&Tag[Tag][]=1&Tag[Tag][]=2',
			]);
			expect($request->getData())->toEqual($data);
		});

		it('methode reecrite', function (): void {
			$post = ['_method' => 'POST'];
			$request = ServerRequestFactory::fromGlobals([], [], $post);
			expect($request->getEnv('REQUEST_METHOD'))->toBe('POST');

			$post = ['_method' => 'DELETE'];
			$request = ServerRequestFactory::fromGlobals([], [], $post);
			expect($request->getEnv('REQUEST_METHOD'))->toBe('DELETE');

			$request = ServerRequestFactory::fromGlobals(['HTTP_X_HTTP_METHOD_OVERRIDE' => 'PUT']);
			expect($request->getEnv('REQUEST_METHOD'))->toBe('PUT');

			$request = ServerRequestFactory::fromGlobals(
				['REQUEST_METHOD' => 'POST'],
				[],
				['_method' => 'PUT']
			);
			expect($request->getEnv('REQUEST_METHOD'))->toBe('PUT');
			expect($request->getEnv('ORIGINAL_REQUEST_METHOD'))->toBe('POST');
		});

		it('Recuperation des parametres serveur', function (): void {
			$vars = [
				'REQUEST_METHOD' => 'PUT',
				'HTTPS'          => 'on',
			];

			$request = ServerRequestFactory::fromGlobals($vars);
			$expected = $vars + [
				'CONTENT_TYPE'                => null,
				'HTTP_CONTENT_TYPE'           => null,
				'HTTP_X_HTTP_METHOD_OVERRIDE' => null,
				'ORIGINAL_REQUEST_METHOD'     => 'PUT',
				'HTTP_HOST'                   => 'example.com',
			];

			expect($request->getServerParams())->toBe($expected);
		});

		it('Surcharge de la méthode Corps vide analysé.', function (): void {
			$body = ['_method' => 'GET', 'foo' => 'bar'];
			$request = ServerRequestFactory::fromGlobals(
				['REQUEST_METHOD' => 'POST'],
				[],
				$body
			);
			expect($request->getParsedBody())->toBeEmpty();

			$request = ServerRequestFactory::fromGlobals(
				[
					'REQUEST_METHOD' => 'POST',
					'HTTP_X_HTTP_METHOD_OVERRIDE' => 'GET',
				],
				[],
				['foo' => 'bar']
			);
			expect($request->getParsedBody())->toBeEmpty();
		});
	});

	describe('Fichiers', function (): void {
		it("Teste le comportement par défaut de la fusion des fichiers téléchargés.", function (): void {
			$files = [
				'file' => [
					'name'     => 'file.txt',
					'type'     => 'text/plain',
					'tmp_name' => __FILE__,
					'error'    => 0,
					'size'     => 1234,
				],
			];
			$request = ServerRequestFactory::fromGlobals(null, null, null, null, $files);

			/** @var UploadedFile $expected */
			$expected = $request->getData('file');

			expect($expected->getSize())->toBe($files['file']['size']);
			expect($expected->getError())->toBe($files['file']['error']);
			expect($expected->getClientFilename())->toBe($files['file']['name']);
			expect($expected->getClientMediaType())->toBe($files['file']['type']);
		});

		it("Test de traitement des fichiers avec des noms de champs `file`.", function (): void {
			$files = [
				'image_main' => [
					'name'     => ['file' => 'born on.txt'],
					'type'     => ['file' => 'text/plain'],
					'tmp_name' => ['file' => __FILE__],
					'error'    => ['file' => 0],
					'size'     => ['file' => 17178],
				],
				0 => [
					'name' => ['image' => 'scratch.text'],
					'type' => ['image' => 'text/plain'],
					'tmp_name' => ['image' => __FILE__],
					'error' => ['image' => 0],
					'size' => ['image' => 1490],
				],
				'pictures' => [
					'name' => [
						0 => ['file' => 'a-file.png'],
						1 => ['file' => 'a-moose.png'],
					],
					'type' => [
						0 => ['file' => 'image/png'],
						1 => ['file' => 'image/jpg'],
					],
					'tmp_name' => [
						0 => ['file' => __FILE__],
						1 => ['file' => __FILE__],
					],
					'error' => [
						0 => ['file' => 0],
						1 => ['file' => 0],
					],
					'size' => [
						0 => ['file' => 17188],
						1 => ['file' => 2010],
					],
				],
			];

			$post = [
				'pictures' => [
					0 => ['name' => 'A cat'],
					1 => ['name' => 'A moose'],
				],
				0 => [
					'name' => 'A dog',
				],
			];

			$request = ServerRequestFactory::fromGlobals(null, null, $post, null, $files);
			$expected = [
				'image_main' => [
					'file' => new UploadedFile(
						__FILE__,
						17178,
						0,
						'born on.txt',
						'text/plain'
					),
				],
				'pictures' => [
					0 => [
						'name' => 'A cat',
						'file' => new UploadedFile(
							__FILE__,
							17188,
							0,
							'a-file.png',
							'image/png'
						),
					],
					1 => [
						'name' => 'A moose',
						'file' => new UploadedFile(
							__FILE__,
							2010,
							0,
							'a-moose.png',
							'image/jpg'
						),
					],
				],
				0 => [
					'name' => 'A dog',
					'image' => new UploadedFile(
						__FILE__,
						1490,
						0,
						'scratch.text',
						'text/plain'
					),
				],
			];

			expect($request->getData())->toEqual($expected);

			$uploads = $request->getUploadedFiles();
			expect($uploads)->toHaveLength(3);
			expect($uploads)->toContainKey(0);
			expect($uploads[0]['image']->getClientFilename())->toBe('scratch.text');

			expect($uploads)->toContainKey('pictures');
			expect($uploads['pictures'][0]['file']->getClientFilename())->toBe('a-file.png');
			expect($uploads['pictures'][1]['file']->getClientFilename())->toBe('a-moose.png');

			expect($uploads)->toContainKey('image_main');
			expect($uploads['image_main']['file']->getClientFilename())->toBe('born on.txt');
		});

		it("Test de traitement d'un fichier d'entrée ne contenant pas de 's.", function (): void {
			$files = [
				'birth_cert' => [
					'name'     => 'born on.txt',
					'type'     => 'application/octet-stream',
					'tmp_name' => __FILE__,
					'error'    => 0,
					'size'     => 123,
				],
			];

			$request = ServerRequestFactory::fromGlobals([], [], [], [], $files);
			expect($request->getData()['birth_cert'])->toBeAnInstanceOf(UploadedFileInterface::class);

			$uploads = $request->getUploadedFiles();
			expect($uploads)->toHaveLength(1);
			expect($uploads)->toContainKey('birth_cert');
			expect($uploads['birth_cert']->getClientFilename())->toBe('born on.txt');
			expect($uploads['birth_cert']->getError())->toBe(0);
			expect($uploads['birth_cert']->getClientMediaType())->toBe('application/octet-stream');
			expect($uploads['birth_cert']->getSize())->toBe(123);
		});

		it("Tester que les fichiers du 0e index fonctionnent.", function (): void {
			$files = [
				0 => [
					'name'     => 'blitz_sqlserver_patch.patch',
					'type'     => 'text/plain',
					'tmp_name' => __FILE__,
					'error'    => 0,
					'size'     => 6271,
				],
			];

			$request = ServerRequestFactory::fromGlobals([], [], [], [], $files);
			expect($request->getData()[0])->toBeAnInstanceOf(UploadedFileInterface::class);

			$uploads = $request->getUploadedFiles();
			expect($uploads)->toHaveLength(1);
			expect($uploads[0]->getClientFilename())->toBe($files[0]['name']);
		});

		it("Teste que les téléchargements de fichiers sont fusionnés avec les données du message sous forme d'objets et non de tableaux.", function (): void {
			$files = [
				'flat' => [
					'name' => 'flat.txt',
					'type' => 'text/plain',
					'tmp_name' => __FILE__,
					'error' => 0,
					'size' => 1,
				],
				'nested' => [
					'name' => ['file' => 'nested.txt'],
					'type' => ['file' => 'text/plain'],
					'tmp_name' => ['file' => __FILE__],
					'error' => ['file' => 0],
					'size' => ['file' => 12],
				],
				0 => [
					'name' => 'numeric.txt',
					'type' => 'text/plain',
					'tmp_name' => __FILE__,
					'error' => 0,
					'size' => 123,
				],
				1 => [
					'name' => ['file' => 'numeric-nested.txt'],
					'type' => ['file' => 'text/plain'],
					'tmp_name' => ['file' => __FILE__],
					'error' => ['file' => 0],
					'size' => ['file' => 1234],
				],
				'deep' => [
					'name' => [
						0 => ['file' => 'deep-1.txt'],
						1 => ['file' => 'deep-2.txt'],
					],
					'type' => [
						0 => ['file' => 'text/plain'],
						1 => ['file' => 'text/plain'],
					],
					'tmp_name' => [
						0 => ['file' => __FILE__],
						1 => ['file' => __FILE__],
					],
					'error' => [
						0 => ['file' => 0],
						1 => ['file' => 0],
					],
					'size' => [
						0 => ['file' => 12345],
						1 => ['file' => 123456],
					],
				],
			];

			$post = [
				'flat' => ['existing'],
				'nested' => [
					'name' => 'nested',
					'file' => ['existing'],
				],
				'deep' => [
					0 => [
						'name' => 'deep 1',
						'file' => ['existing'],
					],
					1 => [
						'name' => 'deep 2',
					],
				],
				1 => [
					'name' => 'numeric nested',
				],
			];

			$expected = [
				'flat' => new UploadedFile(
					__FILE__,
					1,
					0,
					'flat.txt',
					'text/plain'
				),
				'nested' => [
					'name' => 'nested',
					'file' => new UploadedFile(
						__FILE__,
						12,
						0,
						'nested.txt',
						'text/plain'
					),
				],
				'deep' => [
					0 => [
						'name' => 'deep 1',
						'file' => new UploadedFile(
							__FILE__,
							12345,
							0,
							'deep-1.txt',
							'text/plain'
						),
					],
					1 => [
						'name' => 'deep 2',
						'file' => new UploadedFile(
							__FILE__,
							123456,
							0,
							'deep-2.txt',
							'text/plain'
						),
					],
				],
				0 => new UploadedFile(
					__FILE__,
					123,
					0,
					'numeric.txt',
					'text/plain'
				),
				1 => [
					'name' => 'numeric nested',
					'file' => new UploadedFile(
						__FILE__,
						1234,
						0,
						'numeric-nested.txt',
						'text/plain'
					),
				],
			];

			$request = ServerRequestFactory::fromGlobals([], [], $post, [], $files);

			expect($request->getData())->toEqual($expected);
		});

		it("Test de passage d'une structure de liste de fichiers invalide.", function (): void {
			expect(fn() => ServerRequestFactory::fromGlobals([], [], [], [], [
				[
					'invalid' => [
						'data',
					],
				],
			]))->toThrow(new InvalidArgumentException('Valeur non valide dans la spécification des fichiers'));
		});
	});
});
