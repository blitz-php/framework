<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Http\UploadedFileFactory;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\UploadedFileInterface;

describe('Http / UploadedFileFactory', function () {
	beforeAll(function () {
		$this->filename = TEMP_PATH . 'uploadedfile-factory-file-test.txt';
		$this->factory  = new UploadedFileFactory();
	});

	describe('UploadedFileFactoryInterface', function () {
		it('create stream resource', function () {
			file_put_contents($this->filename, 'it works');
			$stream = new Stream(Utils::tryFopen($this->filename, 'r'));

			$uploadedFile = $this->factory->createUploadedFile($stream, null, UPLOAD_ERR_OK, 'my-name');

			expect($uploadedFile->getClientFilename())->toBe('my-name');
			expect($uploadedFile->getStream())->toBe($stream);

			@unlink($this->filename);
		});
	});

	describe('makeUploadedFile', function () {
		it('makeUploadedFile', function () {
			$files = [
				'name'     => 'file.txt',
				'type'     => 'text/plain',
				'tmp_name' => __FILE__,
				'error'    => 0,
				'size'     => 1234,
			];

			/** @var UploadedFile $expected */
			$expected = UploadedFileFactory::makeUploadedFile($files);

			expect($expected->getSize())->toBe($files['size']);
			expect($expected->getError())->toBe($files['error']);
			expect($expected->getClientFilename())->toBe($files['name']);
			expect($expected->getClientMediaType())->toBe($files['type']);
		});

		it('makeUploadedFile genere une erreur', function () {
			$files = [
				'name'     => 'file.txt',
				'type'     => 'text/plain',
				'tmp_name' => __FILE__,
				'error'    => 0,
				// 'size'     => 1234, abscense d'un element
			];

			expect(fn() => UploadedFileFactory::makeUploadedFile($files))
				->toThrow(new InvalidArgumentException());
		});
	});

	describe('normalizeUploadedFile', function () {
		it("Création d'un fichier téléchargé à partir de la spécification d'un fichier plat", function () {
			$files = [
				'avatar' => [
					'tmp_name' => 'phpUxcOty',
					'name'     => 'my-avatar.png',
					'size'     => 90996,
					'type'     => 'image/png',
					'error'    => 0,
				],
			];

			$normalised = UploadedFileFactory::normalizeUploadedFiles($files);

			expect($normalised)->toHaveLength(1);
			expect($normalised['avatar'])->toBeAnInstanceOf(UploadedFileInterface::class);
			expect($normalised['avatar']->getClientFilename())->toBe('my-avatar.png');
		});

		it("Traverse les spécifications de fichiers imbriqués pour extraire le fichier téléchargé", function () {
			$files = [
				'my-form' => [
					'details' => [
						'avatar' => [
							'tmp_name' => 'phpUxcOty',
							'name'     => 'my-avatar.png',
							'size'     => 90996,
							'type'     => 'image/png',
							'error'    => 0,
						],
					],
				],
			];

			$normalised = UploadedFileFactory::normalizeUploadedFiles($files);

			expect($normalised)->toHaveLength(1);
			expect($normalised['my-form']['details']['avatar']->getClientFilename())->toBe('my-avatar.png');
		});

		it("Traverse les spécifications de fichiers imbriqués pour extraire le fichier téléchargé", function () {
			$files = [
				'my-form' => [
					'details' => [
						'avatars' => [
							'tmp_name' => [
								0 => 'abc123',
								1 => 'duck123',
								2 => 'goose123',
							],
							'name'     => [
								0 => 'file1.txt',
								1 => 'file2.txt',
								2 => 'file3.txt',
							],
							'size'     => [
								0 => 100,
								1 => 240,
								2 => 750,
							],
							'type'     => [
								0 => 'plain/txt',
								1 => 'image/jpg',
								2 => 'image/png',
							],
							'error'    => [
								0 => 0,
								1 => 0,
								2 => 0,
							],
						],
					],
				],
			];

			$normalised = UploadedFileFactory::normalizeUploadedFiles($files);

			expect($normalised['my-form']['details']['avatars'])->toHaveLength(3);
			expect($normalised['my-form']['details']['avatars'][0]->getClientFilename())->toBe('file1.txt');
			expect($normalised['my-form']['details']['avatars'][1]->getClientFilename())->toBe('file2.txt');
			expect($normalised['my-form']['details']['avatars'][2]->getClientFilename())->toBe('file3.txt');
		});

		it("Traverse les spécifications de fichiers imbriqués pour extraire le fichier téléchargé", function () {
			$files = [
				'slide-shows' => [
					'tmp_name' => [
						// Note: Nesting *under* tmp_name/etc
						0 => [
							'slides' => [
								0 => '/tmp/phpYzdqkD',
								1 => '/tmp/phpYzdfgh',
							],
						],
					],
					'error'    => [
						0 => [
							'slides' => [
								0 => 0,
								1 => 0,
							],
						],
					],
					'name'     => [
						0 => [
							'slides' => [
								0 => 'foo.txt',
								1 => 'bar.txt',
							],
						],
					],
					'size'     => [
						0 => [
							'slides' => [
								0 => 123,
								1 => 200,
							],
						],
					],
					'type'     => [
						0 => [
							'slides' => [
								0 => 'text/plain',
								1 => 'text/plain',
							],
						],
					],
				],
			];

			$normalised = UploadedFileFactory::normalizeUploadedFiles($files);

			expect($normalised['slide-shows'][0]['slides'])->toHaveLength(2);
			expect($normalised['slide-shows'][0]['slides'][0]->getClientFilename())->toBe('foo.txt');
			expect($normalised['slide-shows'][0]['slides'][1]->getClientFilename())->toBe('bar.txt');
		});
	});
});
