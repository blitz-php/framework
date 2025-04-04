<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use App\Controllers\HomeController;
use BlitzPHP\Autoloader\Autoloader;
use BlitzPHP\Spec\ReflectionHelper;

use function Kahlan\expect;

describe('Autoloader', function (): void {
    beforeEach(function (): void {
        $config = config('autoload');

        $config['classmap'] = [
            'UnnamespacedClass' => SUPPORT_PATH . 'Autoloader/UnnamespacedClass.php',
            'OtherClass'        => APP_PATH . 'Controllers/HomeController.php',
            'Name\Spaced\Class' => APP_PATH . 'Controllers/HomeController.php',
        ];
        $config['psr4'] = [
            'App'      => APP_PATH,
            'BlitzPHP' => SYST_PATH,
        ];
        $config['composer']['discover'] = false;

        $this->loader = new Autoloader($config);
        $this->loader->initialize()->register();

        $this->classLoader = ReflectionHelper::getPrivateMethodInvoker($this->loader, 'loadInNamespace');
    });

    afterEach(function (): void {
        $this->loader->unregister();
    });

    describe('Autoloader', function (): void {
        it(': Charge les classes stockees', function (): void {
            expect(new UnnamespacedClass())->toBeAnInstanceOf('UnnamespacedClass');
        });

        it(": L'initialisation de l'autoloader avec des arguments invalides genere une exception", function (): void {
            $config             = config('autoload');
            $config['psr4']     = [];
            $config['classmap'] = [];
            $loader             = new Autoloader($config);

            expect(static fn () => $loader->initialize())
                ->toThrow(new InvalidArgumentException('Le tableau de configuration doit contenir soit la clé \'psr4\' soit la clé \'classmap\'.'));
        });

        it(": L'initialisation de l'autoloader fonctionne", function (): void {
            $loader = new Autoloader(config('autoload'));
            $loader->initialize();

            $ns = $loader->getNamespace();
            expect(count($ns['App']))->toBe(1);
            expect(clean_path($ns['App'][0]))->toBe('ROOTPATH' . DS . 'app');
        });

        it(": Recuperation de l'autoloader a partir d'une instance partagée du Service", function (): void {
            $classLoader = ReflectionHelper::getPrivateMethodInvoker(service('autoloader'), 'loadInNamespace');

            // recherchez HomeController, comme cela devrait être dans le dépôt de base
            $actual   = $classLoader(HomeController::class);
            $expected = APP_PATH . 'Controllers' . DS . 'HomeController.php';
            expect(realpath($actual) ?: $actual)->toBe($expected);
        });

        it(': Service autoloader', function (): void {
            $autoloader = single_service('autoloader');
            $autoloader->initialize();
            $autoloader->register();

            $classLoader = ReflectionHelper::getPrivateMethodInvoker($autoloader, 'loadInNamespace');

            // recherchez HomeController, comme cela devrait être dans le dépôt de base
            $actual   = $classLoader(HomeController::class);
            $expected = APP_PATH . 'Controllers' . DS . 'HomeController.php';
            expect(realpath($actual) ?: $actual)->toBe($expected);

            $autoloader->unregister();
        });

        it(': Existence de fichier', function (): void {
            $actual   = $this->classLoader(HomeController::class);
            $expected = APP_PATH . 'Controllers' . DS . 'HomeController.php';
            expect($actual)->toBe($expected);

            $actual   = $this->classLoader('BlitzPHP\Helpers\scl');
            $expected = SYST_PATH . 'Helpers' . DS . 'scl.php';
            expect($actual)->toBe($expected);
        });

        it(': Fichier non existant', function (): void {
            expect($this->classLoader('\App\Missing\Classname'))->toBeFalsy();
        });
    });

    describe('Namespace', function (): void {
        it(': Ajout de namespace', function (): void {
            expect(($this->classLoader)('My\App\Class'))->toBeFalsy();

            $this->loader->addNamespace('My\App', SUPPORT_PATH . 'Autoloader');

            $actual   = $this->classLoader('My\App\FatalLocator');
            $expected = SUPPORT_PATH . 'Autoloader' . DS . 'FatalLocator.php';

            expect($actual)->toBe($expected);
        });

        it(': Ajout de namespace pointant vers plusieurs dossiers', function (): void {
            $this->loader->addNamespace([
                'My\App' => [SUPPORT_PATH . 'Autoloader', MIDDLEWARE_PATH],
            ]);

            $actual   = $this->classLoader('My\App\FatalLocator');
            $expected = SUPPORT_PATH . 'Autoloader' . DS . 'FatalLocator.php';
            expect($actual)->toBe($expected);

            $actual   = $this->classLoader('My\App\CustomMiddleware');
            $expected = MIDDLEWARE_PATH . 'CustomMiddleware.php';
            expect($actual)->toBe($expected);
        });

        it(": Ajout d'une chaine namespace a un namespace du tableau defini", function (): void {
            $this->loader->addNamespace('App\Controllers', SUPPORT_PATH . 'Autoloader');

            $actual   = $this->classLoader('App\Controllers\FatalLocator');
            $expected = SUPPORT_PATH . 'Autoloader' . DS . 'FatalLocator.php';
            expect($actual)->toBe($expected);
        });

        it(': La methode `getNamespace` retourne un tableau avec les namespaces definis', function (): void {
            expect($this->loader->getNamespace())->toBe([
                'App'      => [APP_PATH],
                'BlitzPHP' => [SYST_PATH],
            ]);
            expect($this->loader->getNamespace('BlitzPHP'))->toBe([SYST_PATH]);
            expect($this->loader->getNamespace('Foo'))->toBe([]);
        });

        it(': Retrait de namespace', function (): void {
            $this->loader->addNamespace('My\App', SUPPORT_PATH . 'Autoloader');
            expect($this->classLoader('My\App\FatalLocator'))->toBe(SUPPORT_PATH . 'Autoloader' . DS . 'FatalLocator.php');

            $this->loader->removeNamespace('My\App');
            expect($this->classLoader('My\App\FatalLocator'))->toBeFalsy();
        });

        it(': Retrait de namespace', function (): void {
            $this->loader->addNamespace('My\App', SUPPORT_PATH . 'Autoloader');
            expect($this->classLoader('My\App\FatalLocator'))->toBe(SUPPORT_PATH . 'Autoloader' . DS . 'FatalLocator.php');

            $this->loader->removeNamespace('My\App');
            expect($this->classLoader('My\App\FatalLocator'))->toBeFalsy();
        });
    });

    describe('Composer', function (): void {
        it(': Capable de trouver les packages Composer', function (): void {
            $config = config('autoload');
            $loader = new Autoloader($config);
            $loader->initialize();

            $namespaces = $loader->getNamespace();
            expect($namespaces)->toContainKey('Ahc\\Cli');
        });

        it(": Les namespace Composer ne doivent pas remplacer les namespaces definis par l'autoloder", function (): void {
            $config         = config('autoload');
            $config['psr4'] = [
                'Psr\Log' => '/Config/Autoload/Psr/Log/',
            ];
            $loader = new Autoloader($config);
            $loader->initialize();

            $namespaces = $loader->getNamespace();
            expect($namespaces['Psr\Log'][0])->toBe('/Config/Autoload/Psr/Log' . DS);
            expect($namespaces['Psr\Log'][1])->toMatch(static fn ($actual) => str_contains($actual, VENDOR_PATH));
        });

        it(': Restriction des packages decouverts par Composer', function (): void {
            $config                         = [];
            $config['psr4']                 = [];
            $config['classmap']             = [Autoloader::class => VENDOR_PATH . 'blitz-php/autoloader/Autoload.php'];
            $config['composer']['packages'] = ['only' => ['psr/log']];
            $loader                         = new Autoloader($config);
            $loader->initialize();

            $namespaces = $loader->getNamespace();
            expect(count($namespaces))->toBe(1);
            expect($namespaces['Psr\Log'][0])->toMatch(static fn ($actual) => str_contains($actual, VENDOR_PATH));
        });

        it(': Exclusion des packages decouvers par Composer', function (): void {
            $config                         = [];
            $config['psr4']                 = [];
            $config['classmap']             = [Autoloader::class => VENDOR_PATH . 'blitz-php/autoloader/Autoload.php'];
            $config['composer']['packages'] = ['exclude' => ['psr/log', 'kahlan/kahlan']];
            $loader                         = new Autoloader($config);
            $loader->initialize();

            $namespaces = $loader->getNamespace();
            expect($namespaces)->not->toContainKey(['Psr\Log', 'Kahlan']);
        });

        it(": L'utilisation simultanné de only et exclude genere une exception", function (): void {
            $config                         = [];
            $config['psr4']                 = [];
            $config['classmap']             = [Autoloader::class => VENDOR_PATH . 'blitz-php/autoloader/Autoload.php'];
            $config['composer']['packages'] = ['only' => ['psr/log'], 'exclude' => ['kahlan/kahlan']];
            $loader                         = new Autoloader($config);

            expect(static fn () => $loader->initialize())
                ->toThrow(new LogicException('Impossible d\'utiliser "only" et "exclude" en même temps dans "Config\autoload::composer>packages".'));
        });

        it(": Trouve les routes Composer meme si le fichier autoload.php n'existe pas", function (): void {
            $composerPath = COMPOSER_PATH;
            $config       = config('autoload');
            $loader       = new Autoloader($config);

            rename(COMPOSER_PATH, COMPOSER_PATH . '.backup');
            $loader->initialize();
            rename(COMPOSER_PATH . '.backup', $composerPath);

            $namespaces = $loader->getNamespace();
            expect($namespaces)->not->toContainKey('Psr\Log');
        });
    });

    describe('Chargement de fichier non-classe', function (): void {
        it(': Chargement de fichier non-classe', function (): void {
            $config            = config('autoload');
            $config['files'][] = SUPPORT_PATH . 'Autoloader/functions.php';
            $loader            = new Autoloader($config);
            $loader->initialize()->register();

            expect(function_exists('autoload_foo'))->toBeTruthy();
            expect(autoload_foo())->toBe("Je suis chargé automatiquement par Autoloader via \$files\u{a0}!");
            expect(defined('AUTOLOAD_CONSTANT'))->toBeTruthy();
            expect(AUTOLOAD_CONSTANT)->toBe('foo');

            $loader->unregister();
        });

        it(': Chargement de helpers', function (): void {
            $config              = config('autoload');
            $config['helpers'][] = 'scl';
            $loader              = new Autoloader($config);
            $loader->initialize();
            $loader->loadHelpers();

            expect(function_exists('scl_crypt'))->toBeTruthy();

            $loader->unregister();
        });
    });
});
