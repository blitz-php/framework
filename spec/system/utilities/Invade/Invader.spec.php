<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Utilities\Invade\Invader;
use BlitzPHP\Utilities\Invade\StaticInvader;

use function Kahlan\expect;

describe('Utilities / Invade', function (): void {
    describe('Invader (instances)', function (): void {
        it('Doit accéder aux propriétés privées', function (): void {
            $object = new class() {
                private string $secret = 'private value';
                private function getSecret(): string {
                    return $this->secret;
                }
            };

            $invader = new Invader($object);
            expect($invader->secret)->toBe('private value');
        });

        it('Doit modifier les propriétés privées', function (): void {
            $object = new class() {
                private string $secret = 'original';
            };

            $invader = new Invader($object);
            $invader->secret = 'modified';

            expect($invader->secret)->toBe('modified');
        });

        it('Doit appeler les méthodes privées', function (): void {
            $object = new class() {
                private function secretMethod(string $param): string {
                    return 'secret ' . $param;
                }
            };

            $invader = new Invader($object);
            $result = $invader->secretMethod('test');

            expect($result)->toBe('secret test');
        });
    });

    describe('StaticInvader (classes)', function (): void {
        it('Doit accéder aux propriétés statiques privées', function (): void {
            $class = new class() {
                private static string $secret = 'static private';
            };

            $invader = StaticInvader::make($class::class);
            expect($invader->get('secret'))->toBe('static private');
        });

        it('Doit modifier les propriétés statiques privées', function (): void {
            $class = new class() {
                private static string $secret = 'original';
            };
            $className = $class::class;

            $invader = StaticInvader::make($className);
            $invader->set('secret', 'modified');

            expect($invader->get('secret'))->toBe('modified');
        });

        it('Doit appeler les méthodes statiques privées', function (): void {
            $class = new class() {
                private static function secretMethod(string $param): string {
                    return 'static ' . $param;
                }
            };
            $className = $class::class;

            $invader = StaticInvader::make($className);
            $result = $invader->method('secretMethod')->call('test');

            expect($result)->toBe('static test');
        });
    });

    describe('Sécurité et Contrôle', function (): void {
        it('Doit lancer une exception si méthode non définie pour StaticInvader', function (): void {
            $invader = StaticInvader::make(stdClass::class);

            expect(function () use ($invader): void {
                $invader->call('nonexistent');
            })->toThrow(new Exception());
        });

        it('Doit fonctionner avec make() pour Invader', function (): void {
            $object = new class() {
                private string $value = 'test';
            };

            $invader = Invader::make($object);
            expect($invader->value)->toBe('test');
        });
    });
});
