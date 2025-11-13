<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Session\Cookie\Cookie;

use function Kahlan\expect;

describe('Session / Cookie / Cookie', function (): void {
    beforeEach(function (): void {
        // Réinitialise les valeurs par défaut avant chaque test
        Cookie::setDefaults([
            'expires'  => null,
            'path'     => '/',
            'domain'   => '',
            'secure'   => false,
            'httponly' => false,
            'samesite' => null,
        ]);
    });

    describe('Création', function (): void {
        it('Devrait créer un cookie avec des valeurs basiques', function (): void {
            $cookie = new Cookie('test', 'value');

            expect($cookie->getName())->toBe('test');
            expect($cookie->getValue())->toBe('value');
            expect($cookie->getPath())->toBe('/');
            expect($cookie->isSecure())->toBe(false);
            expect($cookie->isHttpOnly())->toBe(false);
        });

        it('Devrait créer un cookie avec la méthode create()', function (): void {
            $cookie = Cookie::create('test', 'value', [
                'path'     => '/admin',
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

            expect($cookie->getName())->toBe('test');
            expect($cookie->getPath())->toBe('/admin');
            expect($cookie->isSecure())->toBe(true);
            expect($cookie->isHttpOnly())->toBe(true);
            expect($cookie->getSameSite())->toBe('Strict');
        });

        it('Devrait créer un cookie avec une date d\'expiration', function (): void {
            $expires = new DateTimeImmutable('+1 hour');
            $cookie = new Cookie('test', 'value', $expires);

            expect($cookie->getExpiry())->toBeAnInstanceOf(DateTimeInterface::class);
            expect($cookie->getExpiresTimestamp())->toBe($expires->getTimestamp());
        });

        it('Devrait créer un cookie avec expiration numérique', function (): void {
            $timestamp = time() + 3600;
            $cookie = Cookie::create('test', 'value', ['expires' => $timestamp]);

            expect($cookie->getExpiresTimestamp())->toBe($timestamp);
        });
    });

    describe('Validation', function (): void {
        it('Devrait rejeter un nom de cookie vide', function (): void {
            expect(function (): void {
                new Cookie('', 'value');
            })->toThrow(new InvalidArgumentException('Le nom du cookie ne peut pas être vide.'));
        });

        it('Devrait rejeter un nom de cookie avec caractères invalides', function (): void {
            expect(function (): void {
                new Cookie('test=value', 'value');
            })->toThrow(new InvalidArgumentException('Le nom du cookie "test=value" contient des caractères invalides.'));
        });

        it('Devrait rejeter une valeur SameSite invalide', function (): void {
            expect(function (): void {
                new Cookie('test', 'value', null, null, null, null, null, 'Invalid');
            })->toThrow(new InvalidArgumentException('Samesite value must be either of: Lax, Strict, None'));
        });
    });

    describe('Méthodes with* (immuabilité)', function (): void {
        it('Devrait créer une nouvelle instance avec withName()', function (): void {
            $original = new Cookie('original', 'value');
            $modified = $original->withName('modified');

            expect($original->getName())->toBe('original');
            expect($modified->getName())->toBe('modified');
            expect($original)->not->toBe($modified);
        });

        it('Devrait créer une nouvelle instance avec withValue()', function (): void {
            $original = new Cookie('test', 'original');
            $modified = $original->withValue('modified');

            expect($original->getValue())->toBe('original');
            expect($modified->getValue())->toBe('modified');
        });

        it('Devrait créer une nouvelle instance avec withPath()', function (): void {
            $original = new Cookie('test', 'value', null, '/original');
            $modified = $original->withPath('/modified');

            expect($original->getPath())->toBe('/original');
            expect($modified->getPath())->toBe('/modified');
        });

        it('Devrait créer une nouvelle instance avec withSecure()', function (): void {
            $original = new Cookie('test', 'value', null, null, null, false);
            $modified = $original->withSecure(true);

            expect($original->isSecure())->toBe(false);
            expect($modified->isSecure())->toBe(true);
        });

        it('Devrait créer une nouvelle instance avec withHttpOnly()', function (): void {
            $original = new Cookie('test', 'value', null, null, null, null, false);
            $modified = $original->withHttpOnly(true);

            expect($original->isHttpOnly())->toBe(false);
            expect($modified->isHttpOnly())->toBe(true);
        });

        it('Devrait créer une nouvelle instance avec withSameSite()', function (): void {
            $original = new Cookie('test', 'value', null, null, null, null, null, 'Lax');
            $modified = $original->withSameSite('Strict');

            expect($original->getSameSite())->toBe('Lax');
            expect($modified->getSameSite())->toBe('Strict');
        });

        it('Devrait créer une nouvelle instance avec withExpiry()', function (): void {
            $original = new Cookie('test', 'value', new DateTimeImmutable('+1 hour'));
            $newExpiry = new DateTimeImmutable('+2 hours');
            $modified = $original->withExpiry($newExpiry);

            expect($original->getExpiry()->getTimestamp())->toBeLessThan($modified->getExpiry()->getTimestamp());
        });
    });

    describe('Expiration', function (): void {
        it('Devrait détecter un cookie expiré', function (): void {
            $expired = new Cookie('test', 'value', new DateTimeImmutable('-1 hour'));
            expect($expired->isExpired())->toBe(true);
        });

        it('Devrait détecter un cookie non expiré', function (): void {
            $valid = new Cookie('test', 'value', new DateTimeImmutable('+1 hour'));
            expect($valid->isExpired())->toBe(false);
        });

        it('Devrait créer un cookie qui n\'expire jamais', function (): void {
            $cookie = (new Cookie('test', 'value'))->withNeverExpire();
            expect($cookie->isExpired())->toBe(false);
        });

        it('Devrait créer un cookie expiré', function (): void {
            $cookie = (new Cookie('test', 'value'))->withExpired();
            expect($cookie->isExpired())->toBe(true);
        });
    });

    describe('Valeurs complexes', function (): void {
        it('Devrait gérer les valeurs de tableau', function (): void {
            $data = ['user' => 'john', 'role' => 'admin'];
            $cookie = new Cookie('test', $data);

            expect($cookie->getValue())->toBe($data);
            expect($cookie->isExpanded())->toBe(true);
        });

        it('Devrait lire des valeurs imbriquées avec read()', function (): void {
            $data = ['user' => ['name' => 'john', 'role' => 'admin']];
            $cookie = new Cookie('test', $data);

            expect($cookie->read('user.name'))->toBe('john');
            expect($cookie->read('user.role'))->toBe('admin');
            expect($cookie->read('nonexistent'))->toBeNull();
        });

        it('Devrait vérifier l\'existence de valeurs avec check()', function (): void {
            $data = ['user' => ['name' => 'john']];
            $cookie = new Cookie('test', $data);

            expect($cookie->check('user.name'))->toBe(true);
            expect($cookie->check('user.nonexistent'))->toBe(false);
        });

        it('Devrait ajouter des valeurs avec withAddedValue()', function (): void {
            $original = new Cookie('test', ['user' => 'john']);
            $modified = $original->withAddedValue('user.role', 'admin');

            expect($modified->read('user.role'))->toBe('admin');
        });

        it('Devrait supprimer des valeurs avec withoutAddedValue()', function (): void {
            $original = new Cookie('test', ['user' => 'john', 'role' => 'admin']);
            $modified = $original->withoutAddedValue('role');

            expect($modified->read('role'))->toBeNull();
            expect($modified->read('user'))->toBe('john');
        });
    });

    describe('Parsing d\'en-têtes', function (): void {
        it('Devrait parser un en-tête Set-Cookie simple', function (): void {
            $header = 'test=value; path=/; secure; httponly';
            $cookie = Cookie::createFromHeaderString($header);

            expect($cookie->getName())->toBe('test');
            expect($cookie->getScalarValue())->toBe('value');
            expect($cookie->getPath())->toBe('/');
            expect($cookie->isSecure())->toBe(true);
            expect($cookie->isHttpOnly())->toBe(true);
        });

        it('Devrait parser un en-tête avec expiration', function (): void {
            $header = 'test=value; expires=Mon, 01-Aug-2025 12:00:00 GMT; path=/';
            $cookie = Cookie::createFromHeaderString($header);

            expect($cookie->getName())->toBe('test');
            expect($cookie->getExpiry())->toBeAnInstanceOf(DateTimeInterface::class);
        });

        it('Devrait parser un en-tête avec SameSite', function (): void {
            $header = 'test=value; path=/; samesite=Strict';
            $cookie = Cookie::createFromHeaderString($header);

            expect($cookie->getSameSite())->toBe('Strict');
        });

        it('Devrait ignorer les valeurs SameSite invalides', function (): void {
            $header = 'test=value; path=/; samesite=Invalid';
            $cookie = Cookie::createFromHeaderString($header);

            expect($cookie->getSameSite())->toBeNull();
        });
    });

    describe('Génération d\'en-têtes', function (): void {
        it('Devrait générer une valeur d\'en-tête correcte', function (): void {
            $cookie = new Cookie('test', 'value', null, '/admin', 'example.com', true, true, 'Strict');
            $header = $cookie->toHeaderValue();

            expect($header)->toContain('test=value');
            expect($header)->toContain('path=/admin');
            expect($header)->toContain('domain=example.com');
            expect($header)->toContain('secure');
            expect($header)->toContain('httponly');
            expect($header)->toContain('samesite=Strict');
        });

        it('Devrait encoder les valeurs URL', function (): void {
            $cookie = new Cookie('test', 'value with spaces');
            $header = $cookie->toHeaderValue();

            expect($header)->toContain('test=value%20with%20spaces');
        });

        it('Devrait générer un en-tête sans attributs optionnels', function (): void {
            $cookie = new Cookie('test', 'value');
            $header = $cookie->toHeaderValue();

            expect($header)->toBe('test=value; path=/');
        });
    });

    describe('ID et identification', function (): void {
        it('Devrait générer un ID unique', function (): void {
            $cookie1 = new Cookie('test', 'value', null, '/path1', 'domain.com');
            $cookie2 = new Cookie('test', 'value', null, '/path2', 'domain.com');
            $cookie3 = new Cookie('test', 'value', null, '/path1', 'other.com');

            expect($cookie1->getId())->not->toBe($cookie2->getId());
            expect($cookie1->getId())->not->toBe($cookie3->getId());
            expect($cookie1->getId())->toBe('test;domain.com;/path1');
        });
    });

    describe('Conversion', function (): void {
        it('Devrait convertir en tableau', function (): void {
            $cookie = new Cookie('test', 'value', new DateTimeImmutable('@1234567890'), '/path', 'domain.com', true, true, 'Lax');
            $array = $cookie->toArray();

            expect($array)->toBe([
                'name'     => 'test',
                'value'    => 'value',
                'expires'  => 1234567890,
                'path'     => '/path',
                'domain'   => 'domain.com',
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        });

        it('Devrait retourner les options', function (): void {
            $cookie = new Cookie('test', 'value', null, '/path', 'domain.com', true, false, 'Strict');
            $options = $cookie->getOptions();

            expect($options)->toContainKey('path');
            expect($options)->toContainKey('domain');
            expect($options)->toContainKey('secure');
            expect($options)->toContainKey('httponly');
            expect($options)->toContainKey('samesite');
        });
    });

    describe('Valeurs par défaut', function (): void {
        it('Devrait utiliser les valeurs par défaut configurées', function (): void {
            Cookie::setDefaults([
                'path'     => '/admin',
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            $cookie = new Cookie('test', 'value');

            expect($cookie->getPath())->toBe('/admin');
            expect($cookie->isSecure())->toBe(true);
            expect($cookie->isHttpOnly())->toBe(true);
            expect($cookie->getSameSite())->toBe('Lax');
        });
    });
});
