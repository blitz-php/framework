<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Mail\Adapters;

use InvalidArgumentException;

/**
 * Adaptateur de mail pour journalisation.
 * N'envoie pas réellement les mails mais les stocke pour inspection.
 * Utile pour le développement et les tests.
 */
class LogMailer extends AbstractAdapter
{
    /**
     * Configuration du mailer
     *
     * @var array{
     *     to: array<array{0: string, 1: string}>,
     *     from: array{0: string, 1: string},
     *     cc: array<array{0: string, 1: string}>,
     *     bcc: array<array{0: string, 1: string}>,
     *     replyTo: array<array{0: string, 1: string}>,
     *     subject: string,
     *     html: string,
     *     text: string,
     *     alt: string,
     *     attachments: array<array{
     *         path: string,
     *         name: string,
     *         type: string,
     *         disposition: string
     *     }>,
     *     headers: array<string, string>,
     *     priority: int
     * }
     */
    protected $mailer = [
        'to'          => [],
        'from'        => ['', ''],
        'cc'          => [],
        'bcc'         => [],
        'replyTo'     => [],
        'subject'     => '',
        'html'        => '',
        'text'        => '',
        'alt'         => '',
        'attachments' => [],
        'headers'     => [],
        'priority'    => self::PRIORITY_NORMAL,
    ];

    /**
     * Mails envoyés (stockés pour inspection)
     *
     * @var array<array{
     *     to: array<array{0: string, 1: string}>,
     *     from: array{0: string, 1: string},
     *     cc: array<array{0: string, 1: string}>,
     *     bcc: array<array{0: string, 1: string}>,
     *     replyTo: array<array{0: string, 1: string}>,
     *     subject: string,
     *     html: string,
     *     text: string,
     *     alt: string,
     *     attachments: array<array{
     *         path: string,
     *         name: string,
     *         type: string,
     *         disposition: string
     *     }>,
     *     headers: array<string, string>,
     *     priority: int,
     *     timestamp: float
     * }>
     */
    protected array $sent = [];

    /**
     * Mode debug
     */
    protected bool $debug = false;

    /**
     * {@inheritDoc}
     */
    public function setPort(int $port): static
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setHost(string $host): static
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setUsername(string $username): static
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setPassword(string $password): static
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setDebug(int $debug = 1): static
    {
        $this->debug = $debug > 0;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setProtocol(string $protocol): static
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setTimeout(int $timeout): static
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setCharset(string $charset): static
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setPriority(int $priority): static
    {
        if (in_array($priority, static::PRIORITY_MAP, true)) {
            $this->mailer['priority'] = $priority;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setEncryption(?string $encryption): static
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): static
    {
        $this->mailer = [
            'to'          => [],
            'from'        => ['', ''],
            'cc'          => [],
            'bcc'         => [],
            'replyTo'     => [],
            'subject'     => '',
            'html'        => '',
            'text'        => '',
            'alt'         => '',
            'attachments' => [],
            'headers'     => [],
            'priority'    => self::PRIORITY_NORMAL,
        ];
        $this->clearError();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function alt(string $content): static
    {
        $this->mailer['alt'] = $content;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function doAttach(array $path, string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'attachment'): static
    {
        foreach ($path as $filePath => $fileName) {
            $this->mailer['attachments'][] = [
                'path'        => $filePath,
                'name'        => $fileName,
                'type'        => $type,
                'disposition' => $disposition,
            ];
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function attachBinary($binary, string $name, string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'attachment'): static
    {
        if (! $this->isValidMimeType($type)) {
            throw new InvalidArgumentException(sprintf('Type MIME non autorisé: %s', $type));
        }

        $this->mailer['attachments'][] = [
            'path'        => '[binary_data]',
            'name'        => $name,
            'type'        => $type,
            'disposition' => $disposition,
        ];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function bcc(array|string $address, bool|string $name = '', bool $set = false): static
    {
        [$addresses, $set] = $this->parseMultipleAddresses($address, $name, $set);

        if ($set) {
            $this->mailer['bcc'] = [];
        }

        foreach ($addresses as $address) {
            $this->mailer['bcc'][] = $address;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function cc(array|string $address, bool|string $name = '', bool $set = false): static
    {
        [$addresses, $set] = $this->parseMultipleAddresses($address, $name, $set);

        if ($set) {
            $this->mailer['cc'] = [];
        }

        foreach ($addresses as $address) {
            $this->mailer['cc'][] = $address;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function dkim(string $pk, string $passphrase = '', string $selector = '', string $domain = ''): static
    {
        // Simule la signature DKIM
        $this->mailer['headers']['DKIM-Signature'] = 'v=1; a=rsa-sha256; c=relaxed/relaxed;';

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function embedded(string $path, string $cid, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'inline'): static
    {
        if (! file_exists($path)) {
            throw new InvalidArgumentException(sprintf('Fichier non trouvé: %s', $path));
        }

        if (! $this->isValidMimeType($type)) {
            throw new InvalidArgumentException(sprintf('Type MIME non autorisé: %s', $type));
        }

        $this->mailer['attachments'][] = [
            'path'        => $path,
            'name'        => $name ?: basename($path),
            'type'        => $type,
            'disposition' => 'inline',
            'cid'         => $cid,
        ];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function embeddedBinary($binary, string $cid, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'inline'): static
    {
        if (! $this->isValidMimeType($type)) {
            throw new InvalidArgumentException(sprintf('Type MIME non autorisé: %s', $type));
        }

        $this->mailer['attachments'][] = [
            'path'        => '[binary_data]',
            'name'        => $name,
            'type'        => $type,
            'disposition' => 'inline',
            'cid'         => $cid,
        ];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function from(string $address, string $name = ''): static
    {
        [$email, $name]       = $this->makeAddress($address, $name);
        $this->mailer['from'] = [$email, $name];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function header(array|string $name, ?string $value = null): static
    {
        if (is_string($name)) {
            $name = [$name => $value];
        }

        foreach ($name as $key => $value) {
            $this->mailer['headers'][$key] = $value;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function html(string $content): static
    {
        $this->mailer['html'] = $content;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function lastId(): string
    {
        return 'log-' . md5(uniqid(microtime(true)) . serialize($this->mailer));
    }

    /**
     * {@inheritDoc}
     */
    public function message(string $message): static
    {
        $this->mailer['html'] = $message;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function replyTo(array|string $address, bool|string $name = '', bool $set = false): static
    {
        [$addresses, $set] = $this->parseMultipleAddresses($address, $name, $set);

        if ($set) {
            $this->mailer['replyTo'] = [];
        }

        foreach ($addresses as $address) {
            $this->mailer['replyTo'][] = $address;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function send(): bool
    {
        $message               = $this->mailer;
        $message['timestamp']  = microtime(true);
        $message['message_id'] = $this->lastId();

        $this->sent[] = $message;

        if ($this->debug) {
            logger()->info('[Mail][LOG] Message stocké', [
                'to'          => count($message['to']),
                'subject'     => $message['subject'],
                'attachments' => count($message['attachments']),
                'timestamp'   => date('Y-m-d H:i:s', (int) $message['timestamp']),
            ]);
        }

        // Réinitialise pour le prochain envoi
        $this->clear();

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function sign(string $cert_filename, string $key_filename, string $key_pass, string $extracerts_filename = ''): static
    {
        // Simule la signature S/MIME
        $this->mailer['headers']['X-Signed'] = 'S/MIME';

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function subject(string $subject): static
    {
        $this->mailer['subject'] = $subject;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function text(string $content): static
    {
        $this->mailer['text'] = $content;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function to(array|string $address, bool|string $name = '', bool $set = false): static
    {
        [$addresses, $set] = $this->parseMultipleAddresses($address, $name, $set);

        if ($set) {
            $this->mailer['to'] = [];
        }

        foreach ($addresses as $address) {
            $this->mailer['to'][] = $address;
        }

        return $this;
    }

    /**
     * Récupère tous les mails envoyés (stockés)
     *
     * @return array<array{
     *     to: array<array{0: string, 1: string}>,
     *     from: array{0: string, 1: string},
     *     cc: array<array{0: string, 1: string}>,
     *     bcc: array<array{0: string, 1: string}>,
     *     replyTo: array<array{0: string, 1: string}>,
     *     subject: string,
     *     html: string,
     *     text: string,
     *     alt: string,
     *     attachments: array<array{
     *         path: string,
     *         name: string,
     *         type: string,
     *         disposition: string
     *     }>,
     *     headers: array<string, string>,
     *     priority: int,
     *     timestamp: float
     * }>
     */
    public function getSentMessages(): array
    {
        return $this->sent;
    }

    /**
     * Vide les mails stockés
     */
    public function clearSentMessages(): void
    {
        $this->sent = [];
    }

    /**
     * Compte le nombre de mails stockés
     */
    public function countSentMessages(): int
    {
        return count($this->sent);
    }
}
