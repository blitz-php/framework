<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
use BlitzPHP\Exceptions\ValidationException;
use GuzzleHttp\Psr7\Uri;
use BlitzPHP\Http\Request;
use BlitzPHP\Filesystem\Files\UploadedFile;
use BlitzPHP\Validation\DataValidation;
use Dimtrovich\Validation\ValidatedInput;

use function Kahlan\expect;

describe('Http / Request', function (): void {
    describe('InteractsWithInput Trait', function (): void {
        it('Récupère un input', function (): void {
			$request = new Request(['post' => ['name' => 'John']]);
            expect($request->input('name'))->toBe('John');
            expect($request->input('missing', 'default'))->toBe('default');
        });

        it('Récupère tous les inputs', function (): void {
            $request = new Request(['post' => ['id' => 1, 'email' => 'test@example.com']]);
            expect($request->input())->toEqual(['id' => 1, 'email' => 'test@example.com']);
        });

        it('Détermine si un input existe', function (): void {
            $request = new Request(['post' => ['key' => 'value']]);
            expect($request->has('key'))->toBeTruthy();
            expect($request->has('missing'))->toBeFalsy();
        });

        it('Récupère un header', function (): void {
            $request = new Request();
            $request = $request->withEnv('HTTP_AUTHORIZATION', 'Bearer token123');
            expect($request->header('Authorization'))->toBe('Bearer token123');
        });

        it('Extrait le bearer token', function (): void {
            $request = new Request();
            $request = $request->withEnv('HTTP_AUTHORIZATION', 'Bearer token123');
            expect($request->bearerToken())->toBe('token123');

            $request = new Request();
            $request = $request->withEnv('HTTP_AUTHORIZATION', 'Basic auth');
            expect($request->bearerToken())->toBeNull();
        });

        it('Récupère un cookie', function (): void {
            $request = new Request(['cookies' => ['session' => 'abc123']]);
            expect($request->cookie('session'))->toBe('abc123');
        });

        it('Récupère un fichier', function (): void {
            $file = new UploadedFile(__FILE__, 0, UPLOAD_ERR_OK, 'test.txt', 'text/plain');
            $request = new Request(['files' => ['upload' => $file]]);
            expect($request->file('upload'))->toEqual($file);
            expect($request->hasFile('upload'))->toBeTruthy();
            expect($request->hasFile('missing'))->toBeFalsy();
        });

        it('Merge inputs', function (): void {
            $request = new Request(['post' => ['a' => 1]]);
            $merged = $request->merge(['b' => 2]);
            expect($merged->input())->toEqual(['a' => 1, 'b' => 2]);
        });

        it('Replace inputs', function (): void {
            $request = new Request(['post' => ['old' => 'value']]);
            $replaced = $request->replace(['new' => 'value']);
            expect($replaced->input())->toEqual(['new' => 'value']);
        });

        it('Only et except', function (): void {
            $request = new Request(['post' => ['a' => 1, 'b' => 2, 'c' => 3]]);
            expect($request->only('a', 'b'))->toEqual(['a' => 1, 'b' => 2]);
            expect($request->except('a'))->toEqual(['b' => 2, 'c' => 3]);
        });

        it('Keys des inputs', function (): void {
            $request = new Request(['post' => ['name' => 'John', 'age' => 30]]);
            expect($request->keys())->toEqual(['name', 'age']);
        });

        it('All avec keys', function (): void {
            $request = new Request(['post' => ['a' => 1, 'b' => 2]]);
            expect($request->all('a'))->toEqual(['a' => 1]);
        });

        it('Post input', function (): void {
            $request = new Request(['post' => ['input' => 'data']]);
            expect($request->post('input'))->toBe('data');
        });

        it('Server var', function (): void {
            $request = new Request();
            $request = $request->withEnv('SERVER_NAME', 'example.com');
            expect($request->server('SERVER_NAME'))->toBe('example.com');
        });

        it('Has header', function (): void {
            $request = new Request();
            $request = $request->withEnv('CONTENT_TYPE', 'application/json');
            expect($request->hasHeader('Content-Type'))->toBeTruthy();
            expect($request->hasHeader('Nonexistent'))->toBeFalsy();
        });

        it('Input nested', function (): void {
            $request = new Request(['post' => ['user' => ['name' => 'John']]]);
            expect($request->input('user.name'))->toBe('John');
        });

        it('Input vide', function (): void {
            $request = new Request();
            expect($request->input('empty'))->toBeNull();
            expect($request->has('empty'))->toBeFalsy();
        });
    });

    describe('InteractsWithContentTypes Trait', function (): void {
        it('Détecte JSON', function (): void {
            $request = new Request();
            $request = $request->withEnv('CONTENT_TYPE', 'application/json');
            expect($request->isJson())->toBeTruthy();

            $request = new Request();
            $request = $request->withEnv('CONTENT_TYPE', 'text/plain');
            expect($request->isJson())->toBeFalsy();
        });

        it('Expects JSON', function (): void {
            $request = new Request();
            $request = $request->withEnv('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest')
                              ->withEnv('HTTP_ACCEPT', 'application/json');
            expect($request->expectsJson())->toBeTruthy();
        });

        it('Wants JSON', function (): void {
            $request = new Request();
            $request = $request->withEnv('HTTP_ACCEPT', 'application/json');
            expect($request->wantsJson())->toBeTruthy();
        });

        xit('Prefers content type', function (): void {
            $request = new Request();
            $request = $request->withEnv('HTTP_ACCEPT', 'application/json, text/html');
            expect($request->prefers(['application/json', 'text/xml']))->toBe('application/json');
        });

        it('Accepte n\'importe quel type', function (): void {
            $request = new Request();
            $request = $request->withEnv('HTTP_ACCEPT', '*/*');
            expect($request->acceptsAnyContentType())->toBeTruthy();
        });

        xit('Format par défaut', function (): void {
            $request = new Request();
            $request = $request->withEnv('HTTP_ACCEPT', 'text/html');
            expect($request->format())->toBe('html');
        });

        it('Accepte JSON', function (): void {
            $request = new Request();
            $request = $request->withEnv('HTTP_ACCEPT', 'application/json');
            expect($request->acceptsJson())->toBeTruthy();
        });

        it('Accepte HTML', function (): void {
            $request = new Request();
            $request = $request->withEnv('HTTP_ACCEPT', 'text/html');
            expect($request->acceptsHtml())->toBeTruthy();
        });

        xit('Matches type exact', function (): void {
            // expect(InteractsWithContentTypes::matchesType('application/json', 'application/json'))->toBeTruthy();
            // expect(InteractsWithContentTypes::matchesType('application/json', 'text/plain'))->toBeFalsy();
        });

        xit('Prefers avec wildcard', function (): void {
            $request = new Request();
            $request = $request->withEnv('HTTP_ACCEPT', 'text/*');
            expect($request->prefers(['text/html', 'application/json']))->toBe('text/html');
        });

        it('Wants JSON avec +json', function (): void {
            $request = new Request();
            $request = $request->withEnv('HTTP_ACCEPT', 'application/problem+json');
            expect($request->wantsJson())->toBeTruthy();
        });
    });

    describe('InteractsWithFlashData Trait', function (): void {
        beforeEach(function (): void {
            $this->session =  session();
            $this->request = new Request();
            $this->request->setSession($this->session);
        });

        it('Old input', function (): void {
            $this->session->flashInput(['name' => 'John']);
            expect($this->request->old('name'))->toBe('John');
            expect($this->request->old('missing', 'default'))->toBe('default');
        });

        it('Flash inputs', function (): void {
            $this->request = new Request(['post' => ['test' => 'data']]);
            $this->request->flash();
            expect($this->session->getOldInput('test'))->toBe('data');
        });

        it('Flash only', function (): void {
            $this->request = new Request(['post' => ['a' => 1, 'b' => 2]]);
            $this->request->flashOnly('a');
            expect($this->session->getOldInput())->toEqual(['a' => 1]);
        });

        it('Flash except', function (): void {
            $this->request = new Request(['post' => ['a' => 1, 'b' => 2]]);
            $this->request->flashExcept('a');
            expect($this->session->getOldInput())->toEqual(['b' => 2]);
        });

        it('Flush old inputs', function (): void {
            $this->session->flashInput(['key' => 'value']);
            $this->request->flush();
            expect($this->session->getOldInput())->toBeEmpty();
        });

        it('Old avec Model default', function (): void {
            $model = new class() { public function getAttribute($key) { return 'attr'; } };
            $this->session->flashInput(['id' => 1]);
            expect($this->request->old('id', $model))->toBe(1);
        });

        it('Flash sans session', function (): void {
            $request = new Request();
            $request->flash(); // No error
        });

        it('Flash array keys', function (): void {
            $this->request = new Request(['post' => ['users' => ['name' => 'John']]]);
            $this->request->flashOnly('users');
            expect($this->session->getOldInput('users.name'))->toBe('John');
        });
    });

    describe('Validation', function (): void {
        it('Valide avec règles simples', function (): void {
            $request = new Request(['post' => ['email' => 'test@example.com']]);
            $validated = $request->validate(['email' => 'required|email']);
            expect($validated)->toBeAnInstanceOf(ValidatedInput::class);
            expect($validated['email'])->toBe('test@example.com');
        });

        it('Lève exception sur validation échouée', function (): void {
            $request = new Request(['data' => ['email' => 'invalid']]);
            expect(fn(): ValidatedInput => $request->validate(['email' => 'required|email']))
                ->toThrow(new ValidationException());
        });

        xit('Valide avec classe DataValidation', function (): void {
            $validationClass = new class() extends DataValidation {
                public function rules(): array { return ['field' => 'required']; }
            };
            $request = new Request(['post' => ['field' => 'value']]);
            // $validated = $request->validate($validationClass);
            // expect($validated['field'])->toBe('value');
        });

        it('Valide avec messages custom', function (): void {
            $request = new Request(['post' => ['email' => 'test@example.com']]);
            $validated = $request->validate(['email' => 'required|email'], ['email.required' => 'Custom msg']);
            // Assume no errors
            expect($validated['email'])->toBe('test@example.com');
        });

        it('Valide nested data', function (): void {
            $request = new Request(['post' => ['user' => ['email' => 'test@example.com']]]);
            $validated = $request->validate(['user.email' => 'required|email']);

            expect($validated['user']['email'])->toBe('test@example.com');
        });

        xit('Valide avec ignore ID pour unique', function (): void {
            $request = new Request(['post' => ['id' => 1, 'email' => 'test@example.com']]);
            $validated = $request->validate(DataValidation::class, ['id' => 1]); // Assume class uses ID
            // Coverage for attributes passed
        });
    });

    describe('Autres méthodes Request', function (): void {
        it('User agent', function (): void {
            $request = new Request();
            $request = $request->withEnv('HTTP_USER_AGENT', 'Chrome');
            expect((string) $request->userAgent())->toBe('Chrome');
        });

        it('Merge if missing', function (): void {
            $request = new Request(['post' => ['a' => 1]]);
            $merged = $request->mergeIfMissing(['a' => 2, 'b' => 3]);
            expect($merged->input('a'))->toBe(1); // Not overwritten
            expect($merged->input('b'))->toBe(3);
        });

        it('To array', function (): void {
            $request = new Request(['post' => ['key' => 'value']]);
            expect($request->toArray())->toEqual(['key' => 'value']);
        });

        it('Array access', function (): void {
            $request = new Request(['post' => ['key' => 'value']]);
            expect(isset($request['key']))->toBeTruthy();
            expect($request['key'])->toBe('value');
            $request['new'] = 'test';
            expect($request['new'])->toBe('test');
            unset($request['key']);
            expect(isset($request['key']))->toBeFalsy();
        });

        xit('Session handling', function (): void {
            // $session = new Store('test');
            // $request = new Request();
            // $request->setSession($session);
            // expect($request->hasSession())->toBeTruthy();
        });
    });

    describe('URI et Path', function (): void {
        it('Récupère le scheme', function (): void {
            $request = new Request();
            $request = $request->withUri(new Uri('https://example.com'));
            expect($request->getScheme())->toBe('https');
        });

        it('Récupère le host', function (): void {
            $request = new Request();
            $request = $request->withUri(new Uri('https://example.com'));
            expect($request->getHost())->toBe('example.com');
        });

        it('Récupère le port', function (): void {
            $request = new Request();
            $request = $request->withUri(new Uri('https://example.com:8443'));
            expect($request->getPort())->toBe(8443);
        });

        it('Récupère le request target', function (): void {
            $request = new Request();
            $request = $request->withUri(new Uri('https://example.com/path?query=1'));
            expect($request->getRequestTarget())->toBe('/path?query=1');
        });

        it('Récupère le path', function (): void {
            $request = new Request();
            $request = $request->withRequestTarget('/path?query=1');
            expect($request->getPath())->toBe('/path');
        });
    });
});
