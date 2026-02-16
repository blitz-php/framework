<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Mail\Mailable;
use BlitzPHP\Mail\Mail;
use Kahlan\Plugin\Double;
use BlitzPHP\Contracts\Event\EventManagerInterface;

use function Kahlan\expect;

describe('Mail / Mailable', function (): void {
    beforeEach(function (): void {
        // Mock du mailer
        $this->mockMail = Mockery::mock(Mail::class);
		$this->mockMail->shouldReceive('bcc');
		$this->mockMail->shouldReceive('cc');
		$this->mockMail->shouldReceive('from');
		$this->mockMail->shouldReceive('view');
		$this->mockMail->shouldReceive('html');
		$this->mockMail->shouldReceive('text');
		$this->mockMail->shouldReceive('markdown');
		$this->mockMail->shouldReceive('header');
		$this->mockMail->shouldReceive('priority');
		$this->mockMail->shouldReceive('replyTo');
		$this->mockMail->shouldReceive('subject');
		$this->mockMail->shouldReceive('to');
		$this->mockMail->shouldReceive('attach');
		$this->mockMail->shouldReceive('send')->andReturn(true);

        // Mock de l'EventManager
        $this->mockEvent = Double::instance([
            'implements' => [EventManagerInterface::class]
        ]);

        config()->set('mail.from', [
            'address' => 'default@example.com',
            'name' => 'Default Sender'
        ]);

        // Classe de test concrète
        $this->mailable = new class extends Mailable {
            public $customProperty = 'test value';

            public function to(): array
            {
                return ['recipient@example.com' => 'Test Recipient'];
            }

            public function subject(): string
            {
                return 'Test Subject';
            }

            public function content(): array
            {
                return ['view' => 'emails.test'];
            }

            public function with(): array
            {
                return ['additional' => 'data'];
            }
        };
    });

	afterAll(function(): void {
		config()->reset('mail');
	});

    describe('Méthodes de base', function (): void {
        it('Doit retourner des pièces jointes vides par défaut', function (): void {
            $attachments = $this->mailable->attachments();
            expect($attachments)->toBeAn('array');
            expect($attachments)->toBeEmpty();
        });

        it('Doit retourner des BCC vides par défaut', function (): void {
            $bcc = $this->mailable->bcc();
            expect($bcc)->toBeAn('array');
            expect($bcc)->toBeEmpty();
        });

        it('Doit retourner des CC vides par défaut', function (): void {
            $cc = $this->mailable->cc();
            expect($cc)->toBeAn('array');
            expect($cc)->toBeEmpty();
        });

        it('Doit retourner le contenu par défaut', function (): void {
            $content = $this->mailable->content();
            expect($content)->toBeAn('array');
            expect($content)->toContainKeys(['view']);
        });

        it('Doit retourner l\'expéditeur par défaut depuis la config', function (): void {
            $from = $this->mailable->from();
            expect($from)->toBeAn('array');
            expect($from[0])->toBe('default@example.com');
            expect($from[1])->toBe('Default Sender');
        });

        it('Doit retourner des en-têtes vides par défaut', function (): void {
            $headers = $this->mailable->headers();
            expect($headers)->toBeAn('array');
            expect($headers)->toBeEmpty();
        });

        it('Doit retourner la priorité normale par défaut', function (): void {
            $priority = $this->mailable->priority();
            expect($priority)->toBe(Mail::PRIORITY_NORMAL);
        });

        it('Doit retourner des replyTo vides par défaut', function (): void {
            $replyTo = $this->mailable->replyTo();
            expect($replyTo)->toBeAn('array');
            expect($replyTo)->toBeEmpty();
        });

        it('Doit retourner un sujet vide par défaut', function (): void {
            $mailable = new class extends Mailable {};
            expect($mailable->subject())->toBe('');
        });

        it('Doit retourner des destinataires vides par défaut', function (): void {
            $mailable = new class extends Mailable {};
            $to = $mailable->to();
            expect($to)->toBeAn('array');
            expect($to)->toBeEmpty();
        });

        it('Doit retourner des données vides pour with() par défaut', function (): void {
            $with = $this->mailable->with();
            expect($with)->toBeAn('array');
            expect($with['additional'])->toBe('data');
        });
    });

    describe('data()', function (): void {
        it('Doit combiner les propriétés publiques et les données with()', function (): void {
            $data = $this->mailable->data();

            expect($data)->toBeAn('array');
            expect($data['customProperty'])->toBe('test value');
            expect($data['additional'])->toBe('data');
        });
    });

    xdescribe('send()', function (): void {
        it('Doit envoyer un mail valide', function (): void {
            expect(fn() => $this->mailable->send($this->mockMail))->not->toThrow();
        });

        it('Doit configurer BCC si présent', function (): void {
            $mailable = new class extends Mailable {
                public function to(): array { return ['test@example.com']; }
                public function subject(): string { return 'Test'; }
                public function content(): array { return ['html' => 'content']; }
                public function bcc(): array { return ['bcc@example.com' => 'BCC Name']; }
            };

            expect($this->mockMail)->toReceive('bcc');
            $mailable->send($this->mockMail);
        });

        it('Doit configurer CC si présent', function (): void {
            $mailable = new class extends Mailable {
                public function to(): array { return ['test@example.com']; }
                public function subject(): string { return 'Test'; }
                public function content(): array { return ['html' => 'content']; }
                public function cc(): array { return ['cc@example.com' => 'CC Name']; }
            };

            $mailable->send($this->mockMail);
            expect($this->mockMail)->toReceive('cc');
        });

        it('Doit configurer l\'expéditeur si spécifié', function (): void {
            $mailable = new class extends Mailable {
                public function to(): array { return ['test@example.com']; }
                public function subject(): string { return 'Test'; }
                public function content(): array { return ['html' => 'content']; }
                public function from(): array { return ['custom@example.com', 'Custom Sender']; }
            };

            $mailable->send($this->mockMail);
            expect($this->mockMail)->toReceive('from');
        });

        it('Doit configurer les en-têtes si présents', function (): void {
            $mailable = new class extends Mailable {
                public function to(): array { return ['test@example.com']; }
                public function subject(): string { return 'Test'; }
                public function content(): array { return ['html' => 'content']; }
                public function headers(): array { return ['X-Custom' => 'Value']; }
            };

            $mailable->send($this->mockMail);
            expect($this->mockMail)->toReceive('header');
        });

        it('Doit configurer la priorité', function (): void {
            $mailable = new class extends Mailable {
                public function to(): array { return ['test@example.com']; }
                public function subject(): string { return 'Test'; }
                public function content(): array { return ['html' => 'content']; }
                public function priority(): int { return Mail::PRIORITY_HIGH; }
            };

            $mailable->send($this->mockMail);
            expect($this->mockMail)->toReceive('priority');
        });

        it('Doit configurer replyTo si présent', function (): void {
            $mailable = new class extends Mailable {
                public function to(): array { return ['test@example.com']; }
                public function subject(): string { return 'Test'; }
                public function content(): array { return ['html' => 'content']; }
                public function replyTo(): array { return ['reply@example.com' => 'Reply Name']; }
            };

            $mailable->send($this->mockMail);
            expect($this->mockMail)->toReceive('replyTo');
        });

        it('Doit configurer les pièces jointes si présentes', function (): void {
            $mailable = new class extends Mailable {
                public function to(): array { return ['test@example.com']; }
                public function subject(): string { return 'Test'; }
                public function content(): array { return ['html' => 'content']; }
                public function attachments(): array {
                    return [[
                        'path' => __FILE__,
                        'name' => 'test.php',
                        'disposition' => 'attachment'
                    ]];
                }
            };

            $mailable->send($this->mockMail);
            expect($this->mockMail)->toReceive('attach');
        });

        it('Doit lever une exception pour un mail invalide', function (): void {
            $mailable = new class extends Mailable {
                public function to(): array { return []; } // Pas de destinataire
                public function subject(): string { return 'Test'; }
                public function content(): array { return ['html' => 'content']; }
            };

            expect(fn(): bool => $mailable->send($this->mockMail))
                ->toThrow(new InvalidArgumentException('Mail invalide: Aucun destinataire spécifié'));
        });
    });

    xdescribe('Edge cases', function (): void {
        it('Doit gérer les adresses avec nom ou sans nom', function (): void {
            $mailable = new class extends Mailable {
                public function to(): array {
                    return [
                        'withname@example.com' => 'With Name',
                        'noname@example.com'
                    ];
                }
                public function subject(): string { return 'Test'; }
                public function content(): array { return ['html' => 'content']; }
            };

            $mailable->send($this->mockMail);
            expect($this->mockMail)->toReceive('to')->times(2);
        });

        it('Doit ignorer les valeurs vides dans les adresses', function (): void {
            $mailable = new class extends Mailable {
                public function to(): array {
                    return [
                        '' => 'Empty Email',
                        'valid@example.com' => '',
                        'another@example.com' => 'Valid'
                    ];
                }
                public function subject(): string { return 'Test'; }
                public function content(): array { return ['html' => 'content']; }
            };

            $mailable->send($this->mockMail);
            // Doit n'appeler to() que pour les adresses valides
            expect($this->mockMail)->toReceive('to')->times(2);
        });

        it('Doit supporter markdown comme contenu', function (): void {
            $mailable = new class extends Mailable {
                public function to(): array { return ['test@example.com']; }
                public function subject(): string { return 'Test'; }
                public function content(): array { return ['markdown' => 'emails.markdown.test']; }
            };

            allow($this->mockMail)->toReceive('markdown');

            $mailable->send($this->mockMail);
            expect($this->mockMail)->toReceive('markdown');
        });

        it('Doit supporter HTML comme contenu', function (): void {
            $mailable = new class extends Mailable {
                public function to(): array { return ['test@example.com']; }
                public function subject(): string { return 'Test'; }
                public function content(): array { return ['html' => '<p>Content</p>']; }
            };

            $mailable->send($this->mockMail);
            expect($this->mockMail)->toReceive('html');
        });

        it('Doit supporter texte comme contenu', function (): void {
            $mailable = new class extends Mailable {
                public function to(): array { return ['test@example.com']; }
                public function subject(): string { return 'Test'; }
                public function content(): array {
                    return [
                        'html' => '<p>Content</p>',
                        'text' => 'Text Content'
                    ];
                }
            };

            $mailable->send($this->mockMail);
            expect($this->mockMail)->toReceive('html');
            expect($this->mockMail)->toReceive('text');
        });

        it('Doit gérer les pièces jointes inexistantes', function (): void {
            $mailable = new class extends Mailable {
                public function to(): array { return ['test@example.com']; }
                public function subject(): string { return 'Test'; }
                public function content(): array { return ['html' => 'content']; }
                public function attachments(): array {
                    return [[
                        'path' => '/nonexistent/file.pdf',
                        'name' => 'test.pdf'
                    ]];
                }
            };

            $mailable->send($this->mockMail);
            // attach() ne doit pas être appelé pour un fichier inexistant
            expect($this->mockMail)->not->toReceive('attach');
        });
    });
});
