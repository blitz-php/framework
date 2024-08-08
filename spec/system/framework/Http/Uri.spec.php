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
use BlitzPHP\Http\Uri;

describe('Http / URI', function (): void {
	describe('Uri', function (): void {
		it("Teste si le constructeur definie toutes les parties", function (): void {
			$uri = new Uri('http://username:password@hostname:9090/path?arg=value#anchor');

			expect($uri->getScheme())->toBe('http');
			expect($uri->getUserInfo())->toBe('username');
			expect($uri->getHost())->toBe('hostname');
			expect($uri->getPath())->toBe('/path');
			expect($uri->getQuery())->toBe('arg=value');
			expect($uri->getPort())->toBe(9090);
			expect($uri->getFragment())->toBe('anchor');
			expect($uri->getSegments())->toBe(['path']);

			// Mot de passe ignoré par défaut pour des raisons de sécurité.
			expect($uri->getAuthority())->toBe('username@hostname:9090');
		});

		it("Teste si Segments est rempli correctement pour plusieurs segments", function (): void {
			$uri = new Uri('http://hostname/path/to/script');

			expect($uri->getSegments())->toBe(['path', 'to', 'script']);
			expect($uri->getSegment(1))->toBe('path');
			expect($uri->getSegment(2))->toBe('to');
			expect($uri->getSegment(3))->toBe('script');
			expect($uri->getSegment(4))->toBe('');

			expect($uri->getTotalSegments())->toBe(3);
		});

		it("Teste les segments hors limites", function (): void {
			$uri = new Uri('http://hostname/path/to/script');

			expect(fn() => $uri->getSegment(5))->toThrow(new HttpException());
		});

		it("Teste les segments hors limites avec une valeur par defaut", function (): void {
			$uri = new Uri('http://abc.com/a123/b/c');

			expect(fn() => $uri->getSegment(22, 'something'))->toThrow(new HttpException());
		});

		it("Teste si Segments est rempli avec les valeurs par defaut", function (): void {
			$uri = new Uri('http://hostname/path/to');

			expect($uri->getSegments())->toBe(['path', 'to']);
			expect($uri->getSegment(1))->toBe('path');
			expect($uri->getSegment(2, 'different'))->toBe('to');
			expect($uri->getSegment(3, 'script'))->toBe('script');
			expect($uri->getSegment(3))->toBe('');

			expect($uri->getTotalSegments())->toBe(2);
		});

		it("Teste si l'URI peut etre caster en string", function (): void {
			$url = 'http://username:password@hostname:9090/path?arg=value#anchor';
			$uri = new Uri($url);

			$expected = 'http://username@hostname:9090/path?arg=value#anchor';

			expect((string) $uri)->toBe($expected);
		});

		it("Teste les URI simple", function (): void {
			$urls = [
				[
					'http://example.com', // url
					'http://example.com', // expectedURL
					'',                   // expectedPath
				],
				['http://example.com/', 'http://example.com/', '/'],
				['http://example.com/one/two', 'http://example.com/one/two', '/one/two'],
				['http://example.com/one/two/', 'http://example.com/one/two/', '/one/two/'],
				['http://example.com/one/two//', 'http://example.com/one/two/', '/one/two/'],
				['http://example.com//one/two//', 'http://example.com/one/two/', '/one/two/'],
				['http://example.com//one//two//', 'http://example.com/one/two/', '/one/two/'],
				['http://example.com///one/two', 'http://example.com/one/two', '/one/two'],
				['http://example.com/one/two///', 'http://example.com/one/two/', '/one/two/'],
			];

			foreach ($urls as $u) {
				[$url, $expectedURL, $expectedPath] = $u;

				$uri = new Uri($url);

				expect((string) $uri)->toBe($expectedURL);
				expect($uri->getPath())->toBe($expectedPath);
			}
		});

		it('Teste les URL vide', function (): void {
			$url = '';
			$uri = new Uri($url);

			expect((string) $uri)->toBe('http://');

			$url = '/';
			$uri = new Uri($url);

			expect((string) $uri)->toBe('http://');
		});

		it('Teste les URL malformés', function (): void {
			$url = 'http://abc:a123';

			expect(fn() => new Uri($url))->toThrow(new HttpException());
		});

		it('Teste les schema manquant', function (): void {
			$url = 'http://foo.bar/baz';
			$uri = new Uri($url);

			expect($uri->getScheme())->toBe('http');
			expect($uri->getAuthority())->toBe('foo.bar');
			expect($uri->getPath())->toBe('/baz');
			expect((string) $uri)->toBe($url);
		});
	});

	describe('Getter et setter', function (): void {
		it('setScheme', function (): void {
			$url = 'http://example.com/path';
			$uri = new Uri($url);

			$uri->setScheme('https');

			expect($uri->getScheme())->toBe('https');
			$expected = 'https://example.com/path';
			expect((string) $uri)->toBe($expected);
		});

		it('withScheme', function (): void {
			$url = 'example.com';
			$uri = new Uri('http://' . $url);

			$new = $uri->withScheme('x');

			expect((string) $uri)->toBe('http://' . $url);
			expect((string) $new)->toBe('x://' . $url);
		});

		it('withScheme avec https', function (): void {
			$url = 'http://example.com/path';
			$uri = new Uri($url);

			$new = $uri->withScheme('https');

			expect($new->getScheme())->toBe('https');
			expect($uri->getScheme())->toBe('http');

			$expected = 'https://example.com/path';
			expect((string) $new)->toBe($expected);
			$expected = 'http://example.com/path';
			expect((string) $uri)->toBe($expected);
		});

		it('withScheme avec une valeur vide', function (): void {
			$url = 'example.com';
			$uri = new Uri('http://' . $url);

			$new = $uri->withScheme('');

			expect((string) $new)->toBe($url);
			expect((string) $uri)->toBe('http://' . $url);
		});

		it('setUserInfo', function (): void {
			$url = 'http://example.com/path';
			$uri = new Uri($url);

			$uri->setUserInfo('user', 'password');

			expect($uri->getUserInfo())->toBe('user');
			$expected = 'http://user@example.com/path';
			expect((string) $uri)->toBe($expected);
		});

		it('Teste si UserInfo peut afficher le password', function (): void {
			$url = 'http://example.com/path';
			$uri = new Uri($url);

			$uri->setUserInfo('user', 'password');

			expect($uri->getUserInfo())->toBe('user');
			$expected = 'http://user@example.com/path';
			expect((string) $uri)->toBe($expected);

			$uri->showPassword();
			expect($uri->getUserInfo())->toBe('user:password');
			$expected = 'http://user:password@example.com/path';
			expect((string) $uri)->toBe($expected);
		});

		it('setHost', function (): void {
			$url = 'http://example.com/path';
			$uri = new Uri($url);

			$uri->setHost('another.com');

			expect($uri->getHost())->toBe('another.com');
			$expected = 'http://another.com/path';
			expect((string) $uri)->toBe($expected);
		});

		it('setPort', function (): void {
			$url = 'http://example.com/path';
			$uri = new Uri($url);

			$uri->setPort(9000);

			expect($uri->getPort())->toBe(9000);
			$expected = 'http://example.com:9000/path';
			expect((string) $uri)->toBe($expected);
		});

		it('setPort avec une valeur invalide', function (): void {
			$url = 'http://example.com/path';
			$uri = new Uri($url);

			$ports = [70000, -1, 0];
			foreach ($ports as $port) {
				$errorString = lang('HTTP.invalidPort', [$port]);
				expect($errorString)->not->toBeEmpty();
				expect(fn() => $uri->setPort($port))
					->toThrow(new HttpException($errorString));
			}
		});

		it('setURI capture les mauvais port', function (): void {
			$url = 'http://username:password@hostname:90909/path?arg=value#anchor';
        	$uri = new Uri();

			expect(fn() => $uri->setURI($url))
				->toThrow(new HttpException());
		});
	});
});
