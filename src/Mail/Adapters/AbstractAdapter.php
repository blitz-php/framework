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
use BlitzPHP\Contracts\Mail\MailerInterface;
use BlitzPHP\Utilities\String\Text;
use InvalidArgumentException;
use RuntimeException;

/**
 * Classe abstraite de base pour tous les adaptateurs de mail.
 * Définit l'interface commune et fournit des fonctionnalités de base.
 */
abstract class AbstractAdapter implements MailerInterface
{
    /**
     * Tableau de correspondance entre les priorités
     *
     * @var array<int>
     */
    protected const PRIORITY_MAP = [
        self::PRIORITY_HIGH,
        self::PRIORITY_NORMAL,
        self::PRIORITY_LOW,
    ];

    /**
     * Dépendances nécessaires à l'adaptateur
     *
     * @var list<array{class: string, package: string}>
     */
    protected array $dependancies = [];

    /**
     * Instance du moteur de mail sous-jacent
     *
     * @var mixed
     */
    protected $mailer;

    /**
     * Dernière erreur survenue
     */
    protected ?string $lastError = null;

    /**
     * Constructeur
     *
     * @param bool $debug Activer le mode debug
	 *
     * @throws RuntimeException Si une dépendance est manquante
     */
    public function __construct(bool $debug = false)
    {
        $this->validateDependencies();

        if ($debug) {
            $this->setDebug();
        }
    }

    /**
     * Valide que toutes les dépendances sont présentes
     *
     * @throws RuntimeException Si une dépendance est manquante
     */
    private function validateDependencies(): void
    {
		foreach ($this->dependancies as $dependency) {
            if (empty($dependency['class']) || empty($dependency['package'])) {
                throw new InvalidArgumentException('Propriété de dépendance invalide');
            }
            if (! is_string($dependency['class']) || ! is_string($dependency['package'])) {
                throw new InvalidArgumentException('Propriété de dépendance invalide');
            }

            if (! class_exists($dependency['class'])) {
                throw new RuntimeException(lang('Mail.dependancyNotFound', [$dependency['class'], static::class, $dependency['package']]));
            }
        }
    }

    /**
     * Initialise l'adaptateur avec la configuration
     *
     * @param array<string, mixed> $config Configuration
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

    /**
     * Définit le port SMTP
     */
    abstract public function setPort(int $port): static;

    /**
     * Définit l'hôte SMTP
     */
    abstract public function setHost(string $host): static;

    /**
     * Définit le nom d'utilisateur SMTP
     */
    abstract public function setUsername(string $username): static;

    /**
     * Définit le mot de passe SMTP
     */
    abstract public function setPassword(string $password): static;

    /**
     * Active/désactive le mode debug
     *
     * @param int $debug Niveau de debug
     */
    abstract public function setDebug(int $debug = 1): static;

    /**
     * Définit le protocole d'envoi
     *
     * @param string $protocol Protocole (smtp, mail, sendmail)
     */
    abstract public function setProtocol(string $protocol): static;

    /**
     * Définit le timeout de connexion
     *
     * @param int $timeout Timeout en secondes
     */
    abstract public function setTimeout(int $timeout): static;

    /**
     * Définit le charset du message
     */
    abstract public function setCharset(string $charset): static;

    /**
     * Définit la priorité du message
     *
     * @param int $priority Priorité (PRIORITY_HIGH, PRIORITY_NORMAL, PRIORITY_LOW)
     */
    abstract public function setPriority(int $priority): static;

    /**
     * Définit le chiffrement SMTP
     *
     * @param string|null $encryption Chiffrement (ssl, tls, null)
     */
    abstract public function setEncryption(?string $encryption): static;

    /**
     * Nettoie tous les éléments d'envoi du message
     */
    abstract public function clear(): self;

	abstract protected function doAttach(array $path, string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'attachment'): static;

	/**
	 * {@inheritDoc}
	 */
	public function attach(array|string $path, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'attachment'): static
	{
		if (is_string($path)) {
            $path = [$path => $name];
        }

		foreach ($path as $filePath => $fileName) {
            if (!file_exists($filePath)) {
                $this->lastError = sprintf('Fichier non trouvé: %s', $filePath);
				unset($path[$filePath]);
                continue;
            }

            if (!$this->isValidFileSize($filePath)) {
                $this->lastError = sprintf('Fichier trop volumineux: %s', $filePath);
				unset($path[$filePath]);
                continue;
            }

			if ($type === '' && function_exists('mime_content_type')) {
				$type = mime_content_type($filePath) ?: 'application/octet-stream';
			}
            if (!$this->isValidMimeType($type)) {
                $this->lastError = sprintf('Type MIME non autorisé: %s', $type);
				unset($path[$filePath]);
                continue;
            }
        }

		return $this->doAttach($path, $type, $encoding, $disposition);
	}

    /**
     * Récupère la dernière erreur survenue
     *
     * @return string|null Message d'erreur ou null si aucune erreur
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Nettoie la dernière erreur
     */
    public function clearError(): void
    {
        $this->lastError = null;
    }

    /**
     * Gestionnaire d'appel de méthode magique
     * Permet d'appeler les méthodes set/get sur l'instance ou le moteur sous-jacent
     *
     * @throws BadMethodCallException Si la méthode n'existe pas
     */
	public function __call(string $method, array $arguments): mixed
    {
        $name = static::methodName($method, 'get');
        if (method_exists($this, $name)) {
            return call_user_func_array([$this, $name], $arguments);
        }

        $name = static::methodName($method, 'set');
        if (method_exists($this, $name)) {
            return call_user_func_array([$this, $name], $arguments);
        }

        if ($this->mailer && method_exists($this->mailer, $method)) {
            return call_user_func_array([$this->mailer, $method], $arguments);
        }

        throw new BadMethodCallException(
            sprintf('La méthode %s n\'existe pas dans %s', $method, static::class)
        );
    }

    /**
     * Convertit un nom de clé en nom de méthode camelCase
     *
     * @param string $prefix Préfixe (set, get)
     */
    protected static function methodName(string $name, string $prefix = 'set'): string
    {
        return Text::camel($prefix . '_' . $name);
    }

    /**
     * Crée une adresse au format valide pour l'adaptateur
     *
     * @return array{0: string, 1: string} Tableau [email, nom]
     *
	 * @throws InvalidArgumentException Si l'email n'est pas valide
     */
    protected function makeAddress(string $email, string $name)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false && filter_var($name, FILTER_VALIDATE_EMAIL) !== false) {
            $tmp   = $email;
            $email = $name;
            $name  = $tmp;
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException(
                sprintf('Adresse email invalide: %s', $email)
            );
        }

        return [$email, $name];
    }

    /**
     * Parse plusieurs adresses email
     *
     * @param array|string $address Adresse(s) email
     * @param bool|string $name Nom ou indicateur de remplacement
     * @param bool $set Indique si on doit remplacer les adresses existantes
     *
	 * @return array{0: array<array{0: string, 1: string}>, 1: bool}
     *
	 * @throws InvalidArgumentException Si les arguments sont invalides
     */
    protected function parseMultipleAddresses(array|string $address, bool|string $name = '', bool $set = false): array
    {
        if (is_string($address)) {
            if (is_bool($name)) {
                throw new InvalidArgumentException(
                    'L\'argument 2 ($name) doit être une chaîne de caractères quand $address est une chaîne'
                );
            }

            $address = [$address => $name];
        } elseif (is_bool($name)) {
            $set = $name;
        }

        $addresses = [];

        foreach ($address as $key => $value) {
            $email = is_string($key) ? $key : $value;
            $nameValue = is_string($key) ? $value : '';

            try {
                $addresses[] = $this->makeAddress($email, $nameValue);
            } catch (InvalidArgumentException $e) {
                // Log l'erreur mais continue avec les autres adresses
                logger()->warning($e->getMessage());
            }
        }

        return [$addresses, $set];
    }

    /**
     * Valide un type MIME pour les pièces jointes
     *
     * @param string $mimeType Type MIME à valider
     * @param array<string> $allowedTypes Types MIME autorisés (peuvent contenir des wildcards comme 'image/*')
     */
    protected function isValidMimeType(string $mimeType, array $allowedTypes = []): bool
    {
        if (empty($allowedTypes)) {
            // Types par défaut sécurisés
            $allowedTypes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'application/pdf',
                'text/plain', 'text/csv', 'text/html',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip', 'application/x-rar-compressed',
            ];
        }

        foreach ($allowedTypes as $allowed) {
            if (strpos($allowed, '*') !== false) {
                // Gestion des wildcards
                $pattern = str_replace('*', '.*', $allowed);
                $pattern = '/^' . str_replace('/', '\/', $pattern) . '$/i';
                if (preg_match($pattern, $mimeType)) {
                    return true;
                }
            } elseif (strtolower($mimeType) === strtolower($allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Valide la taille d'un fichier
     *
     * @param string $filePath Chemin du fichier
     * @param int|null $maxSize Taille maximale en octets (null = utiliser la config)
     */
    protected function isValidFileSize(string $filePath, ?int $maxSize = null): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $fileSize = filesize($filePath);

        if ($maxSize === null) {
            $maxSize = config('mail.max_attachment_size', 10 * 1024 * 1024); // 10MB par défaut
        }

        return $fileSize <= $maxSize;
    }
}
