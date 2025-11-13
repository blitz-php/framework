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
use BlitzPHP\Session\Cookie\CookieManager;

use function Kahlan\expect;

describe('Session / Cookie / CookieManager', function (): void {
    beforeEach(function (): void {
        $this->manager = new CookieManager();
        $this->manager->setDefaultPathAndDomain('/test', 'example.com', true, true, 'Strict');

        // Sauvegarde des cookies existants
        $this->originalCookies = $_COOKIE;
        $_COOKIE = [];
    });

    afterEach(function (): void {
        // Restauration des cookies originaux
        $_COOKIE = $this->originalCookies;
    });

    describe('Création de cookies', function (): void {
        it('Devrait créer un cookie basique', function (): void {
            $cookie = $this->manager->make('test', 'value', 60);

            expect($cookie->getName())->toBe('test');
            expect($cookie->getValue())->toBe('value');
            expect($cookie->getPath())->toBe('/test');
            expect($cookie->getDomain())->toBe('example.com');
            expect($cookie->isSecure())->toBe(true);
            expect($cookie->isHttpOnly())->toBe(true);
            expect($cookie->getSameSite())->toBe('Strict');
        });

        it('Devrait créer un cookie avec durée zéro (session)', function (): void {
            $cookie = $this->manager->make('session_cookie', 'value', 0);

            expect($cookie->getName())->toBe('session_cookie');
            expect($cookie->getExpiry())->toBeNull(); // Cookie de session
        });

        it('Devrait créer un cookie permanent', function (): void {
            $cookie = $this->manager->forever('test', 'value');

            expect($cookie->getName())->toBe('test');
            expect($cookie->isExpired())->toBe(false);
            // Vérifie que le cookie expire dans environ 5 ans
            expect($cookie->getExpiresTimestamp())->toBeGreaterThan(time() + (576000 * 59)); // 5 ans - 1 minute
            expect($cookie->getExpiresTimestamp())->toBeLessThan(time() + (576000 * 61));    // 5 ans + 1 minute
        });

        it('Devrait créer un cookie d\'expiration', function (): void {
            $cookie = $this->manager->forget('test');

            expect($cookie->getName())->toBe('test');
            expect($cookie->getValue())->toBe('');
            expect($cookie->isExpired())->toBe(true);
            expect($cookie->getExpiresTimestamp())->toBeLessThan(time()); // Doit être dans le passé
        });

        it('Devrait surcharger les valeurs par défaut', function (): void {
            $cookie = $this->manager->make('test', 'value', 60, [
                'path'     => '/custom',
                'domain'   => 'custom.com',
                'secure'   => false,
                'httponly' => false,
                'samesite' => 'Lax'
            ]);

            expect($cookie->getPath())->toBe('/custom');
            expect($cookie->getDomain())->toBe('custom.com');
            expect($cookie->isSecure())->toBe(false);
            expect($cookie->isHttpOnly())->toBe(false);
            expect($cookie->getSameSite())->toBe('Lax');
        });

        it('Devrait créer un cookie avec des options minimales', function (): void {
            $cookie = $this->manager->make('minimal', 'value');

            expect($cookie->getName())->toBe('minimal');
            expect($cookie->getValue())->toBe('value');
            expect($cookie->getExpiry())->toBeNull(); // Durée par défaut = 0
        });
    });

    describe('Récupération de cookies', function (): void {
        it('Devrait récupérer un cookie existant', function (): void {
            $_COOKIE['existing'] = 'cookie_value';
            $cookie = $this->manager->get('existing');

            expect($cookie)->toBeAnInstanceOf(Cookie::class);
            expect($cookie->getName())->toBe('existing');
            expect($cookie->getValue())->toBe('cookie_value');
        });

        it('Devrait retourner null pour un cookie non existant', function (): void {
            expect($this->manager->get('nonexistent'))->toBeNull();
        });

        it('Devrait surcharger les options lors de la récupération', function (): void {
            $_COOKIE['test'] = 'value';
            $cookie = $this->manager->get('test', ['path' => '/custom']);

            expect($cookie->getPath())->toBe('/custom');
        });

        it('Devrait récupérer un cookie avec les valeurs par défaut', function (): void {
            $_COOKIE['test'] = 'value';
            $cookie = $this->manager->get('test');

            expect($cookie->getPath())->toBe('/test');
            expect($cookie->getDomain())->toBe('example.com');
            expect($cookie->isSecure())->toBe(true);
            expect($cookie->isHttpOnly())->toBe(true);
        });
    });

    describe('Vérification d\'existence', function (): void {
        it('Devrait détecter un cookie existant', function (): void {
            $_COOKIE['existing'] = 'value';
            expect($this->manager->has('existing'))->toBe(true);
        });

        it('Devrait détecter un cookie non existant', function (): void {
            expect($this->manager->has('nonexistent'))->toBe(false);
        });

        it('Devrait détecter un cookie avec valeur vide', function (): void {
            $_COOKIE['empty'] = '';
            expect($this->manager->has('empty'))->toBe(true);
        });

        it('Devrait détecter un cookie avec valeur null', function (): void {
            $_COOKIE['null_cookie'] = null;
            expect($this->manager->has('null_cookie'))->toBe(true);
        });
    });

    describe('Configuration des valeurs par défaut', function (): void {
        it('Devrait configurer les valeurs par défaut avec setDefaultPathAndDomain', function (): void {
            $manager = new CookieManager();
            $manager->setDefaultPathAndDomain('/admin', 'admin.com', false, false, 'Lax');

            $cookie = $manager->make('test', 'value', 60);

            expect($cookie->getPath())->toBe('/admin');
            expect($cookie->getDomain())->toBe('admin.com');
            expect($cookie->isSecure())->toBe(false);
            expect($cookie->isHttpOnly())->toBe(false);
            expect($cookie->getSameSite())->toBe('Lax');
        });

        it('Devrait permettre de modifier les valeurs par défaut avec setDefaults', function (): void {
            $this->manager->setDefaults([
                'path'     => '/new',
                'domain'   => 'new.com',
                'secure'   => false,
                'httponly' => false,
                'samesite' => 'None'
            ]);

            $cookie = $this->manager->make('test', 'value', 60);

            expect($cookie->getPath())->toBe('/new');
            expect($cookie->getDomain())->toBe('new.com');
            expect($cookie->isSecure())->toBe(false);
            expect($cookie->isHttpOnly())->toBe(false);
            expect($cookie->getSameSite())->toBe('None');
        });

        it('Devrait fusionner les valeurs par défaut partiellement', function (): void {
            $this->manager->setDefaults([
                'path' => '/partial'
            ]);

            $cookie = $this->manager->make('test', 'value', 60);

            expect($cookie->getPath())->toBe('/partial');
            expect($cookie->getDomain())->toBe('example.com'); // Garde l'ancienne valeur
            expect($cookie->isSecure())->toBe(true); // Garde l'ancienne valeur
        });
    });

    describe('Cookies avec valeurs complexes', function (): void {
        it('Devrait créer un cookie avec un tableau', function (): void {
            $data = ['user' => 'john', 'role' => 'admin'];
            $cookie = $this->manager->make('prefs', $data, 60);

            expect($cookie->getValue())->toBe($data);
            expect($cookie->isExpanded())->toBe(true);
        });

        it('Devrait créer un cookie permanent avec données complexes', function (): void {
            $data = ['theme' => 'dark', 'language' => 'fr'];
            $cookie = $this->manager->forever('settings', $data);

            expect($cookie->getValue())->toBe($data);
            expect($cookie->isExpired())->toBe(false);
        });

        it('Devrait créer un cookie d\'expiration avec données complexes', function (): void {
            $data = ['temp' => 'data'];
            $cookie = $this->manager->forget('temp_cookie', ['value' => $data]);

            expect($cookie->getValue())->toBe('');
            expect($cookie->isExpired())->toBe(true);
        });
    });

    describe('Expiration et durée de vie', function (): void {
        it('Devrait créer un cookie sans expiration (session)', function (): void {
            $cookie = $this->manager->make('session_cookie', 'value', 0);
            expect($cookie->getExpiry())->toBeNull();
        });

        it('Devrait créer un cookie avec expiration précise', function (): void {
            $minutes = 30;
            $cookie = $this->manager->make('test', 'value', $minutes);

            $expectedExpiry = time() + ($minutes * 60);
            $actualExpiry = $cookie->getExpiresTimestamp();

            // Tolérance de 1 seconde pour les différences de timing
            expect($actualExpiry)->toMatch(fn($actual) => $actual >= $expectedExpiry - 1);
            expect($actualExpiry)->toMatch(fn($actual) => $actual <= $expectedExpiry + 1);
        });

        it('Devrait créer un cookie avec durée négative', function (): void {
            $cookie = $this->manager->make('expired', 'value', -60);
            expect($cookie->isExpired())->toBe(true);
        });

        it('Devrait calculer correctement l\'expiration des cookies permanents', function (): void {
            $cookie = $this->manager->forever('permanent', 'value');
            $oneYearInSeconds = 576000 * 60;

            expect($cookie->getExpiresTimestamp())->toBeGreaterThan(time() + $oneYearInSeconds - 3600); // -1h
            expect($cookie->getExpiresTimestamp())->toBeLessThan(time() + $oneYearInSeconds + 3600);    // +1h
        });
    });

    describe('Macroable trait', function (): void {
        it('Devrait supporter les macros', function (): void {
            CookieManager::macro('makeJson', function (string $name, array $data, int $minutes = 0) {
                return $this->make($name, json_encode($data), $minutes);
            });

            $data = ['key' => 'value'];
            $cookie = $this->manager->makeJson('json_cookie', $data, 60);

            expect($cookie->getName())->toBe('json_cookie');
            expect($cookie->getValue())->toBe(json_encode($data));
        });

        it('Devrait supporter les macros avec paramètres supplémentaires', function (): void {
            CookieManager::macro('makeSecureJson', function (string $name, array $data, int $minutes = 0, array $options = []) {
                $options['secure'] = true;
                $options['httponly'] = true;
                return $this->make($name, json_encode($data), $minutes, $options);
            });

            $cookie = $this->manager->makeSecureJson('secure_json', ['test' => 'data'], 60);

            expect($cookie->getName())->toBe('secure_json');
            expect($cookie->isSecure())->toBe(true);
            expect($cookie->isHttpOnly())->toBe(true);
        });
    });

    describe('Compatibilité et edge cases', function (): void {
        it('Devrait gérer les noms de cookie avec caractères spéciaux', function (): void {
            $cookie = $this->manager->make('cookie-name', 'value', 60);
            expect($cookie->getName())->toBe('cookie-name');
        });

        it('Devrait gérer les valeurs de cookie avec caractères spéciaux', function (): void {
            $specialValue = 'value with spaces & special/chars';
            $cookie = $this->manager->make('test', $specialValue, 60);

            expect($cookie->getValue())->toBe($specialValue);
        });

        it('Devrait créer un cookie avec SameSite None', function (): void {
            $cookie = $this->manager->make('test', 'value', 60, ['samesite' => 'None']);
            expect($cookie->getSameSite())->toBe('None');
        });

        it('Devrait maintenir l\'immuabilité des cookies créés', function (): void {
            $original = $this->manager->make('test', 'original', 60);
            $modified = $original->withValue('modified');

            expect($original->getValue())->toBe('original');
            expect($modified->getValue())->toBe('modified');
            expect($original)->not->toBe($modified);
        });
    });
});
