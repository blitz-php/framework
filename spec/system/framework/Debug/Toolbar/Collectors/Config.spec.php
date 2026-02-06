<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Debug\Toolbar\Collectors;

use BlitzPHP\Container\Services;
use BlitzPHP\Http\Request;
use Mockery;

use function Kahlan\expect;

describe('Debug / Toolbar / Collectors / Config', function (): void {
    beforeEach(function (): void {
        // Sauvegarder la configuration originale
        $this->originalConfig = config('app');

        // Configuration de test
        config()->set('app', [
            'name' => 'Test Application',
            'environment' => 'testing',
            'base_url' => 'http://localhost:8080',
            'timezone' => 'UTC',
        ]);

        // Mock du service request pour getLocale()
        $mockRequest = Mockery::mock(Request::class);
		$mockRequest->shouldReceive('getLocale')->andReturn('fr_FR');

        Services::injectMock('request', $mockRequest);
    });

    afterEach(function (): void {
        // Restaurer la configuration originale
        config()->set('app', $this->originalConfig);
        Services::resetSingle('request');
    });

    it('affiche toutes les informations de configuration', function (): void {
		// Définir les variables serveur pour le test
        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4.41';
        $_SERVER['DOCUMENT_ROOT'] = '/var/www/html';

        $result = Config::display();

        expect($result)->toBeAn('array');
        expect($result)->toContainKey('blitzVersion');
        expect($result)->toContainKey('serverVersion');
        expect($result)->toContainKey('phpVersion');
        expect($result)->toContainKey('os');
        expect($result)->toContainKey('phpSAPI');
        expect($result)->toContainKey('appName');
        expect($result)->toContainKey('environment');
        expect($result)->toContainKey('baseURL');
        expect($result)->toContainKey('documentRoot');
        expect($result)->toContainKey('timezone');
        expect($result)->toContainKey('locale');

        // Vérifier les valeurs
        expect($result['blitzVersion'])->toBe(BLITZ_CORE_VERSION);
        expect($result['serverVersion'])->toBe('Apache/2.4.41');
        expect($result['phpVersion'])->toBe(PHP_VERSION);
        expect($result['os'])->toBe(PHP_OS_FAMILY);
        expect($result['phpSAPI'])->toBe(PHP_SAPI);
        expect($result['appName'])->toBe('Test Application');
        expect($result['environment'])->toBe('testing');
        expect($result['baseURL'])->toBe('http://localhost:8080');
        expect($result['documentRoot'])->toBe('/var/www/html');
        expect($result['timezone'])->toBe('UTC');
        expect($result['locale'])->toBe('fr_FR');

        // Nettoyer
        unset($_SERVER['SERVER_SOFTWARE'], $_SERVER['DOCUMENT_ROOT']);
    });

    it('gère les valeurs manquantes dans la configuration', function (): void {
        config()->set('app', []);

        $result = Config::display();

        expect($result['appName'])->toBe('');
        expect($result['environment'])->toBe('auto');
        expect($result['baseURL'])->toBe('auto');
    });

    it('utilise WEBROOT si DOCUMENT_ROOT n\'est pas défini', function (): void {
        if (!defined('WEBROOT')) {
            define('WEBROOT', '/default/webroot');
        }

        unset($_SERVER['DOCUMENT_ROOT']);

        $result = Config::display();

        expect($result['documentRoot'])->toBe(WEBROOT);
    });

    it('utilise les valeurs de $_SERVER lorsqu\'elles sont disponibles', function (): void {
        $_SERVER['SERVER_SOFTWARE'] = 'nginx/1.18.0';
        $_SERVER['DOCUMENT_ROOT'] = '/usr/share/nginx/html';

        $result = Config::display();

        expect($result['serverVersion'])->toBe('nginx/1.18.0');
        expect($result['documentRoot'])->toBe('/usr/share/nginx/html');

        unset($_SERVER['SERVER_SOFTWARE'], $_SERVER['DOCUMENT_ROOT']);
    });

    it('retourne toujours la version PHP correcte', function (): void {
        $result = Config::display();

        expect($result['phpVersion'])->toBe(PHP_VERSION);
    });

    it('retourne toujours la famille OS correcte', function (): void {
        $result = Config::display();

        expect($result['os'])->toBe(PHP_OS_FAMILY);
    });

    it('retourne toujours le SAPI PHP correct', function (): void {
        $result = Config::display();

        expect($result['phpSAPI'])->toBe(PHP_SAPI);
    });
});
