<?php

/**
 * This file is part of Blitz PHP framework - Contracts.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Contracts\Http;

use DateTime;
use DateTimeImmutable;

interface CookieInterface
{
    /**
     * Expires attribute format.
     *
     * @var string
     */
    public const EXPIRES_FORMAT = 'D, d-M-Y H:i:s T';

    /**
     * Sets the cookie name
     *
     * @return static
     */
    public function withName(string $name): self;

    /**
     * Gets the cookie name
     */
    public function getName(): string;

    /**
     * Gets the cookie value
     *
     * @return array|string
     */
    public function getValue();

    /**
     * Gets the cookie value as a string.
     *
     * This will collapse any complex data in the cookie with json_encode()
     */
    public function getStringValue(): string;

    /**
     * Create a cookie with an updated value.
     *
     * @param array|string $value Value of the cookie to set
     *
     * @return static
     */
    public function withValue($value): self;

    /**
     * Get the id for a cookie
     *
     * Cookies are unique across name, domain, path tuples.
     */
    public function getId(): string;

    /**
     * Get the path attribute.
     */
    public function getPath(): string;

    /**
     * Create a new cookie with an updated path
     *
     * @return static
     */
    public function withPath(string $path): self;

    /**
     * Get the domain attribute.
     */
    public function getDomain(): string;

    /**
     * Create a cookie with an updated domain
     *
     * @return static
     */
    public function withDomain(string $domain): self;

    /**
     * Get the current expiry time
     *
     * @return DateTime|DateTimeImmutable|null Timestamp of expiry or null
     */
    public function getExpiry();

    /**
     * Get the timestamp from the expiration time
     *
     * Timestamps are strings as large timestamps can overflow MAX_INT
     * in 32bit systems.
     *
     * @return string|null The expiry time as a string timestamp.
     */
    public function getExpiresTimestamp(): ?string;

    /**
     * Builds the expiration value part of the header string
     */
    public function getFormattedExpires(): string;

    /**
     * Create a cookie with an updated expiration date
     *
     * @param DateTime|DateTimeImmutable $dateTime Date time object
     *
     * @return static
     */
    public function withExpiry($dateTime): self;

    /**
     * Create a new cookie that will virtually never expire.
     *
     * @return static
     */
    public function withNeverExpire(): self;

    /**
     * Create a new cookie that will expire/delete the cookie from the browser.
     *
     * This is done by setting the expiration time to 1 year ago
     *
     * @return static
     */
    public function withExpired(): self;

    /**
     * Check if a cookie is expired when compared to $time
     *
     * Cookies without an expiration date always return false.
     *
     * @param DateTime|DateTimeImmutable $time The time to test against. Defaults to 'now' in UTC.
     */
    public function isExpired($time = null): bool;

    /**
     * Check if the cookie is HTTP only
     */
    public function isHttpOnly(): bool;

    /**
     * Create a cookie with HTTP Only updated
     *
     * @return static
     */
    public function withHttpOnly(bool $httpOnly): self;

    /**
     * Check if the cookie is secure
     */
    public function isSecure(): bool;

    /**
     * Create a cookie with Secure updated
     *
     * @return static
     */
    public function withSecure(bool $secure): self;

    /**
     * Returns the cookie as header value
     */
    public function toHeaderValue(): string;
}
