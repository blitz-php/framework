<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Output;

use BlitzPHP\Config\Config;
use BlitzPHP\Loader\Load;
use BlitzPHP\Loader\Services;
use BlitzPHP\Utilities\Arr;
use MessageFormatter;

class Language
{
    /**
     * Stores the retrieved language lines
     * from files for faster retrieval on
     * second use.
     *
     * @var array
     */
    protected $language = [];

    /**
     * The current language/locale to work with.
     *
     * @var string
     */
    protected $locale;

    /**
     * Boolean value whether the intl
     * libraries exist on the system.
     *
     * @var bool
     */
    protected $intlSupport = false;

    /**
     * Stores filenames that have been
     * loaded so that we don't load them again.
     *
     * @var array
     */
    protected $loadedFiles = [];

    public function __construct()
    {
        if (class_exists('\MessageFormatter')) {
            $this->intlSupport = true;
        }
    }

    /**
     * Sets the current locale to use when performing string lookups.
     */
    public function setLocale(?string $locale = null): self
    {
        $this->findLocale($locale);

        return $this;
    }

    /**
     * Gets the current locale, with a fallback to the default
     * locale if none is set.
     */
    public function getLocale(): string
    {
        if (empty($this->locale)) {
            $this->findLocale();
        }

        return $this->locale;
    }

    /**
     * Parses the language string for a file, loads the file, if necessary,
     * getting the line.
     *
     * @param string $line Line.
     * @param array  $args Arguments.
     *
     * @return string|string[] Returns line.
     */
    public function getLine(string $line, ?array $args = [])
    {
        // ignore requests with no file specified
        if (! strpos($line, '.')) {
            return $line;
        }
        if (empty($args)) {
            $args = [];
        }

        // Parse out the file name and the actual alias.
        // Will load the language file and strings.
        [
            $file,
            $parsedLine,
        ] = $this->parseLine($line, $this->locale);

        $output = Arr::getRecursive($this->language[$this->locale][$file], $parsedLine);

        if ($output === null && strpos($this->locale, '-')) {
            [$locale] = explode('-', $this->locale, 2);

            [
                $file,
                $parsedLine,
            ] = $this->parseLine($line, $locale);

            $output = Arr::getRecursive($this->language[$locale][$file], $parsedLine);
        }

        // if still not found, try English
        if (empty($output)) {
            $this->parseLine($line, 'en');

            $output = Arr::getRecursive($this->language[$this->locale][$file], $parsedLine);
            //$output = $this->language['en'][$file][$parsedLine] ?? null;
        }

        $output ??= $line;

        if (! empty($args)) {
            $output = $this->formatMessage($output, $args);
        }

        return $output;
    }

    /**
     * Parses the language string which should include the
     * filename as the first segment (separated by period).
     */
    protected function parseLine(string $line, string $locale): array
    {
        $file = substr($line, 0, strpos($line, '.'));
        $line = substr($line, strlen($file) + 1);

        /*
        $line = explode('.', $line);
        $file = array_shift($line);
        $line = implode('.', $line);
        */

        if (! isset($this->language[$locale][$file]) || ! array_key_exists($line, $this->language[$locale][$file])) {
            $this->load($file, $locale);
        }

        return [
            $file,
            $line,
        ];
    }

    /**
     * Advanced message formatting.
     *
     * @param array|string $message Message.
     * @param array        $args    Arguments.
     *
     * @return array|string Returns formatted message.
     */
    protected function formatMessage($message, array $args = [])
    {
        if (! $this->intlSupport || ! $args) {
            return $message;
        }

        if (is_array($message)) {
            foreach ($message as $index => $value) {
                $message[$index] = $this->formatMessage($value, $args);
            }

            return $message;
        }

        return MessageFormatter::formatMessage($this->locale, $message, $args);
    }

    /**
     * Charge un fichier de langue dans les paramètres régionaux actuels. Si $return est vrai,
     * renverra le contenu du fichier, sinon fusionnera avec
     * les lignes linguistiques existantes.
     *
     * @return array|void
     */
    protected function load(string $file, string $locale, bool $return = false)
    {
        if (! array_key_exists($locale, $this->loadedFiles)) {
            $this->loadedFiles[$locale] = [];
        }
        if (in_array($file, $this->loadedFiles[$locale], true)) {
            // Don't load it more than once.
            return [];
        }
        if (! array_key_exists($locale, $this->language)) {
            $this->language[$locale] = [];
        }

        if (! array_key_exists($file, $this->language[$locale])) {
            $this->language[$locale][$file] = [];
        }

        $lang = Load::lang($file, $locale);

        if ($return) {
            return $lang;
        }

        $this->loadedFiles[$locale][] = $file;

        // Merge our string
        $this->language[$locale][$file] = $lang;
    }

    /**
     * Cherche la locale appropriee par rapport a la requete de l'utilisateur
     */
    public static function searchLocale(?string $locale = null): string
    {
        $config = Config::get('app');

        if (empty($locale)) {
            $locale = Services::negotiator()->language($config['supported_locales']);
        }
        if (empty($locale)) {
            $locale = $config['language'];
        }

        return self::normalizeLocale(empty($locale) ? 'en' : $locale);
    }

    private function findLocale(?string $locale = null): string
    {
        $this->locale = self::searchLocale($locale);

        return $this->locale;
    }

    /**
     * Valide la langue entree
     */
    private static function normalizeLocale(string $locale): string
    {
        return $locale;
    }
}
