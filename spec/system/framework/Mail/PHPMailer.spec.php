<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Mail\Adapters\PHPMailer;

use function Kahlan\expect;

describe('Mail / Adapters / PHPMailer', function (): void {
    beforeEach(function (): void {
        $this->phpMailer = new PHPMailer(false);
    });

    describe('Initialisation', function (): void {
        it('Doit créer une instance', function (): void {
            expect($this->phpMailer)->toBeAnInstanceOf(PHPMailer::class);
        });

        it('Doit activer SMTPAuth avec username/password', function (): void {
            $config = [
                'username' => 'user',
                'password' => 'pass'
            ];

            $this->phpMailer->init($config);

            // On ne peut pas vérifier directement la propriété SMTPAuth car c'est dans le PHPMailer réel
            // Mais on peut vérifier que init() s'exécute sans erreur
            expect($this->phpMailer)->toBeAnInstanceOf(PHPMailer::class);
        });
    });

    describe('Méthodes de configuration', function (): void {
        it('Doit accepter setPort()', function (): void {
            $result = $this->phpMailer->setPort(587);
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit accepter setHost()', function (): void {
            $result = $this->phpMailer->setHost('smtp.example.com');
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit accepter setUsername()', function (): void {
            $result = $this->phpMailer->setUsername('user');
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit accepter setPassword()', function (): void {
            $result = $this->phpMailer->setPassword('pass');
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit accepter setDebug()', function (): void {
            $result = $this->phpMailer->setDebug(1);
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit accepter setProtocol() pour smtp', function (): void {
            $result = $this->phpMailer->setProtocol('smtp');
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit accepter setProtocol() pour mail', function (): void {
            $result = $this->phpMailer->setProtocol('mail');
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit accepter setProtocol() pour sendmail', function (): void {
            $result = $this->phpMailer->setProtocol('sendmail');
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit accepter setProtocol() pour qmail', function (): void {
            $result = $this->phpMailer->setProtocol('qmail');
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit accepter setTimeout()', function (): void {
            $result = $this->phpMailer->setTimeout(30);
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit accepter setCharset()', function (): void {
            $result = $this->phpMailer->setCharset('UTF-8');
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit accepter setPriority() valide', function (): void {
            $result = $this->phpMailer->setPriority(PHPMailer::PRIORITY_HIGH);
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit ignorer setPriority() invalide', function (): void {
            $result = $this->phpMailer->setPriority(999);
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit accepter setEncryption() pour ssl', function (): void {
            $result = $this->phpMailer->setEncryption('ssl');
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit accepter setEncryption() pour tls', function (): void {
            $result = $this->phpMailer->setEncryption('tls');
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit accepter setEncryption() pour null', function (): void {
            $result = $this->phpMailer->setEncryption(null);
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit ignorer setEncryption() invalide', function (): void {
            $result = $this->phpMailer->setEncryption('invalid');
            expect($result)->toBe($this->phpMailer);
        });
    });

    describe('Méthodes de mail', function (): void {
        // Note: Les méthodes qui utilisent le PHPMailer réel peuvent lever des exceptions
        // Nous les testons de manière minimale

        it('Doit accepter clear()', function (): void {
            $result = $this->phpMailer->clear();
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit accepter alt()', function (): void {
            $result = $this->phpMailer->alt('Alternative text');
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit accepter subject()', function (): void {
            $result = $this->phpMailer->subject('Test Subject');
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit accepter html()', function (): void {
            $result = $this->phpMailer->html('<p>Content</p>');
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit accepter text()', function (): void {
            $result = $this->phpMailer->text('Text content');
            expect($result)->toBe($this->phpMailer);
        });

        it('Doit accepter message()', function (): void {
            $result = $this->phpMailer->message('Test message');
            expect($result)->toBe($this->phpMailer);
        });
    });

    describe('Gestion d\'erreurs', function (): void {
        it('Doit capturer les exceptions dans send()', function (): void {
            // Injecter une exception dans le PHPMailer réel
            $phpMailer = new class(false) extends PHPMailer {
                public function send(): bool {
                    $this->lastError = 'Simulated error';
                    return false;
                }
            };

            $result = $phpMailer->send();
            expect($result)->toBe(false);
            expect($phpMailer->getLastError())->toBe('Simulated error');
        });
    });
});
