<?php

namespace BlitzPHP\Mail\Adapters;

use BadMethodCallException;
use BlitzPHP\Mail\MailerInterface;
use BlitzPHP\Utilities\String\Text;
use InvalidArgumentException;
use RuntimeException;

abstract class AbstractAdapter implements MailerInterface
{  
    /**
     * Dependances necessaires a l'adapter
     * 
     * @var array<string, string>[]
     */
    protected array $dependancies = [];

    protected $mailer;

    protected const PRIORITY_MAP = [
        self::PRIORITY_HIGH, 
        self::PRIORITY_NORMAL, 
        self::PRIORITY_LOW
    ];

    public function __construct(bool $debug = false)
    {
        foreach ($this->dependancies as $dependency) {
            if (empty($dependency['class']) || empty($dependency['package'])) {
                throw new InvalidArgumentException('Invalid dependencies property');
            }            
            if (! is_string($dependency['class']) || ! is_string($dependency['package'])) {
                throw new InvalidArgumentException('Invalid dependencies property');
            }

            if (! class_exists($dependency['class'])) {
                throw new RuntimeException(lang('Mail.dependancyNotFound', [$dependency['class'], static::class, $dependency['package']]));
            }
        }
        
        if ($debug) {
            $this->setDebug();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function init(array $config): self
    {
        foreach ($config as $key => $value) {
            $method = static::methodName($key);
            if (method_exists($this, $method)) {
                call_user_func([$this, $method], $value);
            }
        }

        return $this;
    }

    public abstract function setPort(int $port): self;

    public abstract function setHost(string $host): self;
    
    public abstract function setUsername(string $username): self;

    public abstract function setPassword(string $password): self;

    public abstract function setDebug(int $debug = 1): self;

    public abstract function setProtocol(string $protocol): self;

    public abstract function setTimeout(int $timeout): self;
    
    public abstract function setCharset(string $charset): self;

    public abstract function setPriority(int $priority): self;

    public abstract function setEncryption(?string $encryption): self;

    public function __call(string $method, array $arguments)
    {
        $name = static::methodName($method, 'set');
        if (method_exists($this, $name)) {
            return call_user_func_array([$this, $name], $arguments);
        }

        $name = static::methodName($method, 'get');
        if (method_exists($this, $name)) {
            return call_user_func_array([$this, $name], $arguments);
        }

        if ($this->mailer && method_exists($this->mailer, $method)) {
            return call_user_func_array([$this->mailer, $method], $arguments);
        }

        throw new BadMethodCallException('Method ' . $method . ' does not exist in ' . static::class);
    }

    protected static function methodName(string $name, string $prefix = 'set'): string
    {
        return Text::camel($prefix . '_' . $name);
    }

    /**
     * Cree une adresse au format valide pour l'adapter
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

    protected function parseMultipleAddresses(array|string $address, bool|string $name = '', bool $set = false): array
    {
        if (is_string($address)) {
            if (is_bool($name)) {
                throw new InvalidArgumentException('L\'argument 2 ($name) doit etre une chaine de caractères');
            }

            $address = [$address => $name];
        } else if (is_bool($name)) {
            $set = $name;
        }
        
        $addresses = [];
        
        foreach ($address As $key => $value) {
            $addresses[] = $this->makeAddress($key, $value);
        }

        return [$addresses, $set];
    }
}
