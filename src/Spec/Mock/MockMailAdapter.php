<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Spec\Mock;

use BlitzPHP\Mail\Adapters\AbstractAdapter;

class MockMailAdapter extends AbstractAdapter
{
    public function setPort(int $port): static
    {
        return $this;
    }

    public function setHost(string $host): static
    {
        return $this;
    }

    public function setUsername(string $username): static
    {
        return $this;
    }

    public function setPassword(string $password): static
    {
        return $this;
    }

    public function setDebug(int $debug = 1): static
    {
        return $this;
    }

    public function setProtocol(string $protocol): static
    {
        return $this;
    }

    public function setTimeout(int $timeout): static
    {
        return $this;
    }

    public function setCharset(string $charset): static
    {
        return $this;
    }

    public function setPriority(int $priority): static
    {
        return $this;
    }

    public function setEncryption(?string $encryption): static
    {
        return $this;
    }

    public function clear(): static
    {
        return $this;
    }

    public function doAttach(array $path, string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'attachment'): static
    {
        return $this;
    }

    public function alt(string $content): static
    {
        return $this;
    }

    public function attach(array|string $path, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'attachment'): static
    {
        return $this;
    }

    public function attachBinary($binary, string $name, string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'attachment'): static
    {
        return $this;
    }

    public function bcc(array|string $address, bool|string $name = '', bool $set = false): static
    {
        return $this;
    }

    public function cc(array|string $address, bool|string $name = '', bool $set = false): static
    {
        return $this;
    }

    public function dkim(string $pk, string $passphrase = '', string $selector = '', string $domain = ''): static
    {
        return $this;
    }

    public function embedded(string $path, string $cid, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'inline'): static
    {
        return $this;
    }

    public function embeddedBinary($binary, string $cid, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'inline'): static
    {
        return $this;
    }

    public function from(string $address, string $name = ''): static
    {
        return $this;
    }

    public function header(array|string $name, ?string $value = null): static
    {
        return $this;
    }

    public function html(string $content): static
    {
        return $this;
    }

    public function init(array $config): static
    {
        return $this;
    }

    public function lastId(): string
    {
        return '';
    }

    public function message(string $message): static
    {
        return $this;
    }

    public function replyTo(array|string $address, bool|string $name = '', bool $set = false): static
    {
        return $this;
    }

    public function send(): bool
    {
        return true;
    }

    public function sign(string $cert_filename, string $key_filename, string $key_pass, string $extracerts_filename = ''): static
    {
        return $this;
    }

    public function subject(string $subject): static
    {
        return $this;
    }

    public function text(string $content): static
    {
        return $this;
    }

    public function to(array|string $address, bool|string $name = '', bool $set = false): static
    {
        return $this;
    }
}
