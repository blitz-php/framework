<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Mail\Adapters\SymfonyMailer;
use BlitzPHP\Utilities\Reflection\ReflectionClass;

use function Kahlan\expect;

describe('Mail / Adapters / SymfonyMailer', function (): void {
    beforeEach(function (): void {
        $this->symfonyMailer = new SymfonyMailer(false);
    });

    describe('Initialisation', function (): void {
        it('Doit créer une instance', function (): void {
            expect($this->symfonyMailer)->toBeAnInstanceOf(SymfonyMailer::class);
        });

        it('Doit accepter setDsn()', function (): void {
            $result = $this->symfonyMailer->setDsn('smtp://user:pass@host:port');
            expect($result)->toBe($this->symfonyMailer);
        });
    });

    describe('Méthodes de configuration', function (): void {
        it('Doit accepter setPort()', function (): void {
            $result = $this->symfonyMailer->setPort(587);
            expect($result)->toBe($this->symfonyMailer);
        });

        it('Doit accepter setHost()', function (): void {
            $result = $this->symfonyMailer->setHost('smtp.example.com');
            expect($result)->toBe($this->symfonyMailer);
        });

        it('Doit accepter setUsername()', function (): void {
            $result = $this->symfonyMailer->setUsername('user');
            expect($result)->toBe($this->symfonyMailer);
        });

        it('Doit accepter setPassword()', function (): void {
            $result = $this->symfonyMailer->setPassword('pass');
            expect($result)->toBe($this->symfonyMailer);
        });

        it('Doit accepter setDebug()', function (): void {
            $result = $this->symfonyMailer->setDebug(1);
            expect($result)->toBe($this->symfonyMailer);
        });

        it('Doit accepter setProtocol()', function (): void {
            $result = $this->symfonyMailer->setProtocol('smtp');
            expect($result)->toBe($this->symfonyMailer);
        });

        it('Doit accepter setTimeout()', function (): void {
            $result = $this->symfonyMailer->setTimeout(30);
            expect($result)->toBe($this->symfonyMailer);
        });

        it('Doit accepter setCharset()', function (): void {
            $result = $this->symfonyMailer->setCharset('UTF-8');
            expect($result)->toBe($this->symfonyMailer);
        });

        it('Doit accepter setPriority() valide', function (): void {
            $result = $this->symfonyMailer->setPriority(SymfonyMailer::PRIORITY_HIGH);
            expect($result)->toBe($this->symfonyMailer);
        });

        it('Doit ignorer setPriority() invalide', function (): void {
            $result = $this->symfonyMailer->setPriority(999);
            expect($result)->toBe($this->symfonyMailer);
        });

        it('Doit accepter setEncryption() pour ssl', function (): void {
            $result = $this->symfonyMailer->setEncryption('ssl');
            expect($result)->toBe($this->symfonyMailer);
        });

        it('Doit accepter setEncryption() pour tls', function (): void {
            $result = $this->symfonyMailer->setEncryption('tls');
            expect($result)->toBe($this->symfonyMailer);
        });

        it('Doit accepter setEncryption() pour null', function (): void {
            $result = $this->symfonyMailer->setEncryption(null);
            expect($result)->toBe($this->symfonyMailer);
        });

        it('Doit ignorer setEncryption() invalide', function (): void {
            $result = $this->symfonyMailer->setEncryption('invalid');
            expect($result)->toBe($this->symfonyMailer);
        });
    });

    describe('Méthodes de mail', function (): void {
        it('Doit accepter clear()', function (): void {
            $result = $this->symfonyMailer->clear();
            expect($result)->toBe($this->symfonyMailer);
        });

        it('Doit accepter alt()', function (): void {
            $result = $this->symfonyMailer->alt('Alternative text');
            expect($result)->toBe($this->symfonyMailer);
        });

        it('Doit accepter subject()', function (): void {
            $result = $this->symfonyMailer->subject('Test Subject');
            expect($result)->toBe($this->symfonyMailer);
        });

        it('Doit accepter html()', function (): void {
            $result = $this->symfonyMailer->html('<p>Content</p>');
            expect($result)->toBe($this->symfonyMailer);
        });

        it('Doit accepter text()', function (): void {
            $result = $this->symfonyMailer->text('Text content');
            expect($result)->toBe($this->symfonyMailer);
        });

        it('Doit accepter message()', function (): void {
            $result = $this->symfonyMailer->message('Test message');
            expect($result)->toBe($this->symfonyMailer);
        });
    });

    describe('buildDsn()', function (): void {
        it('Doit construire un DSN SMTP', function (): void {
            $symfonyMailer = new class(false) extends SymfonyMailer {
                public function testBuildDsn() {
                    $this->setProtocol('smtp');
                    $this->setUsername('user');
                    $this->setPassword('pass@word');
                    $this->setHost('smtp.example.com');
                    $this->setPort(587);

					return ReflectionClass::make($this)->invoke('buildDsn');
                }
            };

            $dsn = $symfonyMailer->testBuildDsn();
            expect($dsn)->toContain('smtp://');
            expect($dsn)->toContain('smtp.example.com');
            expect($dsn)->toContain('587');
        });

        it('Doit encoder les caractères spéciaux dans les mots de passe', function (): void {
            $symfonyMailer = new class(false) extends SymfonyMailer {
                public function testBuildDsn() {
                    $this->setProtocol('smtp');
                    $this->setUsername('user@name');
                    $this->setPassword('pass@word#special');
                    $this->setHost('smtp.example.com');
                    $this->setPort(587);

					return ReflectionClass::make($this)->invoke('buildDsn');
                }
            };

            $dsn = $symfonyMailer->testBuildDsn();
            expect($dsn)->toContain('user%40name');
            expect($dsn)->toContain('pass%40word%23special');
        });

        it('Doit construire un DSN sendmail', function (): void {
            $symfonyMailer = new class(false) extends SymfonyMailer {
                public function testBuildDsn() {
                    $this->setProtocol('sendmail');

					return ReflectionClass::make($this)->invoke('buildDsn');
                }
            };

            $dsn = $symfonyMailer->testBuildDsn();
            expect($dsn)->toBe('sendmail://default');
        });

        it('Doit construire un DSM mail', function (): void {
            $symfonyMailer = new class(false) extends SymfonyMailer {
                public function testBuildDsn() {
                    $this->setProtocol('mail');

					return ReflectionClass::make($this)->invoke('buildDsn');
                }
            };

            $dsn = $symfonyMailer->testBuildDsn();
            expect($dsn)->toBe('sendmail://default');
        });

        it('Doit construire un DSN postmark', function (): void {
            $symfonyMailer = new class(false) extends SymfonyMailer {
                public function testBuildDsn() {
                    $this->setProtocol('postmark');
                    $this->setUsername('api-key');

					return ReflectionClass::make($this)->invoke('buildDsn');
                }
            };

            $dsn = $symfonyMailer->testBuildDsn();
            expect($dsn)->toBe('postmark+smtp://api-key@default');
        });

        it('Doit construire un DSN sendgrid', function (): void {
            $symfonyMailer = new class(false) extends SymfonyMailer {
                public function testBuildDsn() {
                    $this->setProtocol('sendgrid');
                    $this->setUsername('SG.api-key');

					return ReflectionClass::make($this)->invoke('buildDsn');
                }
            };

            $dsn = $symfonyMailer->testBuildDsn();
            expect($dsn)->toBe('sendgrid+smtp://SG.api-key@default');
        });
    });
});
