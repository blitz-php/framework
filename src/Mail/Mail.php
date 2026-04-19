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

use BlitzPHP\Contracts\Event\EventManagerInterface;
use BlitzPHP\Contracts\Mail\MailerInterface;
use BlitzPHP\Exceptions\MailException;
use BlitzPHP\Mail\Adapters\AbstractAdapter;
use BlitzPHP\Mail\Adapters\LogMailer;
use BlitzPHP\Mail\Adapters\PHPMailer;
use BlitzPHP\Mail\Adapters\SymfonyMailer;
use Exception;
use InvalidArgumentException;
use League\CommonMark\CommonMarkConverter;

/**
 * Envoi d'e-mail en utilisant Mail, Sendmail ou SMTP.
 * Facade principale pour l'envoi de mails avec support de multiples adaptateurs.
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
     * Un tableau mappant les schémas d'URL aux noms de classe de moteur d'envoi d'email.
     *
     * @var array<string, string>
     * @psalm-var array<string, class-string>
     */
    protected static array $validHandlers = [
        'phpmailer' => PHPMailer::class,
        'symfony'   => SymfonyMailer::class,
        'log'       => LogMailer::class,
    ];

    /**
     * Configuration du mailer
     *
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * Adaptateur à utiliser pour envoyer les mails
     */
    private ?AbstractAdapter $adapter;

    /**
     * Gestionnaire d'événements
     */
    private EventManagerInterface $event;

    /**
     * Données pour les vues
     *
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * Constructeur
     *
     * @param array<string, mixed> $config Configuration
     */
    public function __construct(array $config, ?EventManagerInterface $event = null)
    {
        $this->event = $event ?? service('event');
        $this->init($config);
    }

    /**
     * Nettoie les éléments d'envoi du message
     *
     * @param bool $reset Si vrai, réinitialise également l'adaptateur
     */
    public function clear(bool $reset = false): static
    {
        $this->factory()->clear();
        $this->data = [];

        if ($reset) {
            $this->adapter = null;
        }

        return $this;
    }

    /**
     * Envoi d'un mail de type Mailable
     *
     * @param Mailable $mailable Instance du mailable
     *
     * @throws MailException Si l'envoi échoue
     */
    public function envoi(Mailable $mailable): bool
    {
        return $mailable->send($this);
    }

    /**
     * Envoi avec réessais en cas d'échec
     *
     * @param int   $maxRetries Nombre maximum de tentatives
     * @param int   $delay      Délai initial entre les tentatives en secondes
     * @param float $multiplier Multiplicateur pour le backoff exponentiel
     */
    public function sendWithRetry(int $maxRetries = 3, int $delay = 1, float $multiplier = 2.0): bool
    {
        $attempts = 0;

        while ($attempts < $maxRetries) {
            try {
                if ($this->send()) {
                    return true;
                }
            } catch (MailException $e) {
                // Log l'erreur mais continue
                logger()->error('Erreur d\'envoi de mail', [
                    'attempt' => $attempts + 1,
                    'error'   => $e->getMessage(),
                ]);
            }

            $attempts++;

            if ($attempts < $maxRetries) {
                $sleepTime = $delay * $multiplier ** ($attempts - 1);
                sleep(min((int) $sleepTime, 10)); // Maximum 10 secondes
            }
        }

        return false;
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

    /**
     * Change l'adaptateur de mail
     *
     * @param string $handler Nom de l'adaptateur (phpmailer, symfony, log)
     *
     * @throws InvalidArgumentException Si l'adaptateur n'existe pas
     */
    public function mailer(string $handler): static
    {
        $this->clear(true);

        return $this->merge(['handler' => $handler]);
    }

    /**
     * Fusionne la configuration avec celle existante
     *
     * @param array<string, mixed> $config Nouvelle configuration
     */
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
     *
     * @throws InvalidArgumentException Si le handler n'est pas valide
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
        $adapter = new $handler((bool) $debug);

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
        $this->factory()->embedded($path, $cid, $name, $type, $encoding, $disposition);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function embeddedBinary($binary, string $cid, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'inline'): static
    {
        $this->factory()->embeddedBinary($binary, $cid, $name, $type, $encoding, $disposition);

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
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
        $content = preg_replace('/on\w+=\s*"[^"]*"/i', '', $content);
        $content = preg_replace('/on\w+=\s*\'[^\']*\'/i', '', $content);
        $content = preg_replace('/on\w+=\s*[^\s>]*/i', '', $content);

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
            default => $this,
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
        $this->event->emit('mail.sending', $this, [
            'config'    => $this->config,
            'timestamp' => $startTime = microtime(true),
        ]);

        try {
            if ($this->factory()->send()) {
                $duration = microtime(true) - $startTime;

                $this->event->emit('mail.sent', $this, [
                    'lastId'   => $this->lastId(),
                    'duration' => $duration,
                ]);

                $this->clear(false);

                return true;
            }
        } catch (Exception $e) {
            $this->event->emit('mail.failed', $this, [
                'error'     => $e->getMessage(),
                'exception' => $e,
            ]);

            throw new MailException(
                sprintf('Erreur d\'envoi de mail: %s', $e->getMessage()),
                $e->getCode(),
                $e,
            );
        }

        $this->event->emit('mail.failed', $this, [
            'error'    => $this->getLastError() ?? 'Erreur inconnue',
            'duration' => microtime(true) - $startTime,
        ]);

        return false;
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
     * Utilise une vue markdown pour générer le message de l'email
     *
     * @param array<string, mixed> $data Données pour la vue
     */
    public function markdown(string $view, array $data = []): static
    {
        $converter = new CommonMarkConverter([
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $markdown = $this->_view($view, array_merge($this->data, $data));
        $html     = $converter->convert($markdown)->getContent();

        // Génère une version texte alternative
        $text = strip_tags(preg_replace('/<[^>]*>/', ' ', $html));
        $text = preg_replace('/\s+/', ' ', $text);

        return $this->html($html)->alt($text);
    }

    /**
     * Utilise une vue HTML pour générer le message de l'email
     *
     * @param array<string, mixed> $data Données pour la vue
     */
    public function view(string $view, array $data = []): static
    {
        return $this->html($this->_view($view, $data));
    }

    /**
     * Récupère la dernière erreur survenue
     */
    public function getLastError(): ?string
    {
        return $this->factory()->getLastError();
    }

    /**
     * Récupère les statistiques d'envoi (si disponibles)
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $adapter = $this->factory();

        if (method_exists($adapter, 'getStats')) {
            return $adapter->getStats();
        }

        return [
            'adapter' => $adapter::class,
            'config'  => $this->config,
        ];
    }

    /**
     * Enregistre des données pour les vues
     *
     * @param array<string, mixed> $data
     */
    public function with(array $data): static
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * Gestionnaire d'appel de méthode magique
     */
    public function __call(string $method, array $arguments): mixed
    {
        $result = call_user_func_array([$this->factory(), $method], $arguments);

        if ($result instanceof AbstractAdapter) {
            return $this;
        }

        return $result;
    }

    /**
     * Rendu d'une vue
     *
     * @param array<string, mixed> $data Données pour la vue
     */
    private function _view(string $view, array $data = []): string
    {
        $path = '';

        // N'est-il pas namespaced ? on cherche le dossier en fonction du paramètre "view_base"
        if (! str_contains($view, '\\')) {
            $path = $this->config['view_dir'] ?? '';
            if (! empty($path)) {
                $path .= '/';
            }
        }

        $viewer = view($path . $view, $data);

        // Applique un layout si spécifié
        if (! empty($this->config['template'])) {
            $viewer->layout($this->config['template']);
        }

        return $viewer->get(false);
    }

    /**
     * Envoi de mail en masse avec gestion des erreurs individuelles
     *
     * @param list<array<string, mixed>> $recipients Liste des destinataires
     * @param callable                   $callback   Fonction de callback pour configurer chaque mail
     *
     * @return array{list<int>, array<int, string>} [succès, échecs]
     */
    public function bulk(array $recipients, callable $callback): array
    {
        $success  = [];
        $failures = [];

        foreach ($recipients as $index => $recipient) {
            try {
                // Crée une nouvelle instance pour chaque destinataire
                $mail = clone $this;
                $mail->clear();

                // Configure le mail via le callback
                $callback($mail, $recipient);

                if ($mail->send()) {
                    $success[] = $index;
                } else {
                    $failures[$index] = $mail->getLastError() ?? 'Erreur inconnue';
                }
            } catch (Exception $e) {
                $failures[$index] = $e->getMessage();
            }
        }

        return [$success, $failures];
    }

    /**
     * Vérifie la configuration du mailer
     *
     * @return array<string, mixed> Résultat du test
     */
    public function testConnection(): array
    {
        $adapter = $this->factory();

        if (method_exists($adapter, 'testConnection')) {
            return $adapter->testConnection();
        }

        // Test basique de configuration
        $result = [
            'adapter'      => $adapter::class,
            'config_valid' => true,
            'from'         => $this->config['from'] ?? null,
            'host'         => $this->config['host'] ?? null,
            'port'         => $this->config['port'] ?? null,
        ];

        // Vérifications de base
        if (empty($result['from']['address'])) {
            $result['config_valid'] = false;
            $result['errors'][]     = 'Adresse expéditeur non configurée';
        }

        if ($this->config['handler'] !== 'log' && empty($result['host'])) {
            $result['config_valid'] = false;
            $result['errors'][]     = 'Hôte SMTP non configuré';
        }

        return $result;
    }
}
