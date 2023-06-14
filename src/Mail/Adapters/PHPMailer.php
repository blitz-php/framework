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

use BlitzPHP\Loader\Services;
use PHPMailer\PHPMailer\PHPMailer as Mailer;
use PHPMailer\PHPMailer\SMTP;

class PHPMailer extends AbstractAdapter
{
    /**
     * {@inheritDoc}
     */
    protected array $dependancies = [
        ['class' => Mailer::class, 'package' => 'phpmailer/phpmailer'],
    ];

    /**
     * @var Mailer
     */
    protected $mailer;

    public function __construct(bool $debug = false)
    {
        $this->mailer              = new Mailer();
        $this->mailer->Debugoutput = function ($str, $level) {
            Services::logger()->info('[Mail][' . $level . ']: ' . $str);
        };

        parent::__construct($debug);
    }

    /**
     * {@inheritDoc}
     */
    public function init(array $config): self
    {
        if (! empty($config['username']) && ! empty($config['password'])) {
            $this->mailer->SMTPAuth = true;
        }

        return parent::init($config);
    }

    /**
     * {@inheritDoc}
     */
    public function setPort(int $port): self
    {
        $this->mailer->Port = $port;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setHost(string $host): self
    {
        $this->mailer->Host = $host;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setUsername(string $username): self
    {
        $this->mailer->Username = $username;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setPassword(string $password): self
    {
        $this->mailer->Password = $password;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setDebug(int $debug = SMTP::DEBUG_SERVER): self
    {
        $this->mailer->SMTPDebug = $debug;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setProtocol(string $protocol): self
    {
        switch (strtolower($protocol)) {
            case static::PROTOCOL_MAIL:
                $this->mailer->isMail();
                break;

            case static::PROTOCOL_QMAIL:
                $this->mailer->isQmail();
                break;

            case static::PROTOCOL_SENDMAIL:
                $this->mailer->isSendmail();
                break;

            default:
                $this->mailer->isSMTP();
                break;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setTimeout(int $timeout): self
    {
        $this->mailer->Timeout = $timeout;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setCharset(string $charset): self
    {
        $this->mailer->CharSet = $charset;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setPriority(int $priority): self
    {
        if (in_array($priority, static::PRIORITY_MAP, true)) {
            $this->mailer->Priority = $priority;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setEncryption(?string $encryption): self
    {
        if (in_array($encryption, [null, static::ENCRYPTION_SSL, static::ENCRYPTION_TLS], true)) {
            $this->mailer->SMTPSecure = $encryption;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function alt(string $content): self
    {
        $this->mailer->AltBody = $content;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function attach(array|string $path, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'attachment'): self
    {
        if (is_string($path)) {
            $path = [$path => $name];
        }

        foreach ($path as $key => $value) {
            $this->mailer->addAttachment($key, $value, $encoding, $type, $disposition);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function attachBinary($binary, string $name, string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'attachment'): self
    {
        $this->mailer->addStringAttachment($binary, $name, $encoding, $type, $disposition);

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function bcc(array|string $address, bool|string $name = '', bool $set = false): self
    {
        [$addresses, $set] = $this->parseMultipleAddresses($address, $name, $set);

        if ($set) {
            $this->mailer->clearBCCs();
        }

        foreach ($addresses as $address) {
            $this->mailer->addBCC(...$address);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function cc(array|string $address, bool|string $name = '', bool $set = false): self
    {
        [$addresses, $set] = $this->parseMultipleAddresses($address, $name, $set);

        if ($set) {
            $this->mailer->clearCCs();
        }

        foreach ($addresses as $address) {
            $this->mailer->addCC(...$address);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function dkim(string $pk, string $passphrase = '', string $selector = '', string $domain = ''): self
    {
        $this->mailer->DKIM_domain     = $domain;
        $this->mailer->DKIM_private    = $pk;
        $this->mailer->DKIM_selector   = $selector ?: 'blitz';
        $this->mailer->DKIM_passphrase = $passphrase;
        $this->mailer->DKIM_identity   = $this->mailer->From;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function embedded(string $path, string $cid, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'inline'): self
    {
        $this->mailer->addEmbeddedImage($path, $cid, $name, $encoding, $type, $disposition);

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function embeddedBinary($binary, string $cid, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'inline'): self
    {
        $this->mailer->addStringEmbeddedImage($binary, $cid, $name, $encoding, $type, $disposition);

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function from(string $address, string $name = ''): self
    {
        $this->mailer->setFrom($address, $name);

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function header(array|string $name, ?string $value = null): self
    {
        if (is_string($name)) {
            $name = [$name => $value];
        }

        foreach ($name as $key => $value) {
            $this->mailer->addCustomHeader($key, $value);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function html(string $content): self
    {
        $this->mailer->isHTML(true);

        return $this->message($content);
    }

    /**
     * {@inheritDoc}
     */
    public function lastId(): string
    {
        return $this->mailer->getLastMessageID();
    }

    /**
     * {@inheritDoc}
     */
    public function message(string $message): self
    {
        $this->mailer->Body = $message;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function replyTo(array|string $address, bool|string $name = '', bool $set = false): self
    {
        [$addresses, $set] = $this->parseMultipleAddresses($address, $name, $set);

        if ($set) {
            $this->mailer->clearReplyTos();
        }

        foreach ($addresses as $address) {
            $this->mailer->addReplyTo(...$address);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function send(): bool
    {
        return $this->mailer->send();
    }

    /**
     * {@inheritDoc}
     */
    public function sign(string $cert_filename, string $key_filename, string $key_pass, string $extracerts_filename = ''): self
    {
        $this->mailer->sign($cert_filename, $key_filename, $key_pass, $extracerts_filename);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function subject(string $subject): self
    {
        $this->mailer->Subject = $subject;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function text(string $content): self
    {
        $this->mailer->isHTML(false);

        return $this->message($content);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function to(array|string $address, bool|string $name = '', bool $set = false): self
    {
        [$addresses, $set] = $this->parseMultipleAddresses($address, $name, $set);

        if ($set) {
            $this->mailer->clearAddresses();
        }

        foreach ($addresses as $address) {
            $this->mailer->addAddress(...$address);
        }

        return $this;
    }
}
