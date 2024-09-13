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

use BlitzPHP\Container\Services;
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
        $this->mailer->Debugoutput = static function ($str, $level): void {
            Services::logger()->info('[Mail][' . $level . ']: ' . $str);
        };

        parent::__construct($debug);
    }

    /**
     * {@inheritDoc}
     */
    public function init(array $config): static
    {
        if (! empty($config['username']) && ! empty($config['password'])) {
            $this->mailer->SMTPAuth = true;
        }

        return parent::init($config);
    }

    /**
     * {@inheritDoc}
     */
    public function setPort(int $port): static
    {
        $this->mailer->Port = $port;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setHost(string $host): static
    {
        $this->mailer->Host = $host;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setUsername(string $username): static
    {
        $this->mailer->Username = $username;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setPassword(string $password): static
    {
        $this->mailer->Password = $password;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setDebug(int $debug = SMTP::DEBUG_SERVER): static
    {
        $this->mailer->SMTPDebug = $debug;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setProtocol(string $protocol): static
    {
        match (strtolower($protocol)) {
            static::PROTOCOL_MAIL     => $this->mailer->isMail(),
            static::PROTOCOL_QMAIL    => $this->mailer->isQmail(),
            static::PROTOCOL_SENDMAIL => $this->mailer->isSendmail(),
            default                   => $this->mailer->isSMTP(),
        };

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setTimeout(int $timeout): static
    {
        $this->mailer->Timeout = $timeout;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setCharset(string $charset): static
    {
        $this->mailer->CharSet = $charset;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setPriority(int $priority): static
    {
        if (in_array($priority, static::PRIORITY_MAP, true)) {
            $this->mailer->Priority = $priority;
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
            $this->mailer->SMTPSecure = $encryption;
        }

        return $this;
    }

	/**
	 * {@inheritDoc}
	 */
	public function clear(): self
	{
		$this->mailer->clearAddresses();
		$this->mailer->clearAllRecipients();
		$this->mailer->clearAttachments();
		$this->mailer->clearBCCs();
		$this->mailer->clearCCs();
		$this->mailer->clearCustomHeaders();
		$this->mailer->clearReplyTos();

		return $this;
	}

    /**
     * {@inheritDoc}
     */
    public function alt(string $content): static
    {
        $this->mailer->AltBody = $content;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function attach(array|string $path, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'attachment'): static
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
    public function attachBinary($binary, string $name, string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'attachment'): static
    {
        $this->mailer->addStringAttachment($binary, $name, $encoding, $type, $disposition);

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function bcc(array|string $address, bool|string $name = '', bool $set = false): static
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
    public function cc(array|string $address, bool|string $name = '', bool $set = false): static
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
    public function dkim(string $pk, string $passphrase = '', string $selector = '', string $domain = ''): static
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
    public function embedded(string $path, string $cid, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'inline'): static
    {
        $this->mailer->addEmbeddedImage($path, $cid, $name, $encoding, $type, $disposition);

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function embeddedBinary($binary, string $cid, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'inline'): static
    {
        $this->mailer->addStringEmbeddedImage($binary, $cid, $name, $encoding, $type, $disposition);

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function from(string $address, string $name = ''): static
    {
        $this->mailer->setFrom($address, $name);

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function header(array|string $name, ?string $value = null): static
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
    public function html(string $content): static
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
    public function message(string $message): static
    {
        $this->mailer->Body = $message;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function replyTo(array|string $address, bool|string $name = '', bool $set = false): static
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
    public function sign(string $cert_filename, string $key_filename, string $key_pass, string $extracerts_filename = ''): static
    {
        $this->mailer->sign($cert_filename, $key_filename, $key_pass, $extracerts_filename);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function subject(string $subject): static
    {
        $this->mailer->Subject = $subject;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function text(string $content): static
    {
        $this->mailer->isHTML(false);

        return $this->message($content);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function to(array|string $address, bool|string $name = '', bool $set = false): static
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
