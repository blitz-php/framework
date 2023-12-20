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
use BlitzPHP\Exceptions\HttpException;
use BlitzPHP\Exceptions\LoadException;
use BlitzPHP\Formatter\Formatter;
use BlitzPHP\Http\Response;
use BlitzPHP\Session\Cookie\Cookie;
use BlitzPHP\Spec\ReflectionHelper;

describe('Http / Response', function () {
	describe('Constructeur', function () {
		it('Le constructeur fonctionne', function () {
			$response = new Response();
			expect((string) $response->getBody())->toBe('');
			expect($response->getCharset())->toBe('UTF-8');
			expect($response->getType())->toBe('text/html');
			expect($response->getHeaderLine('Content-Type'))->toBe('text/html; charset=UTF-8');
			expect($response->getStatusCode())->toBe(200);

			$options = [
				'body' => 'This is the body',
				'charset' => 'my-custom-charset',
				'type' => 'mp3',
				'status' => 203,
			];
			$response = new Response($options);
			expect((string) $response->getBody())->toBe($options['body']);
			expect($response->getCharset())->toBe($options['charset']);
			expect($response->getType())->toBe('audio/mpeg');
			expect($response->getHeaderLine('Content-Type'))->toBe('audio/mpeg');
			expect($response->getStatusCode())->toBe($options['status']);
		});
	});

	describe('Types', function() {
		it('GetType', function() {
			$response = new Response();

			expect($response->getType())->toBe('text/html');
			expect($response->withType('pdf')->getType())->toBe('application/pdf');
			expect($response->withType('custom/stuff')->getType())->toBe('custom/stuff');
			expect($response->withType('json')->getType())->toBe('application/json');
		});

		it('SetTypeMap', function() {
			$response = new Response();
			$response->setTypeMap('ical', 'text/calendar');
			expect($response->withType('ical')->getType())->toBe('text/calendar');

			$response = new Response();
			$response->setTypeMap('ical', ['text/calendar']);
			expect($response->withType('ical')->getType())->toBe('text/calendar');
		});

		it('WithTypeAlias', function() {
			$response = new Response();

			// Le type de contenu par défaut doit correspondre
			expect($response->getHeaderLine('Content-Type'))->toBe('text/html; charset=UTF-8');

			$new = $response->withType('pdf');
			// Doit être une nouvelle instance
			expect($new)->not->toBe($response);

			// L'objet original ne doit pas être modifié
			expect($response->getHeaderLine('Content-Type'))->toBe('text/html; charset=UTF-8');

			expect($new->getHeaderLine('Content-Type'))->toBe('application/pdf');

			$json = $new->withType('json');
        	expect($json->getHeaderLine('Content-Type'))->toBe('application/json');
        	expect($json->getType())->toBe('application/json');
		});

		it('WithTypeFull', function() {
			$response = new Response();

			// Ne doit pas ajouter de jeu de caractères à un type explicite
			expect($response->withType('application/json')->getHeaderLine('Content-Type'))
				->toBe('application/json');

			// Doit permettre des types arbitraires
			expect($response->withType('custom/stuff')->getHeaderLine('Content-Type'))
				->toBe('custom/stuff');

			// Doit autoriser les types de jeux de caractères
			expect($response->withType('text/html; charset=UTF-8')->getHeaderLine('Content-Type'))
				->toBe('text/html; charset=UTF-8');
		});

		it('Un type invalide leve une exception', function() {
			$response = new Response();

			expect(fn() => $response->withType('beans'))
				->toThrow(new InvalidArgumentException('`beans` est un content type invalide.'));
		});

		it('MapType', function() {
			$response = new Response();

			expect($response->mapType('audio/x-wav'))->toBe('wav');
			expect($response->mapType('application/pdf'))->toBe('pdf');
			expect($response->mapType('text/xml'))->toBe('xml');
			expect($response->mapType('*/*'))->toBe('html');
			expect($response->mapType('application/vnd.ms-excel'))->toBe('csv');

	        $expected = ['json', 'xhtml', 'css'];
        	$result = $response->mapType(['application/json', 'application/xhtml+xml', 'text/css']);
        	expect($result)->toBe($expected);
		});
	});

    describe('Status code', function () {
        it('Modification du statut code', function () {
            $response = new Response();
            $response = $response->withStatus(200);

            expect($response->getStatusCode())->toBe(200);
        });

        it('Status code leve une erreur lorsque le code est invalide', function () {
            $response = new Response();

            expect(static fn () => $response->withStatus(54322))->toThrow(new HttpException());
        });

        it('Status code modifie la raison', function () {
            $response = new Response();
            $response = $response->withStatus(200);

            expect($response->getReasonPhrase())->toBe('OK');
        });

        it('Status code modifie une raison personnalisee', function () {
            $response = new Response();
            $response = $response->withStatus(200, 'Not the right person');

            expect($response->getReasonPhrase())->toBe('Not the right person');
        });

        it('Erreur lorsque le statut code est inconnue', function () {
            $response = new Response();

            expect(static fn () => $response->withStatus(115))->toThrow(new HttpException(lang('HTTP.unknownStatusCode', [115])));
        });

        it('Erreur lorsque le statut code est petit', function () {
            $response = new Response();

            expect(static fn () => $response->withStatus(95))->toThrow(new HttpException(lang('HTTP.invalidStatusCode', [95])));
        });

        it('Erreur lorsque le statut code est grand', function () {
            $response = new Response();

            expect(static fn () => $response->withStatus(695))->toThrow(new HttpException(lang('HTTP.invalidStatusCode', [695])));
        });

        it('Raison avec le statut different de 200', function () {
            $response = new Response();
            $response = $response->withStatus(300, 'Multiple Choices');

            expect($response->getReasonPhrase())->toBe('Multiple Choices');
        });

        it('Raison personnalisee avec un statut different de 200', function () {
            $response = new Response();
            $response = $response->withStatus(300, 'My Little Pony');

            expect($response->getReasonPhrase())->toBe('My Little Pony');
        });

		it('WithStatus efface le content type', function () {
			$response = new Response();
        	$new = $response->withType('pdf')->withStatus(204);

			expect($new->hasHeader('Content-Type'))->toBeFalsy();
        	expect($new->getType())->toBe('');
        	expect($new->getStatusCode())->toBe(204); // Le code d'état doit effacer le type de contenu;

			$response = new Response();
        	$new = $response->withStatus(304)->withType('pdf');

        	expect($new->getType())->toBe('');
			expect($new->hasHeader('Content-Type'))->toBeFalsy(); // Le type ne doit pas être conservé en raison du code d'état.

			$response = new Response();
        	$new = $response->withHeader('Content-Type', 'application/json')->withStatus(204);

			expect($new->hasHeader('Content-Type'))->toBeFalsy(); // L'en-tête direct doit être dégagé
        	expect($new->getType())->toBe('');
		});

		it("WithStatus n'efface pas le content type lorsqu'il passe par withHeader", function () {
			$response = new Response();
        	$new = $response->withHeader('Content-Type', 'application/json')->withStatus(403);

			expect($new->getHeaderLine('Content-Type'))->toBe('application/json');
        	expect($new->getStatusCode())->toBe(403);

			$response = new Response();
        	$new = $response->withStatus(403)->withHeader('Content-Type', 'application/json');

			expect($new->getHeaderLine('Content-Type'))->toBe('application/json');
        	expect($new->getStatusCode())->toBe(403);
			expect($new->getType())->toBe('application/json');
		});
    });

    describe('Redirection', function () {
        it('Redirection simple', function () {
            $response = new Response();

            $response = $response->redirect('example.com');

            expect($response->hasHeader('Location'))->toBeTruthy();
            expect($response->getHeaderLine('Location'))->toBe('example.com');
            expect($response->getStatusCode())->toBe(302);
        });

        it('Redirection temporaire', function () {
            $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
            $_SERVER['REQUEST_METHOD']  = 'POST';
            $response                   = new Response();

            $response = $response
                ->withProtocolVersion('HTTP/1.1')
                ->redirect('/foo');

            expect($response->getStatusCode())->toBe(303);
        });

        xit('Redirection temporaire avec la methode GET', function () {
            $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
            $_SERVER['REQUEST_METHOD']  = 'GET';
            $response                   = new Response();

            $response = $response
                ->withProtocolVersion('HTTP/1.1')
                ->redirect('/foo');

            expect($response->getStatusCode())->toBe(302);
        });
    });

    describe('Cookie', function () {
        it('hasCookie', function () {
            $response = new Response();

            expect($response->hasCookie('foo'))->toBeFalsy();
        });

        it('setCookie', function () {
            $response = new Response();
            $response = $response->withCookie(new Cookie('foo', 'bar'));

            expect($response->hasCookie('foo'))->toBeTruthy();
            expect($response->hasCookie('foo', 'bar'))->toBeTruthy();
        });

        it('Redirection avec cookie', function () {
            $loginTime = (string) time();

            $response = new Response();
            $answer1  = $response
                ->cookie('foo', 'bar', YEAR)
                ->cookie('login_time', $loginTime, YEAR);

            expect($answer1->hasCookie('foo'))->toBeTruthy();
            expect($answer1->hasCookie('login_time', $loginTime))->toBeTruthy();
        });
    });

    describe('JSON et XML', function () {
        it('JSON avec un tableau', function () {
            $body = [
                'foo' => 'bar',
                'bar' => [
                    1,
                    2,
                    3,
                ],
            ];
            $expected = Formatter::type('application/json')->format($body);

            $response = new Response();
            $response = $response->json($body);

            expect($response->content())->toBe($expected);
            expect($response->getHeaderLine('content-type'))->toMatch(static fn ($content) => str_contains($content, 'application/json'));
        });

        it('XML avec un tableau', function () {
            $body = [
                'foo' => 'bar',
                'bar' => [
                    1,
                    2,
                    3,
                ],
            ];
            $expected = Formatter::type('application/xml')->format($body);

            $response = new Response();
            $response = $response->xml($body);

            expect($response->content())->toBe($expected);
            expect($response->getHeaderLine('content-type'))->toMatch(static fn ($content) => str_contains($content, 'application/xml'));
        });
    });

    describe('Telechargement', function () {
        it('StreamDownload', function () {
            $response = new Response();
            $actual   = $response->streamDownload('data', 'unit-test.txt');

            expect($actual)->toBeAnInstanceOf(Response::class);
            expect($actual->getHeaderLine('Content-Disposition'))->toBe('attachment; filename="unit-test.txt"');

            $emitter = ReflectionHelper::getPrivateMethodInvoker(Services::emitter(), 'emitBody');

            expect(static fn () => $emitter($actual, 8192))->toEcho('data');
        });

        it('Download', function () {
			$response = new Response();
			$new = $response->withDownload('myfile.mp3');
			expect($response->hasHeader('Content-Disposition'))->toBeFalsy(); // Pas de mutation

			$expected = 'attachment; filename="myfile.mp3"';
			expect($new->getHeaderLine('Content-Disposition'))->toBe($expected);


            $response = new Response();
            $actual   = $response->download(__FILE__);

            expect($actual)->toBeAnInstanceOf(Response::class);
            expect($actual->getHeaderLine('Content-Disposition'))->toBe('attachment; filename="' . basename(__FILE__) . '"');

            $emitter = ReflectionHelper::getPrivateMethodInvoker(Services::emitter(), 'emitBody');

            expect(static fn () => $emitter($actual, 8192))->toEcho(file_get_contents(__FILE__));
        });

        it('Download avec un fichier innexistant', function () {
            $response = new Response();

            expect(static fn () => $response->download('__FILE__'))->toThrow(new LoadException());
        });
    });

    describe('With', function () {
        it('withDate', function () {
            $datetime = DateTime::createFromFormat('!Y-m-d', '2000-03-10');

            $response = new Response();
            $response = $response->withDate($datetime);

            $date = clone $datetime;
            $date->setTimezone(new DateTimeZone('UTC'));

            expect($response->getHeaderLine('Date'))
                ->toBe($date->format('D, d M Y H:i:s') . ' GMT');
        });

        it('withLink', function () {
            $response = new Response();

			expect($response->hasHeader('Link'))->toBeFalsy();

            $response = $response
                ->withAddedLink('http://example.com?page=1', ['rel' => 'prev'])
                ->withAddedLink('http://example.com?page=3', ['rel' => 'next']);

            expect($response->getHeader('Link'))->toBe([
                '<http://example.com?page=1>; rel="prev"',
                '<http://example.com?page=3>; rel="next"',
            ]);
        });

        it('withContentType', function () {
            $response = new Response();
            $response = $response->withType('text/json');

            expect($response->getHeaderLine('Content-Type'))->toBe('text/json; charset=UTF-8');
        });

        it('withCache', function () {
            $date   = date('r');
            $result = (new DateTime($date))->setTimezone(new DateTimeZone('UTC'))->format(DATE_RFC7231);

            $response = new Response();
            $response = $response->withCache($date, '+1 day');

            expect($response->getHeaderLine('Last-Modified'))->toBe($result);
            expect($response->getHeaderLine('Cache-Control'))->toMatch(static fn ($value) => str_contains($value, 'public, max-age='));


			$response = new Response();
        	$since = $time = time();

			$new = $response->withCache($since, $time);
			expect($response->hasHeader('Date'))->toBeFalsy();
			expect($response->hasHeader('Last-Modified'))->toBeFalsy();

			expect($new->getHeaderLine('Date'))->toBe(gmdate(DATE_RFC7231, $since));
			expect($new->getHeaderLine('Last-Modified'))->toBe(gmdate(DATE_RFC7231, $since));
			expect($new->getHeaderLine('Expires'))->toBe(gmdate(DATE_RFC7231, $time));
			expect($new->getHeaderLine('Cache-Control'))->toBe('public, max-age=0');
        });

        it('withDisabledCache', function () {
            $response = new Response();
			$expected = [
				'Content-Type'  => ['text/html; charset=UTF-8'],
				'Expires'       => ['Mon, 26 Jul 1997 05:00:00 GMT'],
				'Last-Modified' => [gmdate(DATE_RFC7231)],
				'Cache-Control' => ['no-store, no-cache, must-revalidate, post-check=0, pre-check=0'],
			];

			$new = $response->withDisabledCache();
			expect($response->hasHeader('Expires'))->toBeFalsy(); // Ancienne instance non mutée.

			expect($new->getHeaders())->toBe($expected);
        });

        it('withCharset', function () {
            $response = new Response();
            expect($response->getHeaderLine('Content-Type'))->toBe('text/html; charset=UTF-8');

			$new = $response->withCharset('iso-8859-1');
			// L'ancienne instance n'a pas été modifiée
            expect($response->getHeaderLine('Content-Type'))->toMatch(fn($actual) => ! str_contains($actual, 'iso'));
            expect($new->getCharset())->toBe('iso-8859-1');
            expect($new->getHeaderLine('Content-Type'))->toBe('text/html; charset=iso-8859-1');
        });

        it('withLength', function () {
            $response = new Response();
            expect($response->hasHeader('Content-Length'))->toBeFalsy();

			$new = $response->withLength(100);
			// L'ancienne instance n'a pas été modifiée
            expect($response->hasHeader('Content-Length'))->toBeFalsy();
            expect($new->getHeaderLine('Content-Length'))->toBe('100');
        });

        it('withExpires', function () {
            $response = new Response();
			$now = new DateTime('now', new DateTimeZone('Africa/Douala'));

			$new = $response->withExpires($now);
            expect($response->hasHeader('Expires'))->toBeFalsy();

			$now->setTimeZone(new DateTimeZone('UTC'));
            expect($new->getHeaderLine('Expires'))->toBe($now->format(DATE_RFC7231));

			$now = time();
        	$new = $response->withExpires($now);
			expect($new->getHeaderLine('Expires'))->toBe(gmdate(DATE_RFC7231));

			$time = new DateTime('+1 day', new DateTimeZone('UTC'));
			$new = $response->withExpires('+1 day');
			expect($new->getHeaderLine('Expires'))->toBe($time->format(DATE_RFC7231));
        });

        it('withModified', function () {
            $response = new Response();
			$now = new DateTime('now', new DateTimeZone('Africa/Douala'));

			$new = $response->withModified($now);
            expect($response->hasHeader('Last-Modified'))->toBeFalsy();

			$now->setTimeZone(new DateTimeZone('UTC'));
            expect($new->getHeaderLine('Last-Modified'))->toBe($now->format(DATE_RFC7231));

			$now = time();
        	$new = $response->withModified($now);
			expect($new->getHeaderLine('Last-Modified'))->toBe(gmdate(DATE_RFC7231));

			$now = new DateTimeImmutable();
			$new = $response->withModified($now);
			expect($new->getHeaderLine('Last-Modified'))->toBe(gmdate(DATE_RFC7231, $now->getTimestamp()));

			$time = new DateTime('+1 day', new DateTimeZone('UTC'));
			$new = $response->withModified('+1 day');
			expect($new->getHeaderLine('Last-Modified'))->toBe($time->format(DATE_RFC7231));
        });

		it('withSharable', function () {
			$response = new Response();
			$new = $response->withSharable(true);

			expect($response->hasHeader('Cache-Control'))->toBeFalsy();
			expect($new->getHeaderLine('Cache-Control'))->toBe('public');

			$new = $response->withSharable(false);
			expect($new->getHeaderLine('Cache-Control'))->toBe('private');

			$new = $response->withSharable(true, 3600);
			expect($new->getHeaderLine('Cache-Control'))->toBe('public, max-age=3600');

			$new = $response->withSharable(false, 3600);
			expect($new->getHeaderLine('Cache-Control'))->toBe('private, max-age=3600');
		});

		it('withMaxAge', function () {
			$response = new Response();

			expect($response->hasHeader('Cache-Control'))->toBeFalsy();

			$new = $response->withMaxAge(3600);
			expect($new->getHeaderLine('Cache-Control'))->toBe('max-age=3600');

			$new = $response->withMaxAge(3600)->withSharable(false);
			expect($new->getHeaderLine('Cache-Control'))->toBe('max-age=3600, private');
		});

		it('withSharedMaxAge', function () {
			$response = new Response();
			$new = $response->withSharedMaxAge(3600);

			expect($response->hasHeader('Cache-Control'))->toBeFalsy();
			expect($new->getHeaderLine('Cache-Control'))->toBe('s-maxage=3600');

			$new = $response->withSharedMaxAge(3600)->withSharable(true);
			expect($new->getHeaderLine('Cache-Control'))->toBe('s-maxage=3600, public');
		});

		it('withMustRevalidate', function () {
			$response = new Response();

			expect($response->hasHeader('Cache-Control'))->toBeFalsy();

			$new = $response->withMustRevalidate(true);
			expect($response->hasHeader('Cache-Control'))->toBeFalsy();
			expect($new->getHeaderLine('Cache-Control'))->toBe('must-revalidate');

			$new = $response->withMustRevalidate(false);
			expect($new->getHeaderLine('Cache-Control'))->toBeEmpty();
		});

		it('withVary', function () {
			$response = new Response();
			$new = $response->withVary('Accept-encoding');

			expect($response->hasHeader('Vary'))->toBeFalsy();
			expect($new->getHeaderLine('Vary'))->toBe('Accept-encoding');

			$new = $response->withVary(['Accept-encoding', 'Accept-language']);
			expect($response->hasHeader('Vary'))->toBeFalsy();
			expect($new->getHeaderLine('Vary'))->toBe('Accept-encoding, Accept-language');
		});

		it('withEtag', function () {
			$response = new Response();
			$new = $response->withEtag('something');

			expect($response->hasHeader('Etag'))->toBeFalsy();
			expect($new->getHeaderLine('Etag'))->toBe('"something"');

			$new = $response->withEtag('something', true);
			expect($new->getHeaderLine('Etag'))->toBe('W/"something"');
		});

		it('withNotModified', function () {
			$response = new Response(['body' => 'something']);
			$response = $response->withLength(100)
				->withStatus(200)
				->withHeader('Last-Modified', 'value')
				->withHeader('Content-Language', 'en-EN')
				->withHeader('X-things', 'things')
				->withType('application/json');

			$new = $response->withNotModified();

			expect($response->hasHeader('Content-Language'))->toBeTruthy();
			expect($response->hasHeader('Content-Length'))->toBeTruthy();

			expect($new->hasHeader('Content-Type'))->toBeFalsy();
			expect($new->hasHeader('Content-Length'))->toBeFalsy();
			expect($new->hasHeader('Content-Language'))->toBeFalsy();
			expect($new->hasHeader('Last-Modified'))->toBeFalsy();

			expect($new->getHeaderLine('X-things'))->toBe('things'); // Les autres headers sont conserves
			expect($new->getStatusCode())->toBe(304);
			expect($new->getBody()->getContents())->toBe('');
		});
    });

	describe('Autres', function () {
		it('Compression', function () {
			$response = new Response();

			if (ini_get('zlib.output_compression') === '1' || !extension_loaded('zlib')) {
				expect($response->compress())->toBeFalsy();
				skipIf(true);
			}

			$_SERVER['HTTP_ACCEPT_ENCODING'] = '';
        	$result = $response->compress();
			expect($result)->toBeFalsy();

			$_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip';
	        $result = $response->compress();
			expect($result)->toBeTruthy();
			expect(ob_list_handlers())->toContain('ob_gzhandler');

	        ob_get_clean();
		});

		it('Sortie compressee', function () {
			$response = new Response();

			$_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip';
			expect($response->outputCompressed())->toBeFalsy();

			$_SERVER['HTTP_ACCEPT_ENCODING'] = '';
			expect($response->outputCompressed())->toBeFalsy();

			skipIf(!extension_loaded('zlib'));

			if (ini_get('zlib.output_compression') !== '1') {
				ob_start('ob_gzhandler');
			}
			$_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip';

        	expect($response->outputCompressed())->toBeTruthy();

			$_SERVER['HTTP_ACCEPT_ENCODING'] = '';
	        expect($response->outputCompressed())->toBeFalsy();

			if (ini_get('zlib.output_compression') !== '1') {
				ob_get_clean();
			}
		});
	});
});
