<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Publisher\ContentReplacer;

describe('Publisher / ContentReplacer', function (): void {
	it('Remplace le contenu', function (): void {
		$replacer = new ContentReplacer();
        $content  = <<<'FILE'
            <?php

            namespace BlitzPHP\Schild\Config;

            use BlitzPHP\Config\BaseConfig;
            use BlitzPHP\Schild\Models\UserModel;

            class Auth extends BaseConfig
            {
            FILE;

        $replaces = [
            'namespace BlitzPHP\Schild\Config'    => 'namespace Config',
            "use BlitzPHP\\Config\\BaseConfig;\n" => '',
            'extends BaseConfig'                     => 'extends \\BlitzPHP\\Schild\\Config\\Auth',
        ];
        $output = $replacer->replace($content, $replaces);

        $expected = <<<'FILE'
            <?php

            namespace Config;

            use BlitzPHP\Schild\Models\UserModel;

            class Auth extends \BlitzPHP\Schild\Config\Auth
            {
            FILE;

		expect($output)->toBe($expected);
	});

	it('Ajoute apres le texte', function (): void {
		$replacer = new ContentReplacer();
        $content  = <<<'FILE'
            $routes->get('/', 'Home::index');
            $routes->get('/login', 'Login::index');

            FILE;

        $line   = "\n" . 'service(\'auth\')->routes($routes);';
        $after  = '$routes->';
        $result = $replacer->addAfter($content, $line, $after);

        $expected = <<<'FILE'
            $routes->get('/', 'Home::index');
            $routes->get('/login', 'Login::index');

            service('auth')->routes($routes);

            FILE;

		expect($result)->toBe($expected);
	});

	it('addAfter est deja modifier', function (): void {
		$replacer = new ContentReplacer();
        $content  = <<<'FILE'
            $routes->get('/', 'Home::index');
            $routes->get('/login', 'Login::index');

            service('auth')->routes($routes);

            FILE;

        $line   = "\n" . 'service(\'auth\')->routes($routes);';
        $after  = '$routes->';
        $result = $replacer->addAfter($content, $line, $after);

		expect($result)->toBeNull();
	});

	it('Ajoute avant le texte', function (): void {
		$replacer = new ContentReplacer();
        $content  = <<<'FILE'
            <?php

            // Do Not Edit This Line
            parent::initController($request, $response, $logger);
            // Do Not Edit This Line

            FILE;

        $line   = '$this->helpers = array_merge($this->helpers, [\'auth\', \'setting\']);';
        $before = '// Do Not Edit This Line';
        $result = $replacer->addBefore($content, $line, $before);

        $expected = <<<'FILE'
            <?php

            $this->helpers = array_merge($this->helpers, ['auth', 'setting']);
            // Do Not Edit This Line
            parent::initController($request, $response, $logger);
            // Do Not Edit This Line

            FILE;

		expect($result)->toBe($expected);
	});

	it('addBefore est deja modifier', function (): void {
		$replacer = new ContentReplacer();
        $content  = <<<'FILE'
            <?php

            $this->helpers = array_merge($this->helpers, ['auth', 'setting']);
            // Do Not Edit This Line
            parent::initController($request, $response, $logger);
            // Do Not Edit This Line

            FILE;

        $line   = '$this->helpers = array_merge($this->helpers, [\'auth\', \'setting\']);';
        $before = '// Do Not Edit This Line';
        $result = $replacer->addBefore($content, $line, $before);

		expect($result)->toBeNull();
	});
});
