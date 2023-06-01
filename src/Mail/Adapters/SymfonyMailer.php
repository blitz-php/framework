<?php

namespace BlitzPHP\Mail\Adapters;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

class SymfonyMailer extends AbstractAdapter
{
    /**
     * {@inheritDoc}
     */
    protected array $dependancies = [
        ['class' => Mailer::class, 'package' => 'symfony/mailer']
    ];

    /**
	 * @var Email
	 */
    protected $mailer;

    private ?Mailer $transporter = null;

    private string $charset    = 'utf-8';
    private string $protocol   = self::PROTOCOL_SMTP;
    private int $timeout       = 0;
    private int $port          = 587;
    private string $host       = '';
    private string $username   = '';
    private string $password   = '';
    private string $encryption = self::ENCRYPTION_TLS;
    private int $debug         = 0;

    public function __construct(bool $debug = false)
    {
        parent::__construct($debug);

        $this->mailer = new Email();
    }

    /**
     * {@inheritDoc}
     */
    public function setPort(int $port): self 
    {
        $this->port = $port;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setHost(string $host): self 
    {
        $this->host = $host;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setUsername(string $username): self 
    {
        $this->username = $username;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setPassword(string $password): self 
    {
        $this->password = $password;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setDebug(int $debug = 1): self 
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setProtocol(string $protocol): self 
    {
        $this->protocol = $protocol;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setTimeout(int $timeout): self 
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setCharset(string $charset): self 
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setPriority(int $priority): self 
    {
        if (in_array($priority, static::PRIORITY_MAP, true)) {
            $this->mailer->priority($priority);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setEncryption(?string $encryption): self 
    {
        if (in_array($encryption, [null, static::ENCRYPTION_SSL, static::ENCRYPTION_TLS], true)) {
            $this->encryption = $encryption;
        }

        return $this;
    }


    /**
	 * {@inheritDoc}
     */
    public function alt(string $content) : self
    {
        return $this;
    }

    /**
	 * {@inheritDoc}
     */
    public function attachment(array|string $path, string $name = '', string $encoding = '', string $disposition = 'attachment'): self
    {
        if (is_string($path)) {
            $path = [$path => $name];
        }

        foreach ($path As $key => $value) {
            $this->mailer->attachFromPath($key, $value, $encoding);
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function bcc(array|string $address, bool|string $name = '', bool $set = false): self
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
    public function cc(array|string $address, bool|string $name = '', bool $set = false): self
    {
        [$addresses, $set] = $this->parseMultipleAddresses($address, $name, $set);

        if ($set) {
            $this->mailer->cc(...$addresses);
        } else {
            $this->mailer->addCC(...$addresses);
        }

        return $this;
    }

    /**
	 * {@inheritDoc}
     */
    public function from(string $address, string $name = ''): self
    {
        $this->mailer->from($this->makeAddress($address, $name));
      
        return $this;
    }

    /**
	 * {@inheritDoc}
     */
    public function header(array|string $name, ?string $value = null): self
    {
        if (is_string($name)) {
            $name = [$name => $value];
        }

        foreach ($name As $key => $value) {
            $this->mailer->getHeaders()->addTextHeader($key, $value);
        }
        
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function html(string $content): self
    {
        $this->mailer->html($content, $this->charset);
        
        return $this;
    }
    
    /**
	 * {@inheritDoc}
     */
    public function message(string $message): self
    {
        return $this;
    }

    /**
	 * {@inheritDoc}
     */
    public function replyTo(array|string $address, bool|string $name = '', bool $set = false): self
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
    public function send() : bool
    {
        try {
            $this->transporter()->send($this->mailer);
        
            return true;
        } catch (Throwable $e) {
            if ($this->debug > 0) {
                throw $e;
            }

            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function sign(string $cert_filename, string $key_filename, string $key_pass, string $extracerts_filename = ''): self
    {
        return $this;
    }

    /**
	 * {@inheritDoc}
     */
    public function subject(string $subject) : self
    {
        $this->mailer->subject($subject);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function text(string $content): self
    {
        $this->mailer->text($content, $this->charset);
        
        return $this;
    }

    /**
	 * {@inheritDoc}
     */
    public function to(array|string $address, bool|string $name = '', bool $set = false): self
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
     *
     * @return Address
     */
    protected function makeAddress(string $email, string $name) 
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) == false) {
            $tmp = $email;
            $email = $name;
            $name = $tmp;
        }

        return new Address($email, $name);
    }

    private function transporter(): Mailer
    {
        if (null !== $this->transporter) {
            return $this->transporter;
        }

        return $this->transporter = new Mailer(
            Transport::fromDsn($this->buildDsn())
        );
    }

    private function buildDsn(): string
    {
        return '';  
    }
}
