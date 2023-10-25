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

use BadMethodCallException;
use BlitzPHP\Mail\MailerInterface;
use BlitzPHP\Utilities\String\Text;
use InvalidArgumentException;
use RuntimeException;

abstract class AbstractAdapter implements MailerInterface
{
    protected const PRIORITY_MAP = [
        self::PRIORITY_HIGH,
        self::PRIORITY_NORMAL,
        self::PRIORITY_LOW,
    ];

    /**
     * Dependances necessaires a l'adapter
     *
     * @var array<string, string>[]
     */
    protected array $dependancies = [];

    protected $mailer;

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
    public function init(array $config): static
    {
        foreach ($config as $key => $value) {
            $method = static::methodName($key);
            if (method_exists($this, $method)) {
                call_user_func([$this, $method], $value);
            }
        }

        return $this;
    }

    abstract public function setPort(int $port): static;

    abstract public function setHost(string $host): static;

    abstract public function setUsername(string $username): static;

    abstract public function setPassword(string $password): static;

    abstract public function setDebug(int $debug = 1): static;

    abstract public function setProtocol(string $protocol): static;

    abstract public function setTimeout(int $timeout): static;

    abstract public function setCharset(string $charset): static;

    abstract public function setPriority(int $priority): static;

    abstract public function setEncryption(?string $encryption): static;

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
     * @return array|mixed
     */
    protected function makeAddress(string $email, string $name)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $tmp   = $email;
            $email = $name;
            $name  = $tmp;
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
        } elseif (is_bool($name)) {
            $set = $name;
        }

        $addresses = [];

        foreach ($address as $key => $value) {
            $addresses[] = $this->makeAddress($key, $value);
        }

        return [$addresses, $set];
    }
}
