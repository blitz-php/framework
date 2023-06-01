<?php

namespace BlitzPHP\Mail;

use BlitzPHP\Mail\Adapters\AbstractAdapter;
use BlitzPHP\Mail\Adapters\NetteMailer;
use BlitzPHP\Mail\Adapters\PHPMailer;
use BlitzPHP\Mail\Adapters\SymfonyMailer;
use InvalidArgumentException;

/**
 * Envoi d'e-mail en utilisant Mail, Sendmail ou SMTP.
 * 
 * @method $this charset(string $charset)
 * @method $this priority(int $priority)
 * @method $this setCharset(string $charset)
 * @method $this setDebug(int $debug = 1)
 * @method $this setEncryption(?string $encryption)
 * @method $this setHost(string $host)
 * @method $this setPassword(string $password);
 * @method $this setPort(int $port)
 * @method $this setPriority(int $priority)
 * @method $this setProtocol(string $protocol)
 * @method $this setTimeout(int $timeout)
 * @method $this setUsername(string $username)
 * @method $this timeout(int $timeout)
 */
class Mail implements MailerInterface
{
    /**
     * Un tableau mappant les schémas d'URL aux noms de classe de moteur d'envoie d'email.
     *
     * @var array<string, string>
     * @psalm-var array<string, class-string>
     */
    protected static array $validHandlers = [
        'phpmailer' => PHPMailer::class,
        'symfony'   => SymfonyMailer::class,
        'nette'     => NetteMailer::class,
    ];

    /**
     * Configurations
     */
    protected array $config = [];

    /**
     * Adapter a utiliser pour envoyer les mails
     */
    private ?AbstractAdapter $adapter;

    /**
     * Constructeur
     */
    public function __construct(array $config)
    {
        $this->init($config);
    }

    /**
     * {@inheritDoc}
     */
    public function init(array $config): self
    {
        $this->config  = $config;
        $this->adapter = null;

        return $this;
    }

    public function merge(array $config): self
    {
        $this->config = array_merge($this->config, $config);

        if (null !== $this->adapter) {
            $this->adapter->init($config);
        }

        return $this;
    }

    public function clear(): void 
    {
        $this->adapter = null;
    }

    /**
     * Tente de créer le gestionnaire de mail souhaité
     */
    protected function factory(): AbstractAdapter
    {
        if (! empty($this->adapter)) {
            return $this->adapter;
        }

        $handler  = $this->config['handler'] ?? null;

        if (empty($handler)) {
            throw new InvalidArgumentException(lang('Mail.undefinedHandler'));
        }

        if (array_key_exists($handler, static::$validHandlers)) {
            $handler = static::$validHandlers[$handler];
        }

        if (! class_exists($handler)) {
            throw new InvalidArgumentException(lang('Mail.invalidHandler', [$handler]));
        }
        
        $debug = $this->config['debug'] ?? 'auto';
        if ($debug === 'auto') {
            $debug = on_dev();
        }

        if (! is_subclass_of($handler, AbstractAdapter::class)) {
            throw new InvalidArgumentException(lang('Mail.handlerMustExtendClass', [$handler, AbstractAdapter::class]));
        }
        
        /** @var AbstractAdapter $adapter */
        $adapter = new $handler($debug);

        return $this->adapter = $adapter->init($this->config)->from(...$this->config['from']);
    }

    /**
	 * {@inheritDoc}
     */
    public function alt(string $content) : self
    {
        $this->factory()->alt($content);

        return $this;
    }

    /**
	 * {@inheritDoc}
     */
    public function attachment(array|string $path, string $name = '', string $encoding = '', string $disposition = 'attachment'): self
    {
        $this->factory()->attachment($path, $name, $encoding, $disposition);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function bcc(array|string $address, bool|string $name = '', bool $set = false) : self
    {
        $this->factory()->bcc($address, $name, $set);

        return $this;
    }

    /**
	 * {@inheritDoc}
     */
    public function cc(array|string $address, bool|string $name = '', bool $set = false): self
    {
        $this->factory()->cc($address, $name, $set);

        return $this;
    }

    /**
	 * {@inheritDoc}
     */
    public function from(string $address, string $name = ''): self
    {
        $this->factory()->from($address, $name);
      
        return $this;
    }

    /**
	 * {@inheritDoc}
     */
    public function header(array|string $name, ?string $value = null): self
    {
        $this->factory()->header($name, $value);
        
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function html(string $content): self
    {
        $this->factory()->html($content);
        
        return $this;
    }
    
    /**
     * {@inheritDoc}
     */
    public function lastId(): string
    {
        return $this->factory()->lastId();
    }

    /**
	 * {@inheritDoc}
     */
    public function message(string $message): self
    {
        return match($this->config['mailType']) {
            'html'  => $this->html($message),
            'text'  => $this->text($message),
            default => $this
        };
    }

    /**
	 * {@inheritDoc}
     */
    public function replyTo(array|string $address, bool|string $name = '', bool $set = false): self
    {
        $this->factory()->replyTo($address, $name, $set);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function send() : bool
    {
        return $this->factory()->send();
    }

    /**
     * {@inheritDoc}
     */
    public function sign(string $cert_filename, string $key_filename, string $key_pass, string $extracerts_filename = ''): self
    {
        $this->factory()->sign($cert_filename, $key_filename, $key_pass, $extracerts_filename);

        return $this;
    }

    /**
	 * {@inheritDoc}
     */
    public function subject(string $subject) : self
    {
        $this->factory()->subject($subject);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function text(string $content): self
    {
        $this->factory()->text($content);
        
        return $this;
    }

    /**
	 * {@inheritDoc}
     */
    public function to(array|string $address, bool|string $name = '', bool $set = false): self
    {
        $this->factory()->to($address, $name, $set);
        
        return $this;
    }  

    /**
     * Utilise une vue html pour generer le message de l'email
     */
    public function view(string $view, array $data = []): self 
    {
        $path = '';

        // N'est-il pas namespaced ? on cherche le dossier en fonction du parametre "view_base"
        if (strpos($view, '\\') === false) {
            $path = $this->config['view_base'] ?? '';
            if (! empty($path)) {
                $path .= '/';
            }
        }
        
        return $this->html(view($path . $view, $data)->get(false));
    }
    
    public function __call(string $method, array $arguments)
    {
        $result = call_user_func_array([$this->factory(), $method], $arguments);

        if ($result instanceof AbstractAdapter) {
            return $this;
        }

        return $result;
    }
}
