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
    beforeAll(function (): void {
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

    beforeEach(function (): void {
		DotEnv::reset(); // Reset singleton

        $this->envPath = $this->tempDir . '/.env';
        $this->localPath = $this->tempDir . '/.env.local';
        // Clean globals
        unset($_ENV['TEST_VAR'], $_SERVER['TEST_VAR']);
        putenv('TEST_VAR');
    });

    afterEach(function (): void {
        if (is_file($this->envPath)) unlink($this->envPath);
        if (is_file($this->localPath)) unlink($this->localPath);
    });

    afterAll(function (): void {
        if (is_dir($this->tempDir)) {
            $this->recursiveDelete($this->tempDir);
        }
    });

    describe('Singleton et init', function (): void {
        it('instance retourne même objet', function (): void {
            $env1 = DotEnv::instance($this->tempDir);
            $env2 = DotEnv::instance($this->tempDir);
            expect($env1)->toBe($env2);
        });

        it('init charge et sync', function (): void {
            file_put_contents($this->envPath, "VAR=value\nNUM=42");
            DotEnv::init($this->tempDir);

            expect(getenv('VAR'))->toBe('value');
            expect($_ENV['VAR'])->toBe('value');
            expect($_SERVER['VAR'])->toBe('value');
            expect(getenv('NUM'))->toBe('42');
        });

        it('init avec overrides merge last wins', function (): void {
            file_put_contents($this->envPath, "VAR=base");
            file_put_contents($this->localPath, "VAR=override\nEXTRA=local");

            DotEnv::init($this->tempDir, '.env', ['.env.local']);
            expect(getenv('VAR'))->toBe('override');
            expect(getenv('EXTRA'))->toBe('local');
        });
    });

    describe('load/reload', function (): void {
        it('load retourne true et parse', function (): void {
            file_put_contents($this->envPath, "SUCCESS=true");
            $dotenv = DotEnv::instance($this->tempDir);
            expect($dotenv->load())->toBe(true);
            expect(getenv('SUCCESS'))->toBe('true');
        });

        it('load retourne false si manquant', function (): void {
            $dotenv = DotEnv::instance($this->tempDir);
            expect($dotenv->load())->toBe(false);
        });

        it('reload re-parse', function (): void {
            file_put_contents($this->envPath, "OLD=1");
            $dotenv = DotEnv::instance($this->tempDir);
            $dotenv->load();
            expect(getenv('OLD'))->toBe('1');

            file_put_contents($this->envPath, "OLD=2");
            $dotenv->reload();
            expect(getenv('OLD'))->toBe('2');
        });
    });

    describe('parseFile', function (): void {
        it('parseFile check readable', function (): void {
            $dotenv = DotEnv::instance($this->tempDir);
            $ref = new ReflectionClass(DotEnv::class);
            $method = $ref->getMethod('parseFile');

            file_put_contents($this->envPath, "VAR=ok");
            expect($method->invoke($dotenv, $this->envPath))->toBe(true);
        });

        it('parseFile throw sur read fail', function (): void {
            $dotenv = DotEnv::instance($this->tempDir);
            $ref = new ReflectionClass(DotEnv::class);
            $method = $ref->getMethod('parseFile');

            $nonReadable = $this->tempDir . '/nonreadable.env';
            touch($nonReadable);
            chmod($nonReadable, 0000);

			if (! is_readable($nonReadable)) {
				expect(fn(): mixed => $method->invoke($dotenv, $nonReadable))
					->toThrow(new LoadException(), 'Impossible de lire');
			}

            chmod($nonReadable, 0777);
            unlink($nonReadable);
        });
    });

    describe('parseLines', function (): void {
        it('parseLines basics', function (): void {
            $dotenv = DotEnv::instance($this->tempDir);
            $ref = new ReflectionClass(DotEnv::class);
            $method = $ref->getMethod('parseLines');

            $lines = explode("\n", "VAR=value\nEMPTY=\n#comment\n   \nSPACE= trimmed ");
            $parsed = $method->invoke($dotenv, $lines);

            expect($parsed['VAR'])->toBe('value');
            expect($parsed['EMPTY'])->toBe('');
            expect($parsed['SPACE'])->toBe('trimmed');
            expect($parsed)->not->toContainKey('comment');
        });

        it('parseLines quotes/escaped', function (): void {
            $dotenv = DotEnv::instance($this->tempDir);
            $ref = new ReflectionClass(DotEnv::class);
            $method = $ref->getMethod('parseLines');

            $lines = explode("\n", 'QUOTED="hello \"world\""' . "\nSINGLE='single'\n" . 'ESC=\n\\t');
            $parsed = $method->invoke($dotenv, $lines);

            expect($parsed['QUOTED'])->toBe('hello "world"');
            expect($parsed['SINGLE'])->toBe('single');
            expect($parsed['ESC'])->toBe("\n\t");
        });

        it('parseLines multiline', function (): void {
            $dotenv = DotEnv::instance($this->tempDir);
            $ref = new ReflectionClass(DotEnv::class);
            $method = $ref->getMethod('parseLines');

            $lines = explode("\n", "MULTI=|\nline1\nline2\nEND\nOTHER=value");
            $parsed = $method->invoke($dotenv, $lines);

            expect($parsed['MULTI'])->toBe("line1\nline2");
            expect($parsed['OTHER'])->toBe('value');
        });

        it('parseLines throw malformed', function (): void {
            $dotenv = DotEnv::instance($this->tempDir);
            $ref = new ReflectionClass(DotEnv::class);
            $method = $ref->getMethod('parseLines');

            $lines = explode("\n", 'VARIABLE value');
            expect(fn(): mixed => $method->invoke($dotenv, $lines))
				->toThrow(new InvalidArgumentException(), 'On ne voit pas le signe =');
        });
    });

    describe('resolveNestedVariables', function (): void {
        it('résout simples', function (): void {
            $dotenv = DotEnv::instance($this->tempDir);
            $ref = new ReflectionClass(DotEnv::class);
            $method = $ref->getMethod('resolveNestedVariables');

            $_ENV['BASE'] = 'http';
            expect($method->invoke($dotenv, '${BASE}/path'))->toBe('http/path');
            expect($method->invoke($dotenv, 'no${MISSING}'))->toBe('no${MISSING}');
        });

        it('résout deep', function (): void {
            $dotenv = DotEnv::instance($this->tempDir);
            $ref = new ReflectionClass(DotEnv::class);
            $method = $ref->getMethod('resolveNestedVariables');

            $_ENV['PROTO'] = 'https';
            $_ENV['HOST'] = 'example.com';
            expect($method->invoke($dotenv, '${PROTO}://${HOST}'))->toBe('https://example.com');
        });
    });

    describe('setValue/validate', function (): void {
        it('setValue sync et résout', function (): void {
            $dotenv = DotEnv::instance($this->tempDir);
            $_ENV['BASE'] = 'test';
            $dotenv->setValue('PATH', '${BASE}/set');

            expect(getenv('PATH'))->toBe('test/set');
            expect($_ENV['PATH'])->toBe('test/set');
        });

        it('setValue throw invalid name', function (): void {
            $dotenv = DotEnv::instance($this->tempDir);
            expect(fn() => $dotenv->setValue('invalid@', 'val'))->toThrow(new InvalidArgumentException());
        });

        it('validate OK', function (): void {
            $dotenv = DotEnv::instance($this->tempDir);
            $dotenv->setValue('OK', 'ok');
            $dotenv->validate(['OK']);
        });

        it('validate throw missing/empty', function (): void {
            $dotenv = DotEnv::instance($this->tempDir);
            $dotenv->setValue('EMPTY', '');
            expect(fn() => $dotenv->validate(['OK']))->toThrow(new LoadException());
            expect(fn() => $dotenv->validate(['EMPTY']))->toThrow(new LoadException());
        });
    });

    describe('Cache/export', function (): void {
        it('isCached TTL', function (): void {
            $dotenv = DotEnv::instance($this->tempDir);
            $ref = new ReflectionClass(DotEnv::class);
            $prop = $ref->getProperty('lastLoad');
            $prop->setValue($dotenv, time() - 600); // Expired
            expect(invade($dotenv)->isCached())->toBeFalsy();

            $prop->setValue($dotenv, time() - 100); // Valid
            expect(invade($dotenv)->isCached())->toBeTruthy();
        });

        it('export array', function (): void {
            $dotenv = DotEnv::instance($this->tempDir);
            $dotenv->setValue('EXP', 'export');
            expect($dotenv->export())->toContainKey('EXP');
            expect($dotenv->export()['EXP'])->toBe('export');
        });
    });
});
