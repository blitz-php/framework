<?php

namespace BlitzPHP\Mail\Adapters;

use PHPMailer\PHPMailer\PHPMailer as Mailer;
use PHPMailer\PHPMailer\SMTP;

class PHPMailer extends AbstractAdapter
{
    /**
     * {@inheritDoc}
     */
    protected array $dependancies = [
        ['class' => Mailer::class, 'package' => 'phpmailer/phpmailer']
    ];

    /**
	 * @var Mailer
	 */
    protected $mailer;

    public function __construct(bool $debug = false)
    {
        parent::__construct($debug);

        $this->mailer = new Mailer();
        $this->mailer->SMTPAuth   = true;
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
    public function alt(string $content) : self
    {
        $this->mailer->AltBody = $content;

        return $this;
    }

    /**
	 * {@inheritDoc}
	 *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function attachment(array|string $path, string $name = '', string $encoding = Mailer::ENCODING_BASE64, string $disposition = 'attachment'): self
    {
        if (is_string($path)) {
            $path = [$path => $name];
        }

        foreach ($path As $key => $value) {
            $this->mailer->addAttachment($key, $value, $encoding, '', $disposition);
        }

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
	 *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function from(string $address, string $name = '') : self
    {
        $this->mailer->setFrom($address, $name);
      
        return $this;
    }

    /**
	 * {@inheritDoc}
	 *
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function header(array|string $name, ?string $value = null) : self
    {
        if (is_string($name)) {
            $name = [$name => $value];
        }

        foreach ($name As $key => $value) {
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
    

    /**
     * {@inheritDoc}
     * 
     * @return array
     */
    protected function makeAddress(string $email, string $name) 
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) == false) {
            $tmp = $email;
            $email = $name;
            $name = $tmp;
        }

        return [$email, $name];
    }
}