	<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Loader\DotEnv;
use BlitzPHP\Exceptions\LoadException;

use function Kahlan\expect;

describe('Loader / DotEnv', function (): void {
    beforeAll(function () {
		$this->recursiveDelete = function(string $dir): void {
			if (!is_dir($dir)) return;
			foreach (glob($dir . '/*') as $file) {
				is_dir($file) ? $this->recursiveDelete($file) : unlink($file);
			}
			rmdir($dir);
		};

        $this->tempDir = sys_get_temp_dir() . '/blitz-dotenv-test-' . uniqid();
        mkdir($this->tempDir, 0777, true);
    });

    beforeEach(function () {
		DotEnv::reset(); // Reset singleton

        $this->envPath = $this->tempDir . '/.env';
        $this->localPath = $this->tempDir . '/.env.local';
        // Clean globals
        unset($_ENV['TEST_VAR'], $_SERVER['TEST_VAR']);
        putenv('TEST_VAR');
    });

    afterEach(function () {
        if (is_file($this->envPath)) unlink($this->envPath);
        if (is_file($this->localPath)) unlink($this->localPath);
    });

    afterAll(function () {
        if (is_dir($this->tempDir)) {
            $this->recursiveDelete($this->tempDir);
        }
    });

    describe('Singleton et init', function () {
        it('instance retourne même objet', function () {
            $env1 = DotEnv::instance($this->tempDir);
            $env2 = DotEnv::instance($this->tempDir);
            expect($env1)->toBe($env2);
        });

        it('init charge et sync', function () {
            file_put_contents($this->envPath, "VAR=value\nNUM=42");
            DotEnv::init($this->tempDir);

            expect(getenv('VAR'))->toBe('value');
            expect($_ENV['VAR'])->toBe('value');
            expect($_SERVER['VAR'])->toBe('value');
            expect(getenv('NUM'))->toBe('42');
        });

        it('init avec overrides merge last wins', function () {
            file_put_contents($this->envPath, "VAR=base");
            file_put_contents($this->localPath, "VAR=override\nEXTRA=local");

            DotEnv::init($this->tempDir, '.env', ['.env.local']);
            expect(getenv('VAR'))->toBe('override');
            expect(getenv('EXTRA'))->toBe('local');
        });
    });

    describe('load/reload', function () {
        it('load retourne true et parse', function () {
            file_put_contents($this->envPath, "SUCCESS=true");
            $dotenv = DotEnv::instance($this->tempDir);
            expect($dotenv->load())->toBe(true);
            expect(getenv('SUCCESS'))->toBe('true');
        });

        it('load retourne false si manquant', function () {
            $dotenv = DotEnv::instance($this->tempDir);
            expect($dotenv->load())->toBe(false);
        });

        it('reload re-parse', function () {
            file_put_contents($this->envPath, "OLD=1");
            $dotenv = DotEnv::instance($this->tempDir);
            $dotenv->load();
            expect(getenv('OLD'))->toBe('1');

            file_put_contents($this->envPath, "OLD=2");
            $dotenv->reload();
            expect(getenv('OLD'))->toBe('2');
        });
    });

    describe('parseFile', function () {
        it('parseFile check readable', function () {
            $dotenv = DotEnv::instance($this->tempDir);
            $ref = new ReflectionClass(DotEnv::class);
            $method = $ref->getMethod('parseFile');
            $method->setAccessible(true);

            file_put_contents($this->envPath, "VAR=ok");
            expect($method->invoke($dotenv, $this->envPath))->toBe(true);
        });

        it('parseFile throw sur read fail', function () {
            $dotenv = DotEnv::instance($this->tempDir);
            $ref = new ReflectionClass(DotEnv::class);
            $method = $ref->getMethod('parseFile');
            $method->setAccessible(true);

            $nonReadable = $this->tempDir . '/nonreadable.env';
            touch($nonReadable);
            chmod($nonReadable, 0000);

			if (! is_readable($nonReadable)) {
				expect(fn() => $method->invoke($dotenv, $nonReadable))
					->toThrow(new LoadException(), 'Impossible de lire');
			}

            chmod($nonReadable, 0777);
            unlink($nonReadable);
        });
    });

    describe('parseLines', function () {
        it('parseLines basics', function () {
            $dotenv = DotEnv::instance($this->tempDir);
            $ref = new ReflectionClass(DotEnv::class);
            $method = $ref->getMethod('parseLines');
            $method->setAccessible(true);

            $lines = explode("\n", "VAR=value\nEMPTY=\n#comment\n   \nSPACE= trimmed ");
            $parsed = $method->invoke($dotenv, $lines);

            expect($parsed['VAR'])->toBe('value');
            expect($parsed['EMPTY'])->toBe('');
            expect($parsed['SPACE'])->toBe('trimmed');
            expect($parsed)->not->toContainKey('comment');
        });

        it('parseLines quotes/escaped', function () {
            $dotenv = DotEnv::instance($this->tempDir);
            $ref = new ReflectionClass(DotEnv::class);
            $method = $ref->getMethod('parseLines');
            $method->setAccessible(true);

            $lines = explode("\n", 'QUOTED="hello \"world\""' . "\nSINGLE='single'\n" . 'ESC=\n\\t');
            $parsed = $method->invoke($dotenv, $lines);

            expect($parsed['QUOTED'])->toBe('hello "world"');
            expect($parsed['SINGLE'])->toBe('single');
            expect($parsed['ESC'])->toBe("\n\t");
        });

        it('parseLines multiline', function () {
            $dotenv = DotEnv::instance($this->tempDir);
            $ref = new ReflectionClass(DotEnv::class);
            $method = $ref->getMethod('parseLines');
            $method->setAccessible(true);

            $lines = explode("\n", "MULTI=|\nline1\nline2\nEND\nOTHER=value");
            $parsed = $method->invoke($dotenv, $lines);

            expect($parsed['MULTI'])->toBe("line1\nline2");
            expect($parsed['OTHER'])->toBe('value');
        });

        it('parseLines throw malformed', function () {
            $dotenv = DotEnv::instance($this->tempDir);
            $ref = new ReflectionClass(DotEnv::class);
            $method = $ref->getMethod('parseLines');
            $method->setAccessible(true);

            $lines = explode("\n", 'VARIABLE value');
            expect(fn() => $method->invoke($dotenv, $lines))
				->toThrow(new InvalidArgumentException(), 'On ne voit pas le signe =');
        });
    });

    describe('resolveNestedVariables', function () {
        it('résout simples', function () {
            $dotenv = DotEnv::instance($this->tempDir);
            $ref = new ReflectionClass(DotEnv::class);
            $method = $ref->getMethod('resolveNestedVariables');
            $method->setAccessible(true);

            $_ENV['BASE'] = 'http';
            expect($method->invoke($dotenv, '${BASE}/path'))->toBe('http/path');
            expect($method->invoke($dotenv, 'no${MISSING}'))->toBe('no${MISSING}');
        });

        it('résout deep', function () {
            $dotenv = DotEnv::instance($this->tempDir);
            $ref = new ReflectionClass(DotEnv::class);
            $method = $ref->getMethod('resolveNestedVariables');
            $method->setAccessible(true);

            $_ENV['PROTO'] = 'https';
            $_ENV['HOST'] = 'example.com';
            expect($method->invoke($dotenv, '${PROTO}://${HOST}'))->toBe('https://example.com');
        });
    });

    describe('setValue/validate', function () {
        it('setValue sync et résout', function () {
            $dotenv = DotEnv::instance($this->tempDir);
            $_ENV['BASE'] = 'test';
            $dotenv->setValue('PATH', '${BASE}/set');

            expect(getenv('PATH'))->toBe('test/set');
            expect($_ENV['PATH'])->toBe('test/set');
        });

        it('setValue throw invalid name', function () {
            $dotenv = DotEnv::instance($this->tempDir);
            expect(fn() => $dotenv->setValue('invalid@', 'val'))->toThrow(new InvalidArgumentException());
        });

        it('validate OK', function () {
            $dotenv = DotEnv::instance($this->tempDir);
            $dotenv->setValue('OK', 'ok');
            $dotenv->validate(['OK']);
        });

        it('validate throw missing/empty', function () {
            $dotenv = DotEnv::instance($this->tempDir);
            $dotenv->setValue('EMPTY', '');
            expect(fn() => $dotenv->validate(['OK']))->toThrow(new LoadException());
            expect(fn() => $dotenv->validate(['EMPTY']))->toThrow(new LoadException());
        });
    });

    describe('Cache/export', function () {
        it('isCached TTL', function () {
            $dotenv = DotEnv::instance($this->tempDir);
            $ref = new ReflectionClass(DotEnv::class);
            $prop = $ref->getProperty('lastLoad');
            $prop->setAccessible(true);
            $prop->setValue($dotenv, time() - 600); // Expired
            expect(invade($dotenv)->isCached())->toBeFalsy();

            $prop->setValue($dotenv, time() - 100); // Valid
            expect(invade($dotenv)->isCached())->toBeTruthy();
        });

        it('export array', function () {
            $dotenv = DotEnv::instance($this->tempDir);
            $dotenv->setValue('EXP', 'export');
            expect($dotenv->export())->toContainKey('EXP');
            expect($dotenv->export()['EXP'])->toBe('export');
        });
    });
});
