<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Debug;

use InvalidArgumentException;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Formatter\ScalarFormatter;
use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Handler\ChromePHPHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\FirePHPHandler;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\TelegramBotHandler;
use Monolog\Logger as MonologLogger;
use Monolog\Processor\HostnameProcessor;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use stdClass;
use Stringable;

class Logger implements LoggerInterface
{
    /**
     * Options de configuration provenant de app/Config/log.php
     *
     * @var object
     */
    private readonly stdClass $config;

    /**
     * Met en cache les appels de journalisation pour la barre de débogage.
     */
    public array $logCache = [];

    /**
     * Devrions-nous mettre en cache nos éléments enregistrés ?
     *
     * @var bool
     */
    protected $cacheLogs = false;

    /**
     * Instance monolog
     */
    private readonly MonologLogger $monolog;

    public function __construct(bool $debug = BLITZ_DEBUG)
    {
        $this->config = (object) config('log');

        $this->monolog = new MonologLogger(str_replace(' ', '-', $this->config->name ?? 'application'));

        foreach (($this->config->handlers ?? []) as $handler => $options) {
            $this->pushHandler($handler, (object) $options);
        }

        foreach (($this->config->processors ?? []) as $processor) {
            $this->pushProcessor($processor);
        }

        $this->cacheLogs = $debug;
        if ($this->cacheLogs) {
            $this->logCache = [];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->monolog->log($level, $message, $context);

        if ($this->cacheLogs) {
            $this->logCache[] = [
                'level' => $level,
                'msg'   => $message,
            ];
        }
    }

    /**
     * Ajoute les differents gestionnaires prise en charge par la configuration /app/Config/log.php
     */
    private function pushHandler(string $handler, stdClass $options)
    {
        match ($handler) {
            'error'    => $this->pushErrorHandler($options),
            'email'    => $this->pushEmailHandler($options),
            'telegram' => $this->pushTelegramHandler($options),
            'chrome'   => $this->pushChromeHandler($options),
            'firebug'  => $this->pushFirebugHandler($options),
            'browser'  => $this->pushBrowserHandler($options),
            default    => $this->pushFileHandler($options),
        };
    }

    /**
     * Ajoute un gestionnaire de log de type Fichier
     *
     * Enregistre les problèmes dans des fichiers de journalisation
     */
    private function pushFileHandler(stdClass $options): void
    {
        $directory = rtrim($options->path ?: LOG_PATH, DS) . DS;
        $filename  = strtolower($this->config->name ?: 'application');
        $extension = $options->extension ?: '.log';

        if (($options->dayly_rotation ?: true) === true) {
            $handler = new RotatingFileHandler($directory . $filename . $extension, $options->max_files ?: 0, $options->level ?: LogLevel::DEBUG, true, $options->permissions ?: 644);
        } else {
            $handler = new StreamHandler($directory . $filename . $extension, $options->level ?: LogLevel::DEBUG, true, $options->permissions ?: 644);
        }

        $this->monolog->pushHandler(
            $this->setFormatter($handler, ['json', 'line', 'scalar', 'normalizer'], $options->format)
        );
    }

    /**
     * Ajoute un gestionnaire de log de type PHP error_log
     *
     * Enregistre les problèmes dans la fonction PHP error_log().
     */
    private function pushErrorHandler(stdClass $options): void
    {
        $handler = new ErrorLogHandler($options->type ?: ErrorLogHandler::OPERATING_SYSTEM, $options->level ?: LogLevel::DEBUG);

        $this->monolog->pushHandler(
            $this->setFormatter($handler, ['json', 'line'], $options->format)
        );
    }

    /**
     * Ajoute un gestionnaire de log de type Email
     *
     * Envoi un email à l'administrateur en cas de problème
     */
    private function pushEmailHandler(stdClass $options): void
    {
        $handler = new NativeMailerHandler($options->to, $options->subject, $options->from, $options->level ?: LogLevel::ERROR);

        $this->monolog->pushHandler(
            $this->setFormatter($handler, ['html', 'json', 'line'], $options->format)
        );
    }

    private function pushTelegramHandler(stdClass $options): void
    {
        $handler = new TelegramBotHandler($options->api_key, $options->channel, $options->level ?: LogLevel::DEBUG);

        $this->monolog->pushHandler(
            $this->setFormatter($handler, [], $options->format)
        );
    }

    /**
     * Ajoute un gestionnaire de log pour chrome
     *
     * Affichera les log dans la console de chrome
     */
    private function pushChromeHandler(stdClass $options): void
    {
        $handler = new ChromePHPHandler($options->level ?: LogLevel::DEBUG);

        $this->monolog->pushHandler(
            $this->setFormatter($handler, [], $options->format)
        );
    }

    /**
     * Ajoute un gestionnaire de log pour firebug
     *
     * Affichera les log dans la console firebug
     */
    private function pushFirebugHandler(stdClass $options): void
    {
        $handler = new FirePHPHandler();

        $this->monolog->pushHandler(
            $this->setFormatter($handler, [], $options->format)
        );
    }

    /**
     * Ajoute un gestionnaire de log pour les navigateurs
     *
     * Affichera les log dans la console des navigateurs
     */
    private function pushBrowserHandler(stdClass $options): void
    {
        $handler = new BrowserConsoleHandler();

        $this->monolog->pushHandler(
            $this->setFormatter($handler, [], $options->format)
        );
    }

    /**
     * Ajoute les processeur au gestionnaire de log
     *
     * Un processor permet d'ajouter des méta données aux log générés
     */
    private function pushProcessor(string $processor)
    {
        match ($processor) {
            'web'           => $this->monolog->pushProcessor(new WebProcessor()),
            'introspection' => $this->monolog->pushProcessor(new IntrospectionProcessor()),
            'hostname'      => $this->monolog->pushProcessor(new HostnameProcessor()),
            'process_id'    => $this->monolog->pushProcessor(new ProcessIdProcessor()),
            'uid'           => $this->monolog->pushProcessor(new UidProcessor()),
            'memory_usage'  => $this->monolog->pushProcessor(new MemoryUsageProcessor()),
            'psr'           => $this->monolog->pushProcessor(new PsrLogMessageProcessor()),
            default         => throw new InvalidArgumentException('Invalid formatter for log processor. Accepts values: web/introspection/hostname/process_id/uid/memory_usage/psr'),
        };
    }

    /**
     * Definit le formateur des gestionnaire
     *
     * @param array $allowed Formats autorisés
     */
    private function setFormatter(object $handler, array $allowed, ?string $format = 'json'): object
    {
        if (! method_exists($handler, 'setFormatter')) {
            return $handler;
        }

        if ($format === null || $format === '') {
            $format = 'json';
        }
        if ($allowed !== [] && ! in_array($format, $allowed, true)) {
            throw new InvalidArgumentException('Invalid formatter for log file handler. Accepts values: ' . implode('/', $allowed));
        }

        switch ($format) {
            case 'json':
                $handler->setFormatter(new JsonFormatter());
                break;

            case 'line':
                $handler->setFormatter(new LineFormatter(null, $this->config->date_format ?? 'Y-m-d H:i:s'));
                break;

            case 'normalizer':
                $handler->setFormatter(new NormalizerFormatter($this->config->date_format ?? 'Y-m-d H:i:s'));
                break;

            case 'scalar':
                $handler->setFormatter(new ScalarFormatter($this->config->date_format ?? 'Y-m-d H:i:s'));
                break;

            case 'html':
                $handler->setFormatter(new HtmlFormatter($this->config->date_format ?? 'Y-m-d H:i:s'));
                break;

            default:
                break;
        }

        return $handler;
    }
}
