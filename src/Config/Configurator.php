<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Config;

use BlitzPHP\Exceptions\ConfigException;
use BlitzPHP\Exceptions\UnknownOptionException;
use Dflydev\DotAccessData\Data;
use Dflydev\DotAccessData\Exception\DataException;
use Dflydev\DotAccessData\Exception\InvalidPathException;
use Dflydev\DotAccessData\Exception\MissingPathException;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
use Nette\Schema\ValidationException;
use stdClass;

/**
 * @credit league/config (c) Colin O'Dell <colinodell@gmail.com>
 */
final class Configurator
{
    /**
     * @psalm-readonly
     */
    private readonly Data $userConfig;

    /**
     * @psalm-allow-private-mutation
     */
    private Data $finalConfig;

    /**
     * @var array<string, mixed>
     *
     * @psalm-allow-private-mutation
     */
    private array $cache = [];

    /**
     * @param array<string, Schema> $configSchemas
     */
    public function __construct(private array $configSchemas = [])
    {
        $this->userConfig  = new Data();
        $this->finalConfig = new Data();
    }

    /**
     * Enregistre un nouveau schéma de configuration à la clé de niveau supérieur donnée
     *
     * @psalm-allow-private-mutation
     */
    public function addSchema(string $key, Schema $schema): void
    {
        $this->invalidate();

        $this->configSchemas[$key] = $schema;
    }

    /**
     * @psalm-allow-private-mutation
     */
    public function merge(array $config = []): void
    {
        $this->invalidate();

        $this->userConfig->import($config, Data::REPLACE);
    }

    /**
     * @psalm-allow-private-mutation
     *
     * @param mixed $value
     */
    public function set(string $key, $value): void
    {
        $this->invalidate();

        try {
            $this->userConfig->set($key, $value);
        } catch (DataException $ex) {
            throw new UnknownOptionException($ex->getMessage(), $key, (int) $ex->getCode(), $ex);
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    public function get(string $key)
    {
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        try {
            $this->build(self::getTopLevelKey($key));

            return $this->cache[$key] = $this->finalConfig->get($key);
        } catch (InvalidPathException|MissingPathException $ex) {
            throw new UnknownOptionException($ex->getMessage(), $key, (int) $ex->getCode(), $ex);
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    public function exists(string $key): bool
    {
        if (array_key_exists($key, $this->cache)) {
            return true;
        }

        try {
            $this->build(self::getTopLevelKey($key));

            return $this->finalConfig->has($key);
        } catch (InvalidPathException|UnknownOptionException) {
            return false;
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    private function invalidate(): void
    {
        $this->cache       = [];
        $this->finalConfig = new Data();
    }

    /**
     * Applique le schéma à la configuration pour renvoyer la configuration finale
     *
     * @throws ValidationException
     *
     * @psalm-allow-private-mutation
     */
    private function build(string $topLevelKey): void
    {
        if ($this->finalConfig->has($topLevelKey)) {
            return;
        }

        if (! isset($this->configSchemas[$topLevelKey])) {
            throw new UnknownOptionException(sprintf('Schéma de configuration manquant pour "%s".', $topLevelKey), $topLevelKey);
        }

        try {
            $userData = [$topLevelKey => $this->userConfig->get($topLevelKey)];
        } catch (DataException) {
            $userData = [];
        }

        try {
            $schema    = $this->configSchemas[$topLevelKey];
            $processor = new Processor();
            $processed = $processor->process(Expect::structure([$topLevelKey => $schema]), $userData);

            $this->raiseAnyDeprecationNotices($processor->getWarnings());

            $this->finalConfig->import((array) self::convertStdClassesToArrays($processed));
        } catch (ValidationException $ex) {
            throw new ConfigException($ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Convertit récursivement les instances stdClass en tableaux
     *
     * @phpstan-template T
     *
     * @param T $data
     *
     * @return         mixed
     * @phpstan-return ($data is stdClass ? array<string, mixed> : T)
     */
    private static function convertStdClassesToArrays($data)
    {
        if ($data instanceof stdClass) {
            $data = (array) $data;
        }

        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = self::convertStdClassesToArrays($v);
            }
        }

        return $data;
    }

    /**
     * @param list<string> $warnings
     */
    private function raiseAnyDeprecationNotices(array $warnings): void
    {
        foreach ($warnings as $warning) {
            @trigger_error($warning, E_USER_DEPRECATED);
        }
    }

    /**
     * @throws InvalidPathException
     */
    private static function getTopLevelKey(string $path): string
    {
        if ($path === '') {
            throw new InvalidPathException('$path ne peut pas être une chaîne vide');
        }

        $path = str_replace(['.', '/'], '.', $path);

        $firstDelimiter = strpos($path, '.');
        if ($firstDelimiter === false) {
            return $path;
        }

        return substr($path, 0, $firstDelimiter);
    }
}
