<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Contracts\Http;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

interface CookieInterface
{
    /**
     * Format de l'attribut Expires
     *
     * @var string
     */
    public const EXPIRES_FORMAT = 'D, d-M-Y H:i:s T';

    /**
     * SameSite attribute value: Lax
     *
     * @var string
     */
    public const SAMESITE_LAX = 'Lax';

    /**
     * SameSite attribute value: Strict
     *
     * @var string
     */
    public const SAMESITE_STRICT = 'Strict';

    /**
     * SameSite attribute value: None
     *
     * @var string
     */
    public const SAMESITE_NONE = 'None';

    /**
     * Valeurs valides pour l'attribut "SameSite".
     *
     * @var string[]
     */
    public const SAMESITE_VALUES = [
        self::SAMESITE_LAX,
        self::SAMESITE_STRICT,
        self::SAMESITE_NONE,
    ];

    /**
     * Constructor
     *
     * Les arguments des constructeurs sont similaires à la méthode native PHP `setcookie()`.
     * La seule différence est le 3ème argument qui excepte null ou un
     * Objet DateTime ou DateTimeImmutable à la place d'un entier.
     *
     * @see http://php.net/manual/en/function.setcookie.php
     *
     * @param array|string                    $value     Valeur du cookie
     * @param DateTime|DateTimeImmutable|null $expiresAt
     */
    public function __construct(string $name, $value = '', ?DateTimeInterface $expiresAt = null, ?string $path = null, ?string $domain = null, ?bool $secure = null, ?bool $httpOnly = null, ?string $sameSite = null);

    /**
     * Modifie le nom du cookie
     *
     * @return static
     */
    public function withName(string $name);

    /**
     * Recupère le nom du cookie
     */
    public function getName(): string;

    /**
     * Recupère la valeur du cookie
     *
     * @return array|string
     */
    public function getValue();

    /**
     * Obtient la valeur du cookie sous forme scalaire.
     *
     * Cela va réduire toutes les données complexes dans le cookie avec json_encode()
     *
     * @return mixed
     */
    public function getScalarValue();

    /**
     * Crée un cookie avec une valeur mise à jour
     *
     * @param mixed $value
     *
     * @return static
     */
    public function withValue($value);

    /**
     * Obtenir l'identifiant d'un cookie
     *
     * Les cookies sont uniques sur les tuples de nom, de domaine et de chemin.
     */
    public function getId(): string;

    /**
     * Recupère le chemin
     */
    public function getPath(): string;

    /**
     * Créer un nouveau cookie avec un chemin mis à jour
     *
     * @return static
     */
    public function withPath(string $path);

    /**
     * Recupère le domaine
     */
    public function getDomain(): string;

    /**
     * Crée un nouveau cookie avec un domaine mis à jour
     *
     * @return static
     */
    public function withDomain(string $domain);

    /**
     * Obtenir l'heure d'expiration actuelle
     *
     * @return DateTime|DateTimeImmutable|null Timestamp of expiry or null
     */
    public function getExpiry();

    /**
     * Obtenir l'horodatage à partir de l'heure d'expiration
     *
     * @return int|null L'heure d'expiration sous forme d'entier.
     */
    public function getExpiresTimestamp(): ?int;

    /**
     * Construit la partie valeur d'expiration de la chaîne d'en-tête
     */
    public function getFormattedExpires(): string;

    /**
     * Créer un cookie avec une date d'expiration mise à jour
     *
     * @param DateTime|DateTimeImmutable $dateTime
     *
     * @return static
     */
    public function withExpiry($dateTime);

    /**
     * Créez un nouveau cookie qui n'expirera pratiquement jamais.
     *
     * @return static
     */
    public function withNeverExpire();

    /**
     * Créez un nouveau cookie qui expirera/supprimera le cookie du navigateur.
     *
     * Cela se fait en définissant le délai d'expiration sur 1 an auparavant
     *
     * @return static
     */
    public function withExpired();

    /**
     * Vérifiez si un cookie a expiré par rapport à $time
     *
     * Les cookies sans date d'expiration renvoient toujours faux.
     *
     * @param DateTime|DateTimeImmutable $time L'heure de test. Par défaut, 'maintenant' en UTC.
     */
    public function isExpired($time = null): bool;

    /**
     * Vérifie si le cookie est HTTP only
     */
    public function isHttpOnly(): bool;

    /**
     * Créer un cookie avec HTTP Only mis à jour
     *
     * @return static
     */
    public function withHttpOnly(bool $httpOnly);

    /**
     * Vérifie si le cookie est sécurisé
     */
    public function isSecure(): bool;

    /**
     * Créer un cookie avec Secure mis à jour
     *
     * @return static
     */
    public function withSecure(bool $secure);

    /**
     * Obtenez l'attribut SameSite.
     */
    public function getSameSite(): ?string;

    /**
     * Créez un cookie avec une option SameSite mise à jour.
     *
     * @param string|null $sameSite Valeur à définir pour l'option Samesite.
     *                              Une des constantes CookieInterface::SAMESITE_*.
     *
     * @return static
     */
    public function withSameSite(?string $sameSite);

    /**
     * Obtenez les options de cookie
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array;

    /**
     * Obtenez les données de cookie sous forme de tableau.
     *
     * @return array<string, mixed> avec les clés `name`, `value`, `expires` etc. options.
     */
    public function toArray(): array;

    /**
     * Renvoie le cookie comme valeur d'en-tête
     */
    public function toHeaderValue(): string;
}
