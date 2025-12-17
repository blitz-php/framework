<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Container\Container;
use BlitzPHP\Container\AbstractProvider;
use BlitzPHP\Spec\ReflectionHelper;
use Kahlan\Plugin\Double;

use function Kahlan\expect;

describe('Container / AbstractProvider', function (): void {
    describe('Méthodes de base', function () {
        it('constructeur initialise le container', function () {
            $mockContainer = Double::instance(['class' => Container::class]);
            $provider = new class($mockContainer) extends AbstractProvider {};

			$container = ReflectionHelper::getPrivateProperty($provider, 'container');

            expect($container)->toBe($mockContainer);
        });

        it('definitions retourne tableau vide par défaut', function () {
            $provider = new class(Double::instance(['class' => Container::class])) extends AbstractProvider {};

            expect($provider::definitions())->toBe([]);
        });

        it('register ne fait rien par défaut', function () {
            $provider = new class(Double::instance(['class' => Container::class])) extends AbstractProvider {};

            expect(fn() => $provider->register())->not->toThrow();
        });

        it('provides retourne les clés des définitions', function () {
            $provider = new class(Double::instance(['class' => Container::class])) extends AbstractProvider {
                public static function definitions(): array
                {
                    return [
                        'service.one' => fn() => 'one',
                        'service.two' => fn() => 'two',
                    ];
                }
            };

            expect($provider->provides())->toBe(['service.one', 'service.two']);
        });
    });
});
