<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Mail\Adapters\LogMailer;

use function Kahlan\expect;

describe('Mail / Adapters / LogMailer', function (): void {
    beforeEach(function (): void {
        $this->logMailer = new LogMailer(false);
    });

    describe('Initialisation', function (): void {
        it('Doit créer une instance sans erreur', function (): void {
            expect($this->logMailer)->toBeAnInstanceOf(LogMailer::class);
        });

        it('Doit accepter le mode debug', function (): void {
            $logMailer = new LogMailer(true);
            expect($logMailer)->toBeAnInstanceOf(LogMailer::class);
        });
    });

    describe('Méthodes de configuration', function (): void {
        it('Doit accepter setPort()', function (): void {
            $result = $this->logMailer->setPort(587);
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter setHost()', function (): void {
            $result = $this->logMailer->setHost('smtp.example.com');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter setUsername()', function (): void {
            $result = $this->logMailer->setUsername('user');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter setPassword()', function (): void {
            $result = $this->logMailer->setPassword('pass');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter setDebug()', function (): void {
            $result = $this->logMailer->setDebug(1);
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter setProtocol()', function (): void {
            $result = $this->logMailer->setProtocol('smtp');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter setTimeout()', function (): void {
            $result = $this->logMailer->setTimeout(30);
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter setCharset()', function (): void {
            $result = $this->logMailer->setCharset('UTF-8');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter setPriority()', function (): void {
            $result = $this->logMailer->setPriority(LogMailer::PRIORITY_HIGH);
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter setEncryption()', function (): void {
            $result = $this->logMailer->setEncryption('tls');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter setEncryption(null)', function (): void {
            $result = $this->logMailer->setEncryption(null);
            expect($result)->toBe($this->logMailer);
        });
    });

    describe('Méthodes de mail', function (): void {
        it('Doit accepter from()', function (): void {
            $result = $this->logMailer->from('sender@example.com', 'Sender');
            expect($result)->toBe($this->logMailer);

            // Vérifier que l'adresse est stockée
            $sent = $this->logMailer->getSentMessages();
            expect($sent)->toBeEmpty(); // Pas encore envoyé
        });

        it('Doit accepter to() avec une seule adresse', function (): void {
            $result = $this->logMailer->to('recipient@example.com', 'Recipient');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter to() avec plusieurs adresses', function (): void {
            $result = $this->logMailer->to([
                'recipient1@example.com' => 'Recipient 1',
                'recipient2@example.com' => 'Recipient 2'
            ]);
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter subject()', function (): void {
            $result = $this->logMailer->subject('Test Subject');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter html()', function (): void {
            $result = $this->logMailer->html('<p>HTML content</p>');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter text()', function (): void {
            $result = $this->logMailer->text('Text content');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter alt()', function (): void {
            $result = $this->logMailer->alt('Alternative text');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter cc()', function (): void {
            $result = $this->logMailer->cc('cc@example.com', 'CC Name');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter bcc()', function (): void {
            $result = $this->logMailer->bcc('bcc@example.com', 'BCC Name');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter replyTo()', function (): void {
            $result = $this->logMailer->replyTo('reply@example.com', 'Reply Name');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter header()', function (): void {
            $result = $this->logMailer->header('X-Custom', 'Value');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter header() avec un tableau', function (): void {
            $result = $this->logMailer->header(['X-Custom' => 'Value', 'X-Another' => 'Another']);
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter attach()', function (): void {
            $result = $this->logMailer->attach(__FILE__, 'test.php');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter attach() avec un tableau', function (): void {
            $result = $this->logMailer->attach([__FILE__ => 'test.php']);
            expect($result)->toBe($this->logMailer);
        });

        it('Doit ignorer attach() pour un fichier inexistant', function (): void {
            $result = $this->logMailer->attach('/nonexistent/file.pdf', 'test.pdf');
            expect($result)->toBe($this->logMailer);
            // Ne devrait pas lever d'exception
        });

        it('Doit accepter attachBinary()', function (): void {
            $result = $this->logMailer->attachBinary('binary data', 'file.txt', 'text/plain');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter embedded()', function (): void {
            $result = $this->logMailer->embedded(__FILE__, 'cid123', 'image.jpg', 'image/jpeg');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter embeddedBinary()', function (): void {
            $result = $this->logMailer->embeddedBinary('binary data', 'cid123', 'image.jpg', 'image/jpeg');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter dkim()', function (): void {
            $result = $this->logMailer->dkim('private-key', 'passphrase', 'selector', 'example.com');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter sign()', function (): void {
            $result = $this->logMailer->sign('cert.pem', 'key.pem', 'password');
            expect($result)->toBe($this->logMailer);
        });

        it('Doit accepter message()', function (): void {
            $result = $this->logMailer->message('Test message');
            expect($result)->toBe($this->logMailer);
        });
    });

    describe('send()', function (): void {
        it('Doit stocker le mail envoyé', function (): void {
            $this->logMailer->to('test@example.com')
                          ->subject('Test')
                          ->html('<p>Content</p>')
                          ->send();

            $sent = $this->logMailer->getSentMessages();
            expect($sent)->toBeAn('array');
            expect($sent)->toHaveLength(1);
            expect($sent[0]['subject'])->toBe('Test');
            expect($sent[0]['html'])->toBe('<p>Content</p>');
        });

        it('Doit logger en mode debug', function (): void {
            $logMailer = new LogMailer(true);

            $logMailer->to('test@example.com')
                     ->subject('Test')
                     ->send();

			$logs = array_map(fn($log) => $log['msg'], logger()->logCache);
			expect($logs)->toContain('[Mail][LOG] Message stocké');
			logger()->logCache = [];
        });

        it('Doit ne pas logger sans mode debug', function (): void {
            $this->logMailer->to('test@example.com')
                          ->subject('Test')
                          ->send();

            $logs = array_map(fn($log) => $log['msg'], logger()->logCache);
			expect($logs)->not->toContain('[Mail][LOG] Message stocké');
        });

        it('Doit générer un message ID', function (): void {
            $this->logMailer->to('test@example.com')
                          ->subject('Test')
                          ->send();

            $sent = $this->logMailer->getSentMessages();
            expect($sent[0]['message_id'])->toMatch('/^log-/');
        });

        it('Doit inclure un timestamp', function (): void {
            $this->logMailer->to('test@example.com')
                          ->subject('Test')
                          ->send();

            $sent = $this->logMailer->getSentMessages();
            expect($sent[0]['timestamp'])->toBeGreaterThan(0);
        });

        it('Doit vider la configuration après envoi', function (): void {
            $this->logMailer->to('test@example.com')
                          ->subject('Test')
                          ->html('Content')
                          ->send();

            // Envoyer un autre mail
            $this->logMailer->to('another@example.com')
                          ->subject('Another')
                          ->send();

            $sent = $this->logMailer->getSentMessages();
            expect($sent)->toHaveLength(2);
            expect($sent[1]['subject'])->toBe('Another');
        });
    });

    describe('clear()', function (): void {
        it('Doit vider la configuration', function (): void {
            $this->logMailer->to('test@example.com')
                          ->subject('Test')
                          ->html('Content');

            $this->logMailer->clear();

            $this->logMailer->send();
            $sent = $this->logMailer->getSentMessages();
            expect($sent[0]['subject'])->toBe('');
        });

        it('Doit vider les erreurs', function (): void {
            $this->logMailer->attach('/nonexistent/file.pdf', 'test.pdf');
            expect($this->logMailer->getLastError())->not->toBeNull();

            $this->logMailer->clear();
            expect($this->logMailer->getLastError())->toBeNull();
        });
    });

    describe('Gestion des erreurs', function (): void {
        it('Doit stocker les erreurs de pièces jointes', function (): void {
            $this->logMailer->attach('/nonexistent/file.pdf', 'test.pdf');
            expect($this->logMailer->getLastError())->toBe('Fichier non trouvé: /nonexistent/file.pdf');
        });

        it('Doit stocker les erreurs de taille de fichier', function (): void {
            // Créer un fichier temporaire trop gros
            $tempFile = tempnam(sys_get_temp_dir(), 'test');
            file_put_contents($tempFile, str_repeat('x', 11 * 1024 * 1024)); // 11MB

            $this->logMailer->attach($tempFile, 'large.pdf');
            expect($this->logMailer->getLastError())->toBe('Fichier trop volumineux: ' . $tempFile);

            unlink($tempFile);
        });

        it('Doit stocker les erreurs de type MIME', function (): void {
            expect(fn() => $this->logMailer->attachBinary('data', 'file.exe', 'application/x-msdownload'))
				->toThrow(new InvalidArgumentException('Type MIME non autorisé: application/x-msdownload'));
        });
    });

    describe('Méthodes utilitaires', function (): void {
        it('Doit retourner les mails envoyés', function (): void {
            $this->logMailer->to('test@example.com')->subject('Test')->send();
            $this->logMailer->to('test2@example.com')->subject('Test2')->send();

            $sent = $this->logMailer->getSentMessages();
            expect($sent)->toHaveLength(2);
            expect($sent[0]['subject'])->toBe('Test');
            expect($sent[1]['subject'])->toBe('Test2');
        });

        it('Doit vider les mails envoyés', function (): void {
            $this->logMailer->to('test@example.com')->subject('Test')->send();

            $this->logMailer->clearSentMessages();
            $sent = $this->logMailer->getSentMessages();
            expect($sent)->toBeEmpty();
        });

        it('Doit compter les mails envoyés', function (): void {
            expect($this->logMailer->countSentMessages())->toBe(0);

            $this->logMailer->to('test@example.com')->subject('Test')->send();
            expect($this->logMailer->countSentMessages())->toBe(1);

            $this->logMailer->to('test2@example.com')->subject('Test2')->send();
            expect($this->logMailer->countSentMessages())->toBe(2);
        });
    });

    describe('lastId()', function (): void {
        it('Doit générer un ID unique', function (): void {
            $id1 = $this->logMailer->lastId();
            $id2 = $this->logMailer->lastId();

            expect($id1)->not->toBe($id2);
            expect($id1)->toMatch('/^log-/');
            expect($id2)->toMatch('/^log-/');
        });
    });
});
