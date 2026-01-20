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
use RuntimeException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Crypto\DkimSigner;
use Symfony\Component\Mime\Crypto\SMimeSigner;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;
use Throwable;

/**
 * Adaptateur utilisant Symfony Mailer comme moteur d'envoi
 *
 * @see https://symfony.com/doc/current/mailer.html
 */
class SymfonyMailer extends AbstractAdapter
{
    /**
     * {@inheritDoc}
     */
    protected array $dependancies = [
        ['class' => Mailer::class, 'package' => 'symfony/mailer'],
    ];

    /**
     * Instance Email de Symfony
     *
     * @var Email
     */
    protected $mailer;

    /**
     * Transporteur Symfony
     */
    private ?Mailer $transporter = null;

    /**
     * Charset du message
     */
    private string $charset = self::CHARSET_UTF8;

    /**
     * DSN (Data Source Name) de connexion
     */
    private string $dsn = '';

    /**
     * Protocole d'envoi
     */
    private string $protocol = self::PROTOCOL_SMTP;

    /**
     * Timeout de connexion en secondes
     */
    private int $timeout = 30;

    /**
     * Port SMTP
     */
    private int $port = 587;

    /**
     * Hôte SMTP
     */
    private string $host = 'localhost';

    /**
     * Nom d'utilisateur SMTP
     */
    private string $username = '';

    /**
     * Mot de passe SMTP
     */
    private string $password = '';

    /**
     * Chiffrement SMTP
     */
    private ?string $encryption = self::ENCRYPTION_TLS;

    /**
     * Niveau de debug
     */
    private int $debug = 0;

    /**
     * {@inheritDoc}
     */
    public function __construct(bool $debug = false)
    {
        $this->mailer = new Email();
        parent::__construct($debug);
    }

    /**
     * Définit le DSN (Data Source Name) complet
     *
     * @param string $dsn DSN de connexion
     */
    public function setDsn(string $dsn): static
    {
        $this->dsn = $dsn;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setPort(int $port): static
    {
        $this->port = $port;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setHost(string $host): static
    {
        $this->host = $host;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setDebug(int $debug = 1): static
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setProtocol(string $protocol): static
    {
        $this->protocol = $protocol;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setTimeout(int $timeout): static
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setCharset(string $charset): static
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setPriority(int $priority): static
    {
        if (in_array($priority, static::PRIORITY_MAP, true)) {
            $this->mailer->priority($priority);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setEncryption(?string $encryption): static
    {
        if ($encryption === static::ENCRYPTION_NONE) {
            $encryption = null;
        }

        if (in_array($encryption, [null, static::ENCRYPTION_SSL, static::ENCRYPTION_TLS], true)) {
            $this->encryption = $encryption;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): static
    {
        $this->mailer      = new Email();
        $this->transporter = null;

        $this->clearError();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function alt(string $content): static
    {
        $this->mailer->text($content);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function attachBinary($binary, string $name, string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'attachment'): static
    {
        if (!$this->isValidMimeType($type)) {
            throw new InvalidArgumentException(sprintf('Type MIME non autorisé: %s', $type));
        }

        $this->mailer->addPart(new DataPart($binary, $name, $type));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function bcc(array|string $address, bool|string $name = '', bool $set = false): static
    {
        [$addresses, $set] = $this->parseMultipleAddresses($address, $name, $set);

        if ($set) {
            $this->mailer->bcc(...$addresses);
        } else {
            $this->mailer->addBcc(...$addresses);
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
            $this->mailer->cc(...$addresses);
        } else {
            $this->mailer->addCc(...$addresses);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function dkim(string $pk, string $passphrase = '', string $selector = '', string $domain = ''): static
    {
        $signer = new DkimSigner(
            $pk,
            $domain ?: parse_url(site_url(), PHP_URL_HOST),
            $selector ?: 'blitz',
            [],
            $passphrase
        );

        $this->mailer = $signer->sign($this->mailer);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function embedded(string $path, string $cid, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'inline'): static
    {
        if (!$this->isValidMimeType($type)) {
            throw new InvalidArgumentException(sprintf('Type MIME non autorisé: %s', $type));
        }

        $this->mailer->addPart(
            (new DataPart(
                new File($path),
                $cid,
                $type
            ))->asInline()
        );

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function embeddedBinary($binary, string $cid, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'inline'): static
    {
        if (!$this->isValidMimeType($type)) {
            throw new InvalidArgumentException(sprintf('Type MIME non autorisé: %s', $type));
        }

        $this->mailer->addPart(
            (new DataPart(
                $binary,
                $cid,
                $type
            ))->asInline()
        );

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function from(string $address, string $name = ''): static
    {
        $this->mailer->from($this->makeAddress($address, $name));

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
            $this->mailer->getHeaders()->addTextHeader($key, $value);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function html(string $content): static
    {
        $this->mailer->html($content, $this->charset);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function message(string $message): static
    {
        $this->mailer->html($message, $this->charset);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function replyTo(array|string $address, bool|string $name = '', bool $set = false): static
    {
        [$addresses, $set] = $this->parseMultipleAddresses($address, $name, $set);

        if ($set) {
            $this->mailer->replyTo(...$addresses);
        } else {
            $this->mailer->addReplyTo(...$addresses);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function send(): bool
    {
        try {
            $this->transporter()->send($this->mailer);

            return true;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();

            if ($this->debug > 0) {
                throw new RuntimeException(
                    sprintf('Erreur d\'envoi de mail: %s', $e->getMessage()),
                    $e->getCode(),
                    $e
                );
            }

            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function sign(string $cert_filename, string $key_filename, string $key_pass, string $extracerts_filename = ''): static
    {
        $signer = new SMimeSigner($cert_filename, $key_filename, $key_pass, $extracerts_filename);
        $this->mailer = $signer->sign($this->mailer);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function subject(string $subject): static
    {
        $this->mailer->subject($subject);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function text(string $content): static
    {
        $this->mailer->text($content, $this->charset);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function to(array|string $address, bool|string $name = '', bool $set = false): static
    {
        [$addresses, $set] = $this->parseMultipleAddresses($address, $name, $set);

        if ($set) {
            $this->mailer->to(...$addresses);
        } else {
            $this->mailer->addTo(...$addresses);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function lastId(): string
    {
        return $this->mailer->generateMessageId();
    }

    /**
     * {@inheritDoc}
     */
    protected function doAttach(array $path, string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'attachment'): static
    {
        foreach ($path as $filePath => $fileName) {
            $this->mailer->addPart(DataPart::fromPath($filePath, $fileName, $type));
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return Address
     */
    protected function makeAddress(string $email, string $name)
    {
        [$email, $name] = parent::makeAddress($email, $name);

        return new Address($email, $name);
    }

    /**
     * Récupère ou crée le transporteur Symfony
     */
    private function transporter(): Mailer
    {
        if (null !== $this->transporter) {
            return $this->transporter;
        }

        $dsn = $this->buildDsn();
        $transport = Transport::fromDsn($dsn);

        return $this->transporter = new Mailer($transport);
    }

    /**
     * Construit le DSN (Data Source Name) à partir de la configuration
     */
    private function buildDsn(): string
    {
        if ($this->dsn !== '') {
            return $this->dsn;
        }

        $username = urlencode($this->username);
        $password = urlencode($this->password);
        $host     = urlencode($this->host);

        return match ($this->protocol) {
            static::PROTOCOL_SMTP => sprintf(
                'smtp://%s:%s@%s:%d%s',
                $username,
                $password,
                $host,
                $this->port,
                $this->encryption ? '?encryption=' . $this->encryption : ''
            ),

            static::PROTOCOL_SENDMAIL => 'sendmail://default',

            static::PROTOCOL_MAIL => 'sendmail://default',

            static::PROTOCOL_POSTMARK => sprintf(
                'postmark+smtp://%s@default',
                $username
            ),

            static::PROTOCOL_SENDGRID => sprintf(
                'sendgrid+smtp://%s@default',
                $username // username contient l'API Key
            ),

            static::PROTOCOL_MAILGUN => sprintf(
                'mailgun+smtp://%s:%s@default',
                $username,
                $password
            ),

            default => sprintf(
                '%s+smtp://%s:%s@default',
                $this->protocol,
                $username,
                $password
            ),
        };
    }

    /**
     * Définit des options supplémentaires pour le transport
     *
     * @param array<string, mixed> $options Options de transport
     */
    public function setTransportOptions(array $options): static
    {
        $this->transporter = null; // Force la recréation avec nouvelles options

        if (empty($this->dsn)) {
            $this->dsn = $this->buildDsn();
        }

        // Ajoute les options au DSN
        if ($options !== []) {
            $dsn       = Dsn::fromString($this->dsn);
            $query     = http_build_query($options);
            $this->dsn = (string) $dsn->withOption($query);
        }

        return $this;
    }
}
