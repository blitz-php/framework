<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Contracts\Security\HasherInterface;
use BlitzPHP\Exceptions\HashingException;
use BlitzPHP\Security\Hashing\Handlers\Argon2IdHandler;
use BlitzPHP\Security\Hashing\Handlers\ArgonHandler;
use BlitzPHP\Security\Hashing\Handlers\BcryptHandler;
use BlitzPHP\Security\Hashing\Hasher;
use BlitzPHP\Spec\ReflectionHelper;

use function Kahlan\expect;

describe('Security / Hashing', function (): void {
    beforeEach(function (): void {
        $this->hasher = new Hasher();
    });

    describe('Hasher', function (): void {
        it(": L'utilisation d'un mauvais pilote leve une exception", function (): void {
            $config         = (object) config('hashing');
            $config->driver = 'Bogus';

            expect(function () use ($config): void {
                $this->hasher->initialize($config);
            })->toThrow(new HashingException());
        });

        it(": L'abscence du pilote leve une exception", function (): void {
            // ask for a bad driver
            $config         = (object) config('hashing');
            $config->driver = '';

            expect(function () use ($config): void {
                $this->hasher->initialize($config);
            })->toThrow(new HashingException());
        });

		it(':isHashed', function () {
			$hash = $this->hasher->make('password');

			expect($this->hasher->isHashed($hash))->toBeTruthy();
			expect($this->hasher->isHashed('password'))->toBeFalsy();
		});
    });

    describe('Service', function (): void {
        it(': Le service hashing fonctionne', function (): void {
            $config           = config('hashing');
            $config['driver'] = 'bcrypt';

            $hasher = service('hashing', $config);

            expect($hasher)->toBeAnInstanceOf(HasherInterface::class);
        });

        it(': Le service hashing leve une exception si le pilote est mauvais', function (): void {
            $config           = config('hashing');
            $config['driver'] = 'Bogus';

            expect(function () use ($config): void {
                single_service('hashing', $config);
            })->toThrow(new HashingException());
        });

        it(': Service hashing partagé', function (): void {
            $config           = config('hashing');
            $config['driver'] = 'bcrypt';

            $hasher = service('hashing', $config);

            $config['driver'] = 'argon';
            $hasher           = service('hashing', $config, true);

            expect(ReflectionHelper::getPrivateProperty($hasher, 'driver'))->toBe('bcrypt');
        });
    });

	describe(':check' , function () {
		it('Les valeurs vide renvoient false', function () {
			$hasher = service('hashing');
            expect($hasher->check('', ''))->toBeFalsy();
            expect($hasher->check('test', ''))->toBeFalsy();

			$hasher = new BcryptHandler();
			expect($hasher->check('password', ''))->toBeFalsy();
			$hasher = new ArgonHandler();
			expect($hasher->check('password', ''))->toBeFalsy();
			$hasher = new Argon2IdHandler();
			expect($hasher->check('password', ''))->toBeFalsy();
		});
	});

    describe('Drivers', function (): void {
        it(': Bcrypt', function (): void {
			$hasher = new BcryptHandler();
			$value = $hasher->make('password');

			expect($value)->not->toBe('password');
			expect($hasher->check('password', $value))->toBeTruthy();
			expect($hasher->needsRehash($value))->toBeFalsy();
			expect($hasher->needsRehash($value, ['rounds' => 1]))->toBeTruthy();
			expect($hasher->info($value)['algoName'])->toBe('bcrypt');
			expect($hasher->info($value)['options']['cost'])->toBeGreaterThan(11); // >= 12
			expect($this->hasher->isHashed($value))->toBeTruthy();
        });

        it(': Argon', function (): void {
			try {
				$hasher = new ArgonHandler();
				$value = $hasher->make('password');

				expect($value)->not->toBe('password');
				expect($hasher->check('password', $value))->toBeTruthy();
				expect($hasher->needsRehash($value))->toBeFalsy();
				expect($hasher->needsRehash($value, ['threads' => 3]))->toBeTruthy();
				expect($hasher->info($value)['algoName'])->toBe('argon2i');
				expect($this->hasher->isHashed($value))->toBeTruthy();
			} catch (Throwable) {
				skipIf(true);
			}
        });

        it(': Argon2id', function (): void {
			try {
				$hasher = new Argon2IdHandler();
				$value = $hasher->make('password');

				expect($value)->not->toBe('password');
				expect($hasher->check('password', $value))->toBeTruthy();
				expect($hasher->needsRehash($value))->toBeFalsy();
				expect($hasher->needsRehash($value, ['threads' => 3]))->toBeTruthy();
				expect($hasher->info($value)['algoName'])->toBe('argon2id');
				expect($this->hasher->isHashed($value))->toBeTruthy();
			} catch (Throwable) {
				skipIf(true);
			}
        });
    });

	describe('Verification', function (): void {
		it('Bcrypt', function (): void {
			$argonHandler = new ArgonHandler(['verify' => true]);
			$argonHashed = $argonHandler->make('password');

			expect(fn() => (new BcryptHandler(['verify' => true]))->check('password', $argonHashed))
				->toThrow(new RuntimeException());
		});

		it('Argon', function (): void {
			$argonHandler = new BcryptHandler(['verify' => true]);
			$argonHashed = $argonHandler->make('password');

			expect(fn() => (new ArgonHandler(['verify' => true]))->check('password', $argonHashed))
				->toThrow(new RuntimeException());
		});

		it('Argon2id', function (): void {
			$argonHandler = new BcryptHandler(['verify' => true]);
			$argonHashed = $argonHandler->make('password');

			expect(fn() => (new Argon2IdHandler(['verify' => true]))->check('password', $argonHashed))
				->toThrow(new RuntimeException());
		});
	});
});
