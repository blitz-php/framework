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
    private Data $userConfig;

    /**
     * @var array<string, Schema>
     *
     * @psalm-allow-private-mutation
     */
    private array $configSchemas = [];

    /**
     * @psalm-allow-private-mutation
     */
    private ?Data $finalConfig = null;

    /**
     * @var array<string, mixed>
     *
     * @psalm-allow-private-mutation
     */
    private array $cache = [];

    /**
     * @param array<string, Schema> $baseSchemas
     */
    public function __construct(array $baseSchemas = [])
    {
        $this->configSchemas = $baseSchemas;
        $this->userConfig    = new Data();
    }

    /**
     * Enregistre un nouveau schéma de configuration à la clé de niveau supérieur donnée
     *
     * @psalm-allow-private-mutation
     */
    public function addSchema(string $key, Schema $schema, bool $overwrite = true): void
    {
        $this->invalidate();

        if ($overwrite || ! isset($this->configSchemas[$key])) {
            $this->configSchemas[$key] = $schema;
        }
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
        if ($this->finalConfig === null) {
            $this->finalConfig = $this->build();
        } elseif (\array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        try {
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
        if ($this->finalConfig === null) {
            $this->finalConfig = $this->build();
        } elseif (\array_key_exists($key, $this->cache)) {
            return true;
        }

        try {
            return $this->finalConfig->has($key);
        } catch (InvalidPathException $ex) {
            return false;
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    private function invalidate(): void
    {
        $this->cache       = [];
        $this->finalConfig = null;
    }

    /**
     * Applique le schéma à la configuration pour renvoyer la configuration finale
     *
     * @throws ValidationException
     *
     * @psalm-allow-private-mutation
     */
    private function build(): Data
    {
        $schema    = Expect::structure($this->configSchemas);
        $processor = new Processor();
        $processed = $processor->process($schema, $this->userConfig->export());

        $this->raiseAnyDeprecationNotices($processor->getWarnings());

        return $this->finalConfig = new Data(self::convertStdClassesToArrays($processed));
    }

    /**
     * Convertit récursivement les instances stdClass en tableaux
     *
     * @param mixed $data
     *
     * @return mixed
     *
     * @psalm-pure
     */
    private static function convertStdClassesToArrays($data)
    {
        if ($data instanceof stdClass) {
            $data = (array) $data;
        }

        if (\is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = self::convertStdClassesToArrays($v);
            }
        }

        return $data;
    }

    /**
     * @param string[] $warnings
     */
    private function raiseAnyDeprecationNotices(array $warnings): void
    {
        foreach ($warnings as $warning) {
            @\trigger_error($warning, \E_USER_DEPRECATED);
        }
    }
}
