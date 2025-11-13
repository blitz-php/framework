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
use BlitzPHP\Session\Cookie\CookieCollection;
use Kahlan\Plugin\Double;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

use function Kahlan\expect;

describe('Session / Cookie / CookieCollection', function (): void {
    beforeEach(function (): void {
        $this->cookie1 = Cookie::create('session', 'abc123', ['path' => '/', 'domain' => 'example.com']);
        $this->cookie2 = Cookie::create('user', 'john', ['path' => '/admin', 'domain' => 'example.com']);
        $this->cookie3 = Cookie::create('prefs', 'dark', ['path' => '/', 'domain' => 'sub.example.com']);
    });

    describe('Création', function (): void {
        it('Devrait créer une collection vide', function (): void {
            $collection = new CookieCollection();
            expect($collection->count())->toBe(0);
            expect($collection->isEmpty())->toBe(true);
        });

        it('Devrait créer une collection avec des cookies', function (): void {
            $collection = new CookieCollection([$this->cookie1, $this->cookie2]);
            expect($collection->count())->toBe(2);
            expect($collection->isEmpty())->toBe(false);
        });

        it('Devrait créer une collection à partir d\'en-têtes', function (): void {
            $headers = [
                'session=abc123; path=/; domain=example.com',
                'user=john; path=/admin; domain=example.com'
            ];

            $collection = CookieCollection::createFromHeader($headers);
            expect($collection->count())->toBe(2);
            expect($collection->has('session'))->toBe(true);
            expect($collection->has('user'))->toBe(true);
        });

        it('Devrait ignorer les en-têtes invalides', function (): void {
            $headers = [
                'valid=value; path=/',
                '=value; path=/; invalid-attr'
            ];

            $collection = CookieCollection::createFromHeader($headers);
            expect($collection->count())->toBe(1);
            expect($collection->has('valid'))->toBe(true);
        });

		it('Devrait créer une collection depuis une ServerRequest', function (): void {
            $request = Double::instance([
				'implements'  => [ServerRequestInterface::class],
				'stubMethods' => [
					'getCookieParams' => ['session' => 'abc123', 'user' => 'john']
				],
			]);

            $collection = CookieCollection::createFromServerRequest($request);
            expect($collection->count())->toBe(2);
            expect($collection->has('session'))->toBe(true);
            expect($collection->has('user'))->toBe(true);
        });
    });

    describe('Ajout et suppression', function (): void {
        it('Devrait ajouter un cookie', function (): void {
            $collection = new CookieCollection([$this->cookie1]);
            $newCollection = $collection->add($this->cookie2);

            expect($collection->count())->toBe(1);
            expect($newCollection->count())->toBe(2);
            expect($newCollection->has('user'))->toBe(true);
        });

        it('Devrait ajouter plusieurs cookies', function (): void {
            $collection = new CookieCollection();
            $newCollection = $collection->addMany([$this->cookie1, $this->cookie2]);

            expect($newCollection->count())->toBe(2);
            expect($newCollection->has('session'))->toBe(true);
            expect($newCollection->has('user'))->toBe(true);
        });

        it('Devrait rejeter l\'ajout d\'éléments non-Cookie', function (): void {
            expect(function (): void {
                $collection = new CookieCollection();
                $collection->addMany([$this->cookie1, 'invalid']);
            })->toThrow(new InvalidArgumentException('Tous les éléments doivent implémenter CookieInterface'));
        });

        it('Devrait supprimer un cookie par nom', function (): void {
            $collection = new CookieCollection([$this->cookie1, $this->cookie2]);
            $newCollection = $collection->remove('session');

            expect($collection->count())->toBe(2);
            expect($newCollection->count())->toBe(1);
            expect($newCollection->has('session'))->toBe(false);
            expect($newCollection->has('user'))->toBe(true);
        });

        it('Devrait supprimer plusieurs cookies', function (): void {
            $collection = new CookieCollection([$this->cookie1, $this->cookie2, $this->cookie3]);
            $newCollection = $collection->removeMany(['session', 'user']);

            expect($newCollection->count())->toBe(1);
            expect($newCollection->has('session'))->toBe(false);
            expect($newCollection->has('user'))->toBe(false);
            expect($newCollection->has('prefs'))->toBe(true);
        });
    });

    describe('Recherche', function (): void {
        beforeEach(function (): void {
            $this->collection = new CookieCollection([$this->cookie1, $this->cookie2]);
        });

        it('Devrait trouver un cookie par nom', function (): void {
            $cookie = $this->collection->find('session');
            expect($cookie)->toBe($this->cookie1);
        });

        it('Devrait retourner null pour un cookie non trouvé', function (): void {
            expect($this->collection->find('nonexistent'))->toBeNull();
        });

        it('Devrait récupérer un cookie avec get()', function (): void {
            $cookie = $this->collection->get('session');
            expect($cookie)->toBe($this->cookie1);
        });

        it('Devrait lever une exception avec get() si non trouvé', function (): void {
            expect(function (): void {
                $this->collection->get('nonexistent');
            })->toThrow(new InvalidArgumentException('Cookie "nonexistent" non trouvé dans la collection'));
        });

        it('Devrait vérifier l\'existence d\'un cookie', function (): void {
            expect($this->collection->has('session'))->toBe(true);
            expect($this->collection->has('nonexistent'))->toBe(false);
        });

        it('Devrait trouver tous les cookies d\'un nom', function (): void {
            // Ajoute un deuxième cookie avec le même nom mais domaine différent
            $cookie4 = Cookie::create('session', 'def456', ['domain' => 'other.com']);
            $collection = $this->collection->add($cookie4);

            $sessions = $collection->findAll('session');
            expect($sessions)->toHaveLength(2);
        });

        it('Devrait supporter l\'accès magique', function (): void {
            expect($this->collection->session)->toBe($this->cookie1);
            expect($this->collection->nonexistent)->toBeNull();
            expect(isset($this->collection->session))->toBe(true);
            expect(isset($this->collection->nonexistent))->toBe(false);
        });
    });

    describe('Filtrage et correspondance', function (): void {
        it('Devrait filtrer les cookies par contexte', function (): void {
            $collection = new CookieCollection([$this->cookie1, $this->cookie2, $this->cookie3]);
            $filtered = $collection->filter('https', 'example.com', '/');

            expect($filtered->count())->toBe(1);
            expect($filtered->has('session'))->toBe(true);
        });

        it('Devrait trouver les cookies correspondants', function (): void {
            $collection = new CookieCollection([$this->cookie1, $this->cookie2]);
            $matches = invade($collection)->findMatchingCookies('https', 'example.com', '/');

            expect($matches)->toContainKey('session');
            expect($matches['session'])->toBe('abc123');
        });

        it('Devrait exclure les cookies sécurisés en HTTP', function (): void {
            $secureCookie = Cookie::create('secure', 'value', ['secure' => true]);
            $collection = new CookieCollection([$secureCookie]);
            $matches = invade($collection)->findMatchingCookies('http', 'example.com', '/');

            expect($matches)->not->toContainKey('secure');
        });

        it('Devrait exclure les cookies expirés', function (): void {
            $expiredCookie = Cookie::create('expired', 'value', ['expires' => time() - 3600]);
            $collection = new CookieCollection([$expiredCookie]);
            $matches = invade($collection)->findMatchingCookies('https', 'example.com', '/');

            expect($matches)->not->toContainKey('expired');
        });
    });

    describe('Intégration avec les requêtes HTTP', function (): void {
        it('Devrait ajouter des cookies à une requête', function (): void {
            $collection = new CookieCollection([$this->cookie1, $this->cookie2]);

			$uri = Double::instance([
				'implements' => [UriInterface::class],
				'stubMethods' => [
					'getScheme' => 'https',
					'getHost'   => 'example.com',
					'getPath'   => '/'
				]
			]);

			$request = Double::instance([
				'implements' => [RequestInterface::class],
				'stubMethods' => ['getUri' => $uri]
			]);
			allow($request)->toReceive('withHeader')->with('Cookie', 'session=abc123')->andReturn($request);

            $newRequest = $collection->addToRequest($request);
            expect($newRequest)->toBe($request);
        });

        it('Devrait ajouter des cookies supplémentaires', function (): void {
            $collection = new CookieCollection([$this->cookie1]);

            $uri = Double::instance([
				'implements' => [UriInterface::class],
				'stubMethods' => [
					'getScheme' => 'https',
					'getHost'   => 'example.com',
					'getPath'   => '/'
				]
			]);

			$request = Double::instance([
				'implements' => [RequestInterface::class],
				'stubMethods' => ['getUri' => $uri]
			]);
			allow($request)->toReceive('withHeader')->with('Cookie', 'extra=value; session=abc123')->andReturn($request);

            $newRequest = $collection->addToRequest($request, ['extra' => 'value']);
            expect($newRequest)->toBe($request);
        });
    });

    describe('Gestion des expirations', function (): void {
        it('Devrait détecter les cookies expirés', function (): void {
            $expired = Cookie::create('expired', 'value', ['expires' => time() - 3600]);
            $valid = Cookie::create('valid', 'value', ['expires' => time() + 3600]);

            $collection = new CookieCollection([$expired, $valid]);
            expect($collection->hasExpired())->toBe(true);
        });

        it('Devrait créer une collection sans cookies expirés', function (): void {
            $expired = Cookie::create('expired', 'value', ['expires' => time() - 3600]);
            $valid = Cookie::create('valid', 'value', ['expires' => time() + 3600]);

            $collection = new CookieCollection([$expired, $valid]);
            $filtered = $collection->withoutExpired();

            expect($filtered->count())->toBe(1);
            expect($filtered->has('valid'))->toBe(true);
            expect($filtered->has('expired'))->toBe(false);
        });
    });

    describe('Fusion', function (): void {
        it('Devrait fusionner deux collections', function (): void {
            $collection1 = new CookieCollection([$this->cookie1]);
            $collection2 = new CookieCollection([$this->cookie2]);

            $merged = $collection1->merge($collection2);
            expect($merged->count())->toBe(2);
            expect($merged->has('session'))->toBe(true);
            expect($merged->has('user'))->toBe(true);
        });

        it('Devrait écraser les cookies dupliqués', function (): void {
            $cookie1 = Cookie::create('test', 'value1');
            $cookie2 = Cookie::create('test', 'value2');

            $collection1 = new CookieCollection([$cookie1]);
            $collection2 = new CookieCollection([$cookie2]);

            $merged = $collection1->merge($collection2);
            expect($merged->get('test')->getValue())->toBe('value2');
        });
    });

    describe('Itération et conversion', function (): void {
        it('Devrait être itérable', function (): void {
            $collection = new CookieCollection([$this->cookie1, $this->cookie2]);
            $names = [];

            foreach ($collection as $cookie) {
                $names[] = $cookie->getName();
            }

            expect($names)->toContain('session');
            expect($names)->toContain('user');
        });

        it('Devrait convertir en tableau', function (): void {
            $collection = new CookieCollection([$this->cookie1, $this->cookie2]);
            $array = $collection->toArray();

            expect($array)->toBeAn('array');
            expect($array)->toHaveLength(2);
            expect($array[$this->cookie1->getId()])->toBe($this->cookie1);
        });

        it('Devrait retourner les noms des cookies', function (): void {
            $collection = new CookieCollection([$this->cookie1, $this->cookie2]);
            $names = $collection->getNames();

            expect($names)->toContain('session');
            expect($names)->toContain('user');
        });

        it('Devrait avoir une représentation en chaîne', function (): void {
            $collection = new CookieCollection([$this->cookie1, $this->cookie2]);
            $string = (string) $collection;

            expect($string)->toMatch('/CookieCollection\(2 cookies: session, user\)/');
        });
    });
});
