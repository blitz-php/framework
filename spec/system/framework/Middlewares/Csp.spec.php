<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Http\Response;
use BlitzPHP\Http\ServerRequestFactory;
use BlitzPHP\Middlewares\Csp;
use ParagonIE\CSPBuilder\CSPBuilder;
use Spec\BlitzPHP\Middlewares\TestRequestHandler;

use function Kahlan\expect;

describe('Middleware / Csp', function (): void {
    beforeAll(function () {
        $this->getRequestHandler = function () {
            return new TestRequestHandler(function ($request) {
                return new Response();
            });
        };
    });

    it('Process ajoute les headers', function (): void {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/test']);

        $middleware = new Csp([
            'script-src' => [
                'allow' => [
                    'https://www.google-analytics.com',
                ],
                'self'          => true,
                'unsafe-inline' => false,
                'unsafe-eval'   => false,
            ],
        ]);

        $response = $middleware->process($request, $this->getRequestHandler());
        $policy = $response->getHeaderLine('Content-Security-Policy');

        $expected = "script-src 'self' https://www.google-analytics.com";
        
        expect(str_contains($policy, $expected))->toBeTruthy();
        expect(str_contains($policy, 'nonce-'))->toBeFalsy();
    });

    it('Process ajoute les attributs de requete pour nonces', function (): void {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/test']);
    
        $policy = [
            'script-src' => [
                'self'          => true,
                'unsafe-inline' => false,
                'unsafe-eval'   => false,
            ],
            'style-src' => [
                'self'          => true,
                'unsafe-inline' => false,
                'unsafe-eval'   => false,
            ],
        ];

        $middleware = new Csp($policy, [
            'script_nonce' => true,
            'style_nonce'  => true,
        ]);

        $handler = new TestRequestHandler(function ($request) {
            expect($request->getAttribute('cspScriptNonce'))->not->toBeEmpty();
            expect($request->getAttribute('cspStyleNonce'))->not->toBeEmpty();

            return new Response();
        });

        $response = $middleware->process($request, $handler);
        $policy = $response->getHeaderLine('Content-Security-Policy');
        $expected = [
            "script-src 'self' 'nonce-",
            "style-src 'self' 'nonce-",
        ];

        expect($policy)->not->toBeEmpty();

        foreach ($expected as $match) {
            expect(str_contains($policy, $match))->toBeTruthy();
        }
    });

    it('Passage d\'une instance CSPBuilder', function () {
        $request = ServerRequestFactory::fromGlobals(['REQUEST_URI' => '/test']);

        $config = [
            'script-src' => [
                'allow' => [
                    'https://www.google-analytics.com',
                ],
                'self'          => true,
                'unsafe-inline' => false,
                'unsafe-eval'   => false,
            ],
        ];

        $cspBuilder = new CSPBuilder($config);
        $middleware = new Csp($cspBuilder);

        $response = $middleware->process($request, $this->getRequestHandler());
        $policy = $response->getHeaderLine('Content-Security-Policy');
        $expected = "script-src 'self' https://www.google-analytics.com";

        expect(str_contains($policy, $expected))->toBeTruthy();
    });
});
