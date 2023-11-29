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

describe('Response', function () {
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
        });
    });
});
