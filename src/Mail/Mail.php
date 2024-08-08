<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Mail;

use BlitzPHP\Contracts\Mail\MailerInterface;
use BlitzPHP\Mail\Adapters\AbstractAdapter;
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

    public function clear(): void
    {
        $this->adapter = null;
    }

    /**
     * Envoi d'un mail de type Mailable
     */
    public function envoi(Mailable $mailable): bool
    {
        return $mailable->send($this);
    }

    /**
     * {@inheritDoc}
     */
    public function init(array $config): static
    {
        $this->config  = $config;
        $this->adapter = null;

        return $this;
    }

    public function mailer(string $handler): static
    {
        $this->clear();

        return $this->merge(['handler' => $handler]);
    }

    public function merge(array $config): static
    {
        $this->config = array_merge($this->config, $config);

        if (null !== $this->adapter) {
            $this->adapter->init($config);
        }

        return $this;
    }

    /**
     * Tente de créer le gestionnaire de mail souhaité
     */
    protected function factory(): AbstractAdapter
    {
        if (null !== $this->adapter) {
            return $this->adapter;
        }

        $handler = $this->config['handler'] ?? null;

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
    public function alt(string $content): static
    {
        $this->factory()->alt($content);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function attach(array|string $path, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'attachment'): static
    {
        $this->factory()->attach($path, $name, $type, $encoding, $disposition);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function attachBinary($binary, string $name, string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'attachment'): static
    {
        $this->factory()->attachBinary($binary, $name, $type, $encoding, $disposition);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function bcc(array|string $address, bool|string $name = '', bool $set = false): static
    {
        $this->factory()->bcc($address, $name, $set);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function cc(array|string $address, bool|string $name = '', bool $set = false): static
    {
        $this->factory()->cc($address, $name, $set);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function dkim(string $pk, string $passphrase = '', string $selector = '', string $domain = ''): static
    {
        $this->factory()->dkim($pk, $passphrase, $selector, $domain);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function embedded(string $path, string $cid, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'inline'): static
    {
        $this->factory()->embedded($path, $cid, $name, $encoding, $type, $disposition);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function embeddedBinary($binary, string $cid, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'inline'): static
    {
        $this->factory()->embeddedBinary($binary, $cid, $name, $encoding, $type, $disposition);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function from(string $address, string $name = ''): static
    {
        $this->factory()->from($address, $name);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function header(array|string $name, ?string $value = null): static
    {
        $this->factory()->header($name, $value);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function html(string $content): static
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
    public function message(string $message): static
    {
        return match ($this->config['mailType']) {
            'html'  => $this->html($message),
            'text'  => $this->text($message),
            default => $this
        };
    }

    /**
     * {@inheritDoc}
     */
    public function replyTo(array|string $address, bool|string $name = '', bool $set = false): static
    {
        $this->factory()->replyTo($address, $name, $set);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function send(): bool
    {
        return $this->factory()->send();
    }

    /**
     * {@inheritDoc}
     */
    public function sign(string $cert_filename, string $key_filename, string $key_pass, string $extracerts_filename = ''): static
    {
        $this->factory()->sign($cert_filename, $key_filename, $key_pass, $extracerts_filename);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function subject(string $subject): static
    {
        $this->factory()->subject($subject);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function text(string $content): static
    {
        $this->factory()->text($content);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function to(array|string $address, bool|string $name = '', bool $set = false): static
    {
        $this->factory()->to($address, $name, $set);

        return $this;
    }

    /**
     * Utilise une vue html pour generer le message de l'email
     */
    public function view(string $view, array $data = []): static
    {
        $path = '';

        // N'est-il pas namespaced ? on cherche le dossier en fonction du parametre "view_base"
        if (! str_contains($view, '\\')) {
            $path = $this->config['view_dir'] ?? '';
            if (! empty($path)) {
                $path .= '/';
            }
        }

        $view = view($path . $view, $data);
        if (! empty($this->config['template'])) {
            $view->layout($this->config['template']);
        }

        return $this->html($view->get(false));
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
