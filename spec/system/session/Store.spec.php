<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Session\Store;

use function Kahlan\expect;

describe('Session / Store', function (): void {
    beforeEach(function (): void {
        // Sauvegarde l'état original de $_SESSION
        $this->originalSession = $_SESSION ?? [];
        $_SESSION = [];

        // Mock des dépendances nécessaires
        $this->config = [
            'cookie_name' => 'blitz_session',
            'expiration'  => 7200,
            'handler'     => 'array'
        ];

        $this->cookie = [
            'path'   => '/',
            'domain' => 'localhost',
            'secure' => false
        ];

        $this->ipAddress = '127.0.0.1';

        $this->store = new Store($this->config, $this->cookie, $this->ipAddress);
    });

    afterEach(function (): void {
        // Restaure l'état original de $_SESSION
        $_SESSION = $this->originalSession;

        // Ferme la session si elle est active
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    });

    describe('Initialisation', function (): void {
        it('Devrait créer une instance de Store', function (): void {
            expect($this->store)->toBeAnInstanceOf(Store::class);
        });

        it('Devrait initialiser le token CSRF au démarrage', function (): void {
            $result = $this->store->start();
            expect($result)->toBeAnInstanceOf(Store::class);
            expect($this->store->has('_token'))->toBeTruthy();
        });
    });

    describe('Gestion des données', function (): void {
        beforeEach(function (): void {
            $this->store->start();
        });

        it('Devrait stocker et récupérer une valeur', function (): void {
            $this->store->put('name', 'John Doe');
            expect($this->store->get('name'))->toBe('John Doe');
        });

        it('Devrait retourner la valeur par défaut si la clé n\'existe pas', function (): void {
            expect($this->store->get('nonexistent', 'default'))->toBe('default');
        });

        it('Devrait vérifier l\'existence d\'une clé', function (): void {
            $this->store->put('exists', true);
            expect($this->store->exists('exists'))->toBeTruthy();
            expect($this->store->exists('nonexistent'))->toBeFalsy();
        });

        it('Devrait vérifier l\'absence d\'une clé', function (): void {
            expect($this->store->missing('nonexistent'))->toBeTruthy();
            $this->store->put('exists', true);
            expect($this->store->missing('exists'))->toBeFalsy();
        });

        it('Devrait récupérer et supprimer une valeur avec pull', function (): void {
            $this->store->put('temp', 'value');
            $value = $this->store->pull('temp');
            expect($value)->toBe('value');
            expect($this->store->has('temp'))->toBeFalsy();
        });

        it('Devrait récupérer un sous-ensemble avec only', function (): void {
            $this->store->put(['name' => 'John', 'age' => 30, 'city' => 'Paris']);
            $subset = $this->store->only(['name', 'age']);
            expect($subset)->toBe(['name' => 'John', 'age' => 30]);
        });

        it('Devrait exclure des clés avec except', function (): void {
            $this->store->put(['name' => 'John', 'age' => 30, 'city' => 'Paris']);
            $subset = $this->store->except(['city', '_token']); // exclure le token, car il est toujours present dans les données
            expect($subset)->toBe(['name' => 'John', 'age' => 30]);
        });
    });

    describe('Gestion des tableaux', function (): void {
        beforeEach(function (): void {
            $this->store->start();
        });

        it('Devrait ajouter une valeur à un tableau avec push', function (): void {
            $this->store->put('items', ['first']);
            $this->store->push('items', 'second');
            expect($this->store->get('items'))->toBe(['first', 'second']);
        });

        it('Devrait incrémenter une valeur numérique', function (): void {
            $this->store->put('counter', 5);
            $newValue = $this->store->increment('counter', 3);
            expect($newValue)->toBe(8);
            expect($this->store->get('counter'))->toBe(8);
        });

        it('Devrait décrémenter une valeur numérique', function (): void {
            $this->store->put('counter', 10);
            $newValue = $this->store->decrement('counter', 3);
            expect($newValue)->toBe(7);
            expect($this->store->get('counter'))->toBe(7);
        });
    });

    describe('Données flash', function (): void {
        beforeEach(function (): void {
            $this->store->start();
        });

        it('Devrait stocker et récupérer des données flash', function (): void {
            $this->store->flash('message', 'Success!');
            expect($this->store->flashed('message'))->toBe('Success!');
        });

        it('Devrait retourner la valeur par défaut pour les données flash inexistantes', function (): void {
            expect($this->store->flashed('nonexistent', 'default'))->toBe('default');
        });

        it('Devrait stocker des entrées flash', function (): void {
            $input = ['name' => 'John', 'email' => 'john@example.com'];
            $this->store->flashInput($input);
            expect($this->store->flashed('_old_input'))->toBe($input);
        });

        it('Devrait récupérer les anciennes entrées', function (): void {
            $input = ['name' => 'John', 'email' => 'john@example.com'];
            $this->store->flashInput($input);
            expect($this->store->getOldInput('name'))->toBe('John');
            expect($this->store->getOldInput('nonexistent', 'default'))->toBe('default');
        });

        it('Devrait vérifier la présence d\'anciennes entrées', function (): void {
            expect($this->store->hasOldInput())->toBeFalsy();

            $this->store->flashInput(['name' => 'John']);
            expect($this->store->hasOldInput())->toBeTruthy();
            expect($this->store->hasOldInput('name'))->toBeTruthy();
            expect($this->store->hasOldInput('nonexistent'))->toBeFalsy();
        });

        it('Devrait stocker des erreurs flash', function (): void {
            $errors = $this->store->flashErrors('Error message');
            expect($errors)->toContain('Error message');
            expect($this->store->flashed('errors'))->toContain('Error message');
        });

        it('Devrait conserver les données flash', function (): void {
            $this->store->flash('message', 'Hello');
            $this->store->reflash();
            expect($this->store->flashed('message'))->toBe('Hello');
        });

        it('Devrait conserver un sous-ensemble de données flash', function (): void {
            $this->store->flash(['message' => 'Hello', 'error' => 'Error']);
            $this->store->keep(['message']);
            expect($this->store->flashed('message'))->toBe('Hello');
        });
    });

    describe('Données temporaires', function (): void {
        beforeEach(function (): void {
            $this->store->start();
        });

        it('Devrait stocker des données temporaires', function (): void {
            $this->store->temp('temp_data', 'value', 300);
            expect($this->store->get('temp_data'))->toBe('value');
        });
    });

    describe('Token CSRF', function (): void {
        beforeEach(function (): void {
            $this->store->start();
        });

        it('Devrait générer un token CSRF', function (): void {
            $token = $this->store->token();
            expect($token)->toBeA('string');
            expect(strlen($token))->toBe(40); // Text::random(40)
        });

        it('Devrait régénérer le token CSRF', function (): void {
            $originalToken = $this->store->token();
            $this->store->regenerateToken();
            $newToken = $this->store->token();
            expect($newToken)->not->toBe($originalToken);
        });
    });

    describe('URL précédente', function (): void {
        beforeEach(function (): void {
            $this->store->start();
        });

        it('Devrait stocker et récupérer l\'URL précédente', function (): void {
            $this->store->setPreviousUrl('/previous-page');
            expect($this->store->previousUrl())->toBe('/previous-page');
        });

        it('Devrait vérifier la présence d\'une URL précédente', function (): void {
            expect($this->store->hasPreviousUri())->toBeFalsy();
            $this->store->setPreviousUrl('/previous-page');
            expect($this->store->hasPreviousUri())->toBeTruthy();
        });
    });

    describe('Confirmation de mot de passe', function (): void {
        beforeEach(function (): void {
            $this->store->start();
        });

        it('Devrait enregistrer la confirmation du mot de passe', function (): void {
            $this->store->passwordConfirmed();
			expect($this->store->has('auth.password_confirmed_at'))->toBeTruthy();
            expect($this->store->get('auth.password_confirmed_at'))->toBeA('integer');
        });
    });

    describe('Méthode remember', function (): void {
        beforeEach(function (): void {
            $this->store->start();
        });

        it('Devrait retourner la valeur existante', function (): void {
            $this->store->put('cached', 'existing');
            $value = $this->store->remember('cached', function (): string {
                return 'new';
            });
            expect($value)->toBe('existing');
        });

        it('Devrait exécuter le callback et stocker le résultat si la clé n\'existe pas', function (): void {
            $value = $this->store->remember('new_key', function (): string {
                return 'computed_value';
            });
            expect($value)->toBe('computed_value');
            expect($this->store->get('new_key'))->toBe('computed_value');
        });
    });

    describe('Méthode hasAny', function (): void {
        beforeEach(function (): void {
            $this->store->start();
        });

        it('Devrait retourner true si au moins une clé existe', function (): void {
            $this->store->put('existing', 'value');
            expect($this->store->hasAny(['existing', 'nonexistent']))->toBeTruthy();
        });

        it('Devrait retourner false si aucune clé n\'existe', function (): void {
            expect($this->store->hasAny(['nonexistent1', 'nonexistent2']))->toBeFalsy();
        });
    });

    describe('Régénération de session', function (): void {
        beforeEach(function (): void {
            $this->store->start();
            $this->store->put('data', 'important');
        });

        it('Devrait régénérer la session avec le token', function (): void {
            $originalToken = $this->store->token();
            $this->store->regenerate();
            expect($this->store->token())->not->toBe($originalToken);
        });
    });

    describe('Remplacement de données', function (): void {
        beforeEach(function (): void {
            $this->store->start();
            $this->store->put(['old' => 'data']);
        });

        it('Devrait remplacer toutes les données', function (): void {
			expect($this->store->get('old'))->toBe('data');

            $this->store->replace(['new' => 'data']);
			expect($this->store->get('old'))->toBeNull();
            expect($this->store->get('new'))->toBe('data');
        });
    });

    describe('Gestion des erreurs', function (): void {
        beforeEach(function (): void {
            $this->store->start();
        });

        it('Devrait lever une exception quand on push sur une valeur non-tableau', function (): void {
            $this->store->put('not_array', 'string');
            expect(function (): void {
                $this->store->push('not_array', 'value');
            })->toThrow(new InvalidArgumentException("La valeur de la clé 'not_array' n'est pas un tableau."));
        });

        it('Devrait lever une exception quand on incrémente une valeur non-numérique', function (): void {
            $this->store->put('not_numeric', 'string');
            expect(function (): void {
                $this->store->increment('not_numeric');
            })->toThrow(new InvalidArgumentException("La valeur de la clé 'not_numeric' n'est pas numérique."));
        });
    });
});
