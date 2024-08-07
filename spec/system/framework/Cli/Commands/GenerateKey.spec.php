<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Spec\CliOutputHelper as COH;
use BlitzPHP\Spec\StreamInterceptor;

function resetEnvironment(): void
{
    putenv('encryption.key');
    unset($_ENV['encryption.key'], $_SERVER['encryption.key']);
}

describe('Commandes / GenerateKey', function (): void {
    beforeEach(function (): void {
        COH::setUp();

        $this->envPath       = ROOTPATH . '.env';
        $this->backupEnvPath = ROOTPATH . '.env.backup';

        if (is_file($this->envPath)) {
            rename($this->envPath, $this->backupEnvPath);
        }

        resetEnvironment();
    });

    afterEach(function (): void {
        COH::tearDown();

        if (is_file($this->envPath)) {
            unlink($this->envPath);
        }

        if (is_file($this->backupEnvPath)) {
            rename($this->backupEnvPath, $this->envPath);
        }

        resetEnvironment();
    });

    beforeAll(function (): void {
        COH::setUpBeforeClass();
    });

    afterAll(function (): void {
        COH::tearDownAfterClass();
    });

    it(': GenerateKey affiche la clé codée', function (): void {
        command('key:generate --show');
        expect(COH::buffer())->toMatch(static fn ($actual) => str_contains($actual, 'hex2bin:'));

        command('key:generate --prefix=base64 --show');
        expect(COH::buffer())->toMatch(static fn ($actual) => str_contains($actual, 'base64:'));

        command('key:generate --prefix=hex2bin --show');
        expect(COH::buffer())->toMatch(static fn ($actual) => str_contains($actual, 'hex2bin:'));
    });

    it(': GenerateKey génère une nouvelle la clé', function (): void {
        command('key:generate');
        expect(COH::buffer())->toMatch(static fn ($actual) => str_contains($actual, 'SUCCESS'));
        expect(file_get_contents($this->envPath))->toMatch(static fn ($actual) => str_contains($actual, env('encryption.key')));
        expect(file_get_contents($this->envPath))->toMatch(static fn ($actual) => str_contains($actual, 'hex2bin:'));

        command('key:generate --prefix=base64 --force');
        expect(COH::buffer())->toMatch(static fn ($actual) => str_contains($actual, 'SUCCESS'));
        // expect(file_get_contents($this->envPath))->toMatch(fn($actual) => str_contains($actual, env('encryption.key')));
        expect(file_get_contents($this->envPath))->toMatch(static fn ($actual) => str_contains($actual, 'base64:'));

        command('key:generate --prefix=hex2bin --force');
        expect(COH::buffer())->toMatch(static fn ($actual) => str_contains($actual, 'SUCCESS'));
        // expect(file_get_contents($this->envPath))->toMatch(fn($actual) => str_contains($actual, env('encryption.key')));
        expect(file_get_contents($this->envPath))->toMatch(static fn ($actual) => str_contains($actual, 'hex2bin:'));
    });

    it(": Le fichier .env.example n'existe pas", function (): void {
        rename(ROOTPATH . '.env.example', ROOTPATH . 'lostenv');
        command('key:generate');
        rename(ROOTPATH . 'lostenv', ROOTPATH . '.env.example');

        expect(COH::buffer())->toMatch(static fn ($actual) => str_contains($actual, 'livré par défaut'));
        expect(COH::buffer())->toMatch(static fn ($actual) => str_contains($actual, 'Erreur dans la configuration'));
    });

    it(': Le fichier .env existe mais il est vide', function (): void {
        file_put_contents($this->envPath, '');

        command('key:generate');

        expect(COH::buffer())->toMatch(static fn ($actual) => str_contains($actual, 'SUCCESS'));
        expect(file_get_contents($this->envPath))->toBe("\nencryption.key = " . env('encryption.key'));
    });

    it(': Clé générée lorsque la nouvelle clé hexadécimale est ensuite commentée', function (): void {
        command('key:generate');
        $key = env('encryption.key', '');

        file_put_contents($this->envPath, str_replace(
            'encryption.key = ' . $key,
            '# encryption.key = ' . $key,
            file_get_contents($this->envPath),
            $count
        ));

        expect($count)->toBe(1); // Échec de la mise en commentaire de la clé précédemment définie.

        StreamInterceptor::$buffer = '';
        command('key:generate --force');
        expect(COH::buffer())->toMatch(static fn ($actual) => str_contains($actual, 'SUCCESS'));
        // expect($key)->not->toBe(env('encryption.key', $key)); // Échec du remplacement de la clé commentée.
    });

    it(': Clé générée lorsque la nouvelle clé base64 est ensuite commentée', function (): void {
        command('key:generate --prefix=base64');
        $key = env('encryption.key', '');

        file_put_contents($this->envPath, str_replace(
            'encryption.key = ' . $key,
            '# encryption.key = ' . $key,
            file_get_contents($this->envPath),
            $count
        ));

        expect($count)->toBe(1); // Échec de la mise en commentaire de la clé précédemment définie.

        StreamInterceptor::$buffer = '';
        command('key:generate --force');
        expect(COH::buffer())->toMatch(static fn ($actual) => str_contains($actual, 'SUCCESS'));
        // expect($key)->not->toBe(env('encryption.key', $key)); // Échec du remplacement de la clé commentée.
    });
});
