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
 * Configurateur pour validation et merger de configurations.
 *
 * Utilise DotAccessData pour dot notation et Nette\Schema pour validation.
 * Supporte deep merge et cache.
 *
 * @credit league/config (c) Colin O'Dell <colinodell@gmail.com>
 */
final class Configurator
{
    /**
     * Configuration utilisateur.
     */
    private readonly Data $userConfig;

    /**
     * Configuration finale (mutable).
     */
    private Data $finalConfig;

    /**
     * Cache des accès (clé → valeur).
     *
     * @var array<string, mixed>
     */
    private array $cache = [];

    /**
     * Constructeur.
     *
     * @param array<string, Schema> $configSchemas Schémas initiaux.
     */
    public function __construct(private array $configSchemas = [])
    {
        $this->userConfig  = new Data();
        $this->finalConfig = new Data();
    }

    /**
     * Enregistre un schéma pour une clé top-level.
     *
     * Invalide le cache.
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
     * Définit une valeur.
     *
     * @throws UnknownOptionException
     */
    public function set(string $key, mixed $value): void
    {
        $this->invalidate();

        try {
            $this->userConfig->set($key, $value);
        } catch (DataException $ex) {
            throw new UnknownOptionException($ex->getMessage(), $key, (int) $ex->getCode(), $ex);
        }
    }

    /**
     * Obtient une valeur via dot notation.
     *
     * Valide et cache.
     *
     * @throws ConfigException Si validation échoue.
     */
    public function get(string $key)
    {
        if (isset($this->cache[$key])) {
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
     * Vérifie l'existence d'une clé.
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
     * Invalide le cache.
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
            $processed = $this->process($topLevelKey, $userData);

            $this->finalConfig->import(self::convertStdClassesToArrays($processed));
        } catch (ValidationException $ex) {
            throw new ConfigException($ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Normalise et valide les données. Le résultat est une donnée complète et propre.
     */
    private function process(string $topLevelKey, array $userData): mixed
    {
        $schema    = $this->configSchemas[$topLevelKey];
        $processor = new Processor();
        $processed = $processor->process(Expect::structure([$topLevelKey => $schema]), $userData);

        $this->raiseAnyDeprecationNotices($processor->getWarnings());

        return $processed;
    }

    /**
     * Convertit récursivement les instances stdClass en tableaux.
     *
     * @template T
     *
     * @param T $data Données.
     *
     * @return ($data is stdClass ? array<string, mixed> : T)
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
     * Émet des notices de dépréciation.
     *
     * @param list<string> $warnings Avertissements.
     */
    private function raiseAnyDeprecationNotices(array $warnings): void
    {
        foreach ($warnings as $warning) {
            @trigger_error($warning, E_USER_DEPRECATED);
        }
    }

    /**
     * Extrait la clé top-level d'un path.
     *
     * Gère . et /.
     *
     * @throws InvalidPathException Si path vide.
     */
    public static function getTopLevelKey(string $path): string
    {
        if ('' === $path = trim($path)) {
            throw new InvalidPathException('Le chemin ne peut pas être une chaîne vide.');
        }

        // Normalise / et . en .
        $path = str_replace(['.', '/'], '.', trim($path, '. '));

        $firstDelimiter = strpos($path, '.');
        if ($firstDelimiter === false) {
            return $path;
        }

        return substr($path, 0, $firstDelimiter);
    }
}
