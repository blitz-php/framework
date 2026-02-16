<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Exceptions\MailException;
use BlitzPHP\Mail\Mail;
use BlitzPHP\Mail\Adapters\AbstractAdapter;
use BlitzPHP\Mail\Adapters\PHPMailer;
use BlitzPHP\Mail\Adapters\SymfonyMailer;
use BlitzPHP\Mail\Mailable;
use BlitzPHP\Utilities\Reflection\ReflectionClass;
use Kahlan\Plugin\Double;

use function Kahlan\expect;
use function Kahlan\allow;

describe('Mail / Mail', function (): void {
    beforeEach(function (): void {
        // Configuration par défaut
        $this->config = [
            'handler' => 'log',
            'from' => [
                'address' => 'test@example.com',
                'name' => 'Test Sender'
            ],
            'debug' => false,
            'view_dir' => 'views/emails',
            'template' => 'layouts/email'
        ];


        // Instance de Mail
        $this->mail = new Mail($this->config);
    });

    describe('Initialisation', function (): void {
        it('Doit s\'initialiser avec la configuration', function (): void {
            expect($this->mail)->toBeAnInstanceOf(Mail::class);
        });

        it('Doit accepter une configuration vide', function (): void {
            $mail = new Mail([]);
            expect($mail)->toBeAnInstanceOf(Mail::class);
        });

        it('Doit accepter null comme EventManager', function (): void {
            $mail = new Mail($this->config, null);
            expect($mail)->toBeAnInstanceOf(Mail::class);
        });
    });

    describe('Factory', function (): void {
        it('Doit créer un adaptateur PHPMailer', function (): void {
            $config = array_merge($this->config, ['handler' => 'phpmailer']);
            $mail = new Mail($config);

            $adapter = ReflectionClass::make($mail)->invoke('factory');
            expect($adapter)->toBeAnInstanceOf(PHPMailer::class);
        });

        it('Doit créer un adaptateur SymfonyMailer', function (): void {
            $config = array_merge($this->config, ['handler' => 'symfony']);
            $mail = new Mail($config);

            $adapter = ReflectionClass::make($mail)->invoke('factory');
            expect($adapter)->toBeAnInstanceOf(SymfonyMailer::class);
        });

        it('Doit lever une exception pour un handler non défini', function (): void {
            $mail = new Mail([]);

            expect(fn(): mixed => ReflectionClass::make($mail)->invoke('factory'))
                ->toThrow(new InvalidArgumentException(lang('Mail.undefinedHandler')));
        });

        it('Doit lever une exception pour un handler inexistant', function (): void {
            $config = ['handler' => 'nonexistent'];
            $mail = new Mail($config);

            expect(fn(): mixed => ReflectionClass::make($mail)->invoke('factory'))
                ->toThrow(new InvalidArgumentException(lang('Mail.invalidHandler', ['nonexistent'])));
        });

        it('Doit lever une exception pour un handler qui n\'étend pas AbstractAdapter', function (): void {
            $config = ['handler' => 'stdClass'];
            $mail = new Mail($config);

            expect(fn(): mixed => ReflectionClass::make($mail)->invoke('factory'))
                ->toThrow(new InvalidArgumentException(lang('Mail.handlerMustExtendClass', ['stdClass', AbstractAdapter::class])));
        });
    });

    describe('send()', function (): void {
        beforeEach(function (): void {
            $this->mockAdapter = Double::instance([
                'extends' => AbstractAdapter::class,
                'methods' => ['__construct']
            ]);

            allow($this->mockAdapter)->toReceive('init')->andReturn($this->mockAdapter);
            allow($this->mockAdapter)->toReceive('from')->andReturn($this->mockAdapter);
            allow($this->mockAdapter)->toReceive('clear');
            allow($this->mockAdapter)->toReceive('send')->andReturn(true);
            allow($this->mockAdapter)->toReceive('lastId')->andReturn('test-id');
            allow($this->mockAdapter)->toReceive('getLastError')->andReturn(null);

            allow($this->mail)->toReceive('factory')->andReturn($this->mockAdapter);
        });

        it('Doit émettre un événement "sending" avant l\'envoi et "sent" après un envoi réussi', function (): void {
			$events = [];
			service('event')->on('*', static function($event) use (&$events): void {
				$events[] = $event->getName();
			});

            $this->mail->send();
			expect($events)->toContain('mail.sending');
			expect($events)->toContain('mail.sent');
        });

        it('Doit émettre un événement failed après un échec', function (): void {
            allow($this->mockAdapter)->toReceive('send')->andReturn(false);
            allow($this->mockAdapter)->toReceive('getLastError')->andReturn('SMTP error');
			ReflectionClass::make($this->mail)->setValue('adapter', $this->mockAdapter);

			$events = [];
			service('event')->on('*', static function($event) use (&$events): void {
				$events[] = $event->getName();
			});

            $this->mail->send();
			expect($events)->toContain('mail.failed');
			expect($events)->toContain('mail.failed');
        });

        it('Doit retourner true pour un envoi réussi', function (): void {
            $result = $this->mail->send();
            expect($result)->toBe(true);
        });

        it('Doit retourner false pour un envoi échoué', function (): void {
            allow($this->mockAdapter)->toReceive('send')->andReturn(false);
            allow($this->mockAdapter)->toReceive('getLastError')->andReturn('SMTP error');
			ReflectionClass::make($this->mail)->setValue('adapter', $this->mockAdapter);

            $result = $this->mail->send();
            expect($result)->toBe(false);
        });

        it('Doit lever une exception si l\'adaptateur lève une exception', function (): void {
			allow($this->mockAdapter)->toReceive('send')->andRun(function (): void {
				throw new Exception('SMTP Connection failed');
			});
			ReflectionClass::make($this->mail)->setValue('adapter', $this->mockAdapter);

            expect(fn() => $this->mail->send())
                ->toThrow(new MailException('Erreur d\'envoi de mail: SMTP Connection failed'));
        });
    });

    describe('envoi()', function (): void {
        it('Doit envoyer un Mailable', function (): void {
            $mailable = Double::instance(['extends' => Mailable::class]);
            allow($mailable)->toReceive('isValid')->andReturn(true);
            allow($mailable)->toReceive('send')->andReturn(true);

            $result = $this->mail->envoi($mailable);
            expect($result)->toBe(true);
        });
    });

    describe('Gestion d\'erreurs', function (): void {
        it('Doit retourner la dernière erreur', function (): void {
            $mockAdapter = Mockery::mock(AbstractAdapter::class);
			$mockAdapter->shouldReceive('init')->andReturn($mockAdapter);
            $mockAdapter->shouldReceive('from')->andReturn($mockAdapter);
            $mockAdapter->shouldReceive('getLastError')->andReturn('SMTP error');

			ReflectionClass::make($this->mail)->setValue('adapter', $mockAdapter);

            $error = $this->mail->getLastError();
            expect($error)->toBe('SMTP error');
        });

        it('Doit retourner null si pas d\'erreur', function (): void {
            $mockAdapter = Mockery::mock(AbstractAdapter::class);
			$mockAdapter->shouldReceive('init')->andReturn($mockAdapter);
            $mockAdapter->shouldReceive('from')->andReturn($mockAdapter);
            $mockAdapter->shouldReceive('getLastError')->andReturn(null);

			ReflectionClass::make($this->mail)->setValue('adapter', $mockAdapter);

            $error = $this->mail->getLastError();
            expect($error)->toBeNull();
        });
    });

    describe('Méthodes avancées', function (): void {
        it('Doit supporter sendWithRetry()', function (): void {
            $mockAdapter = Mockery::mock(AbstractAdapter::class);
			$mockAdapter->shouldReceive('init')->andReturn($mockAdapter);
            $mockAdapter->shouldReceive('clear');
            $mockAdapter->shouldReceive('send')->andReturn(true);
            $mockAdapter->shouldReceive('lastId')->andReturn(md5(uniqid()));

			ReflectionClass::make($this->mail)->setValue('adapter', $mockAdapter);

            $result = $this->mail->sendWithRetry(2);
            expect($result)->toBe(true);
        });

        it('Doit supporter bulk()', function (): void {
            $mockAdapter = Mockery::mock(AbstractAdapter::class);
			foreach (['init', 'from', 'to', 'subject', 'html'] as $method) {
				$mockAdapter->shouldReceive($method)->andReturn($mockAdapter);
			}
            $mockAdapter->shouldReceive('clear');
            $mockAdapter->shouldReceive('send')->andReturn(true);
            $mockAdapter->shouldReceive('lastId')->andReturn(md5(uniqid()));
            $mockAdapter->shouldReceive('getLastError')->andReturn(null);

			ReflectionClass::make($this->mail)->setValue('adapter', $mockAdapter);

            $recipients = [
                ['email' => 'test1@example.com'],
                ['email' => 'test2@example.com']
            ];

            [$success, $failures] = $this->mail->bulk($recipients, function ($mail, $recipient): void {
                $mail->to($recipient['email']);
                $mail->subject('Bulk email');
                $mail->html('Content');
            });

            expect($success)->toBeAn('array');
            expect($success)->toHaveLength(2);
            expect($failures)->toBeAn('array');
            expect($failures)->toBeEmpty();
        });

        it('Doit supporter testConnection()', function (): void {
            $result = $this->mail->testConnection();
            expect($result)->toBeAn('array');
            expect($result)->toContainKeys(['adapter', 'config_valid', 'from', 'host', 'port']);
        });
    });

    describe('__call()', function (): void {
        it('Doit proxy les appels de méthode à l\'adaptateur', function (): void {
            $mockAdapter = Mockery::mock(AbstractAdapter::class);
			foreach (['init', 'from'] as $method) {
				$mockAdapter->shouldReceive($method)->andReturn($mockAdapter);
			}
            $mockAdapter->shouldReceive('someCustomMethod')->andReturn('custom result');

			ReflectionClass::make($this->mail)->setValue('adapter', $mockAdapter);

			$result = $this->mail->someCustomMethod();
            expect($result)->toBe('custom result');
        });

        it('Doit retourner $this si l\'adaptateur retourne $this', function (): void {
            $mockAdapter = Mockery::mock(AbstractAdapter::class);
			foreach (['init', 'from', 'fluentMethod'] as $method) {
				$mockAdapter->shouldReceive($method)->andReturn($mockAdapter);
			}

			ReflectionClass::make($this->mail)->setValue('adapter', $mockAdapter);

            $result = $this->mail->fluentMethod();
            expect($result)->toBe($this->mail);
        });
    });
});
