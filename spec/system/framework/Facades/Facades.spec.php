<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Container\Container as ContainerContainer;
use BlitzPHP\Facades\Container;
use BlitzPHP\Facades\Fs;
use BlitzPHP\Facades\Route;
use BlitzPHP\Facades\Storage;
use BlitzPHP\Facades\View;
use BlitzPHP\Filesystem\Filesystem;
use BlitzPHP\Filesystem\FilesystemManager;
use BlitzPHP\Router\RouteBuilder;
use BlitzPHP\Spec\ReflectionHelper;
use BlitzPHP\View\View as ViewView;

describe('Facades', function () {
    describe('Container', function () {
        it('Container', function () {
            $accessor = ReflectionHelper::getPrivateMethodInvoker(Container::class, 'accessor');

            expect($accessor())->toBeAnInstanceOf(ContainerContainer::class);
        });

        it('Execution d\'une methode', function () {
            expect(Container::has(ContainerContainer::class))->toBeTruthy();
        });
    });

    describe('Fs', function () {
        it('FS', function () {
            $accessor = ReflectionHelper::getPrivateMethodInvoker(Fs::class, 'accessor');

            expect($accessor())->toBeAnInstanceOf(Filesystem::class);
        });

        it('Execution d\'une methode', function () {
            expect(FS::exists(__FILE__))->toBeTruthy();
        });
    });

    describe('Route', function () {
        it('Route', function () {
            $accessor = ReflectionHelper::getPrivateMethodInvoker(Route::class, 'accessor');

            expect($accessor())->toBeAnInstanceOf(RouteBuilder::class);
        });

        it('Execution d\'une methode', function () {
            $routeBuilder = Route::setDefaultController('TestController');

            expect(ReflectionHelper::getPrivateProperty($routeBuilder, 'collection')->getDefaultController())
                ->toBe('TestController');
        });
    });

    describe('Storage', function () {
        it('Storage', function () {
            $accessor = ReflectionHelper::getPrivateMethodInvoker(Storage::class, 'accessor');

            expect($accessor())->toBeAnInstanceOf(FilesystemManager::class);
        });

        it('Execution d\'une methode', function () {
            expect(Storage::exists(__FILE__))->toBeFalsy();
        });
    });

    describe('View', function () {
        it('View', function () {
            $accessor = ReflectionHelper::getPrivateMethodInvoker(View::class, 'accessor');

            expect($accessor())->toBeAnInstanceOf(ViewView::class);
        });

        it('Execution d\'une methode', function () {
            expect(View::exists(__FILE__))->toBeFalsy();
            expect(View::exists('simple'))->toBeTruthy();
        });
    });
});
