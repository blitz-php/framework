<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Publisher\Publisher;
use org\bovigo\vfs\vfsStream;

use function Kahlan\expect;

describe('Publisher / PublisherOutput', function (): void {
	beforeAll(function (): void {
		$this->structure = [
            'able' => [
                'apple.php' => 'Once upon a midnight dreary',
                'bazam'     => 'While I pondered weak and weary',
            ],
            'boo' => [
                'far' => 'Upon a tome of long-forgotten lore',
                'faz' => 'There came a tapping up on the door',
            ],
            'AnEmptyFolder' => [],
            'simpleFile'    => 'A tap-tap-tapping upon my door',
            '.hidden'       => 'There is no spoon',
        ];
		$this->file 	 = str_replace(['/', '\\'], DS, SUPPORT_PATH . 'Files/baker/banana.php');
		$this->directory = str_replace(['/', '\\'], DS, SUPPORT_PATH . 'Files/able/');

		helper(['filesystem']);
	});
	afterAll(function (): void {
		config()->reset('publisher.restrictions');
	});
	beforeEach(function (): void {
		$this->root 	 = vfsStream::setup('root', null, $this->structure);
		$restrictions = config('publisher.restrictions');
		config(['publisher.restrictions' => array_merge($restrictions, [$this->root->url() => '*'])]);
	});

	it('Copie', function (): void {
		$publisher = new Publisher($this->directory, $this->root->url());
        $publisher->addFile($this->file);

        expect(file_exists($this->root->url() . '/banana.php'))->toBeFalsy();

        $result = $publisher->copy(false);

        expect($result)->toBeTruthy();
        expect(file_exists($this->root->url() . '/banana.php'))->toBeTruthy();
	});

	it('Copie et remplace', function (): void {
		$file      = $this->directory . 'apple.php';
        $publisher = new Publisher($this->directory, $this->root->url() . '/able');
        $publisher->addFile($file);

        expect(file_exists($this->root->url() . '/able/apple.php'))->toBeTruthy();
        expect(same_file($file, $this->root->url() . '/able/apple.php'))->toBeFalsy();

        $result = $publisher->copy(true);

        expect($result)->toBeTruthy();
        expect(same_file($file, $this->root->url() . '/able/apple.php'))->toBeTruthy();
	});

	it('Copie en ignorant les memes fichiers', function (): void {
		$publisher = new Publisher($this->directory, $this->root->url());
        $publisher->addFile($this->file);

        copy($this->file, $this->root->url() . '/banana.php');

        $result = $publisher->copy(false);
        expect($result)->toBeTruthy();

        $result = $publisher->copy(true);
        expect($result)->toBeTruthy();
        expect($publisher->getPublished())->toBe([$this->root->url() . DS . 'banana.php']);
	});

	it('Copie en ignorant les collision', function (): void {
		$publisher = new Publisher($this->directory, $this->root->url());

        @mkdir($this->root->url() . '/banana.php');

        $result = $publisher->addFile($this->file)->copy(false);

        expect($result)->toBeTruthy();
        expect($publisher->getErrors())->toBe([]);
        expect($publisher->getPublished())->toBe([$this->root->url() . DS . 'banana.php']);
	});

	it('Copie collides', function (): void {
		$publisher = new Publisher($this->directory, $this->root->url());
        $expected  = lang('Publisher.collision', ['dir', $this->file, $this->root->url() . DS . 'banana.php']);

        @mkdir($this->root->url() . '/banana.php');

        $result = $publisher->addFile($this->file)->copy(true);
        $errors = $publisher->getErrors();

        expect($result)->toBeFalsy();
        expect($errors)->toHaveLength(1);
        expect(array_keys($errors))->toBe([$this->file]);
        expect($publisher->getPublished())->toBe([]);
        expect($errors[$this->file]->getMessage())->toBe($expected);
	});

    it('Fusion', function (): void {
		$publisher = new Publisher(SUPPORT_PATH . 'Files', $this->root->url());
        $expected  = [
            $this->root->url() . str_replace(['/', '\\'], DS, '/able/apple.php'),
            $this->root->url() . str_replace(['/', '\\'], DS, '/able/fig_3.php'),
            $this->root->url() . str_replace(['/', '\\'], DS, '/able/prune_ripe.php'),
            $this->root->url() . str_replace(['/', '\\'], DS, '/baker/banana.php'),
            $this->root->url() . str_replace(['/', '\\'], DS, '/baker/fig_3.php.txt'),
        ];

        expect(file_exists($this->root->url() . '/able/fig_3.php'))->toBeFalsy();
        expect(is_dir($this->root->url() . '/baker'))->toBeFalsy();

        $result = $publisher->addPath('/')->merge(false);

        expect($result)->toBeTruthy();
        expect(file_exists($this->root->url() . '/able/fig_3.php'))->toBeTruthy();
        expect(is_dir($this->root->url() . '/baker'))->toBeTruthy();
        expect($publisher->getPublished())->toBe($expected);
	});

	it('Fusionne et remplace', function (): void {
		expect(same_file($this->directory . 'apple.php', $this->root->url() . '/able/apple.php'))->toBeFalsy();
        $publisher = new Publisher(SUPPORT_PATH . 'Files', $this->root->url());
        $expected  = [
            $this->root->url() . str_replace(['/', '\\'], DS, '/able/apple.php'),
            $this->root->url() . str_replace(['/', '\\'], DS, '/able/fig_3.php'),
            $this->root->url() . str_replace(['/', '\\'], DS, '/able/prune_ripe.php'),
            $this->root->url() . str_replace(['/', '\\'], DS, '/baker/banana.php'),
            $this->root->url() . str_replace(['/', '\\'], DS, '/baker/fig_3.php.txt'),
        ];

        $result = $publisher->addPath('/')->merge(true);

        expect($result)->toBeTruthy();
        expect(same_file($this->directory . 'apple.php', $this->root->url() . '/able/apple.php'))->toBeTruthy();
        expect($publisher->getPublished())->toBe($expected);
	});

	it('Fusion collides', function (): void {
		$publisher = new Publisher(SUPPORT_PATH . 'Files', $this->root->url());
        $expected  = lang('Publisher.collision', ['dir', $this->directory . 'fig_3.php', $this->root->url() .  DS . 'able' . DS . 'fig_3.php']);
        $published = [
            $this->root->url() . str_replace(['/', '\\'], DS, '/able/apple.php'),
            $this->root->url() . str_replace(['/', '\\'], DS, '/able/prune_ripe.php'),
            $this->root->url() . str_replace(['/', '\\'], DS, '/baker/banana.php'),
            $this->root->url() . str_replace(['/', '\\'], DS, '/baker/fig_3.php.txt'),
        ];

        mkdir($this->root->url() . '/able/fig_3.php');

        $result = $publisher->addPath('/')->merge(true);
        $errors = $publisher->getErrors();

        expect($result)->toBeFalsy();
        expect($errors)->toHaveLength(1);
        expect(array_keys($errors))->toBe([$this->directory . 'fig_3.php']);
        expect($publisher->getPublished())->toBe($published);
        expect($errors[$this->directory . 'fig_3.php']->getMessage())->toBe($expected);
	});

	it('Publish', function (): void {
		$publisher = new Publisher(SUPPORT_PATH . 'Files', $this->root->url());

        $result = $publisher->publish();

        expect($result)->toBeTruthy();
        expect(is_file($this->root->url() . '/able/fig_3.php'))->toBeTruthy();
        expect(is_dir($this->root->url() . '/baker'))->toBeTruthy();
        expect(same_file($this->directory . 'apple.php', $this->root->url() . '/able/apple.php'))->toBeTruthy();
	});
});
