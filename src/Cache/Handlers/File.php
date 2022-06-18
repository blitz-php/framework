<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cache\Handlers;

use BlitzPHP\Cache\InvalidArgumentException;
use CallbackFilterIterator;
use Exception;
use FilesystemIterator;
use LogicException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use SplFileObject;

class File extends BaseHandler
{
    /**
     * Instance de la classe SplFileObject
     *
     * @var SplFileObject|null
     */
    protected $_File;

    /**
     * La configuration par défaut utilisée sauf si elle est remplacée par la configuration d'exécution
     *
     * - `duration` Spécifiez combien de temps durent les éléments de cette configuration de cache.
     * - `groups` Liste des groupes ou 'tags' associés à chaque clé stockée dans cette configuration.
     * 			pratique pour supprimer un groupe complet du cache.
     * - `lock` Utilisé par FileCache. Les fichiers doivent-ils être verrouillés avant d'y écrire ?
     * - `mask` Le masque utilisé pour les fichiers créés
     * - `path` Chemin d'accès où les fichiers cache doivent être enregistrés. Par défaut, le répertoire temporaire du système.
     * - `prefix` Préfixé à toutes les entrées. Bon pour quand vous avez besoin de partager un keyspace
     * 			avec une autre configuration de cache ou une autre application. cache::gc d'être appelé automatiquement.
     * - `serialize` Les objets du cache doivent-ils être sérialisés en premier.
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'duration'  => 3600,
        'groups'    => [],
        'lock'      => true,
        'mask'      => 0664,
        'path'      => null,
        'prefix'    => 'blitz_',
        'serialize' => true,
    ];

    /**
     * Vrai sauf si FileEngine :: __active(); échoue
     *
     * @var bool
     */
    protected $_init = true;

    /**
     * {@inheritDoc}
     */
    public function init(array $config = []): bool
    {
        parent::init($config);

        if ($this->_config['path'] === null) {
            $this->_config['path'] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'blitz_cache' . DIRECTORY_SEPARATOR;
        }
        if (substr($this->_config['path'], -1) !== DIRECTORY_SEPARATOR) {
            $this->_config['path'] .= DIRECTORY_SEPARATOR;
        }
        if ($this->_groupPrefix) {
            $this->_groupPrefix = str_replace('_', DIRECTORY_SEPARATOR, $this->_groupPrefix);
        }

        return $this->_active();
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value, $ttl = null): bool
    {
        if ($value === '' || ! $this->_init) {
            return false;
        }

        $key = $this->_key($key);

        if ($this->_setKey($key, true) === false) {
            return false;
        }

        if (! empty($this->_config['serialize'])) {
            $value = serialize($value);
        }

        $expires  = time() + $this->duration($ttl);
        $contents = implode('', [$expires, PHP_EOL, $value, PHP_EOL]);

        if ($this->_config['lock']) {
            /** @psalm-suppress PossiblyNullReference */
            $this->_File->flock(LOCK_EX);
        }

        /** @psalm-suppress PossiblyNullReference */
        $this->_File->rewind();
        $success = $this->_File->ftruncate(0)
            && $this->_File->fwrite($contents)
            && $this->_File->fflush();

        if ($this->_config['lock']) {
            $this->_File->flock(LOCK_UN);
        }
        $this->_File = null;

        return $success;
    }

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null)
    {
        $key = $this->_key($key);

        if (! $this->_init || $this->_setKey($key) === false) {
            return $default;
        }

        if ($this->_config['lock']) {
            /** @psalm-suppress PossiblyNullReference */
            $this->_File->flock(LOCK_SH);
        }

        /** @psalm-suppress PossiblyNullReference */
        $this->_File->rewind();
        $time      = time();
        $cachetime = (int) $this->_File->current();

        if ($cachetime < $time) {
            if ($this->_config['lock']) {
                $this->_File->flock(LOCK_UN);
            }

            return $default;
        }

        $data = '';
        $this->_File->next();

        while ($this->_File->valid()) {
            /** @psalm-suppress PossiblyInvalidOperand */
            $data .= $this->_File->current();
            $this->_File->next();
        }

        if ($this->_config['lock']) {
            $this->_File->flock(LOCK_UN);
        }

        $data = trim($data);

        if ($data !== '' && ! empty($this->_config['serialize'])) {
            $data = unserialize($data);
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key): bool
    {
        $key = $this->_key($key);

        if ($this->_setKey($key) === false || ! $this->_init) {
            return false;
        }

        /** @psalm-suppress PossiblyNullReference */
        $path        = $this->_File->getRealPath();
        $this->_File = null;

        if ($path === false) {
            return false;
        }

        // phpcs:disable
        return @unlink($path);
        // phpcs:enable
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        if (! $this->_init) {
            return false;
        }
        $this->_File = null;

        $this->_clearDirectory($this->_config['path']);

        $directory = new RecursiveDirectoryIterator(
            $this->_config['path'],
            FilesystemIterator::SKIP_DOTS
        );
        $contents = new RecursiveIteratorIterator(
            $directory,
            RecursiveIteratorIterator::SELF_FIRST
        );
        $cleared = [];
        /** @var SplFileInfo $fileInfo */
        foreach ($contents as $fileInfo) {
            if ($fileInfo->isFile()) {
                unset($fileInfo);

                continue;
            }

            $realPath = $fileInfo->getRealPath();
            if (! $realPath) {
                unset($fileInfo);

                continue;
            }

            $path = $realPath . DIRECTORY_SEPARATOR;
            if (! in_array($path, $cleared, true)) {
                $this->_clearDirectory($path);
                $cleared[] = $path;
            }

            // les itérateurs internes possibles doivent également être désactivés pour que les verrous sur les parents soient libérés
            unset($fileInfo);
        }

        // la désactivation des itérateurs aide à libérer les verrous possibles dans certains environnements,
        // ce qui pourrait sinon faire échouer `rmdir()`
        unset($directory, $contents);

        return true;
    }

    /**
     * Utilisé pour effacer un répertoire de fichiers correspondants.
     */
    protected function _clearDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $dir = dir($path);
        if (! $dir) {
            return;
        }

        $prefixLength = strlen($this->_config['prefix']);

        while (($entry = $dir->read()) !== false) {
            if (substr($entry, 0, $prefixLength) !== $this->_config['prefix']) {
                continue;
            }

            try {
                $file = new SplFileObject($path . $entry, 'r');
            } catch (Exception $e) {
                continue;
            }

            if ($file->isFile()) {
                $filePath = $file->getRealPath();
                unset($file);

                // phpcs:disable
                @unlink($filePath);
                // phpcs:enable
            }
        }

        $dir->close();
    }

    /**
     * Pas implementé
     *
     * @throws LogicException
     */
    public function decrement(string $key, int $offset = 1)
    {
        throw new LogicException('Files cannot be atomically decremented.');
    }

    /**
     * Pas implementé
     *
     * @throws LogicException
     */
    public function increment(string $key, int $offset = 1)
    {
        throw new LogicException('Files cannot be atomically incremented.');
    }

    /**
     * Définit la clé de cache actuelle que cette classe gère et crée un SplFileObject inscriptible
     * pour le fichier cache auquel la clé fait référence.
     *
     * @param bool $createKey Whether the key should be created if it doesn't exists, or not
     *
     * @return bool true if the cache key could be set, false otherwise
     */
    protected function _setKey(string $key, bool $createKey = false): bool
    {
        $groups = null;
        if ($this->_groupPrefix) {
            $groups = vsprintf($this->_groupPrefix, $this->groups());
        }
        $dir = $this->_config['path'] . $groups;

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $path = new SplFileInfo($dir . $key);

        if (! $createKey && ! $path->isFile()) {
            return false;
        }
        if (
            empty($this->_File)
            || $this->_File->getBasename() !== $key
            || $this->_File->valid() === false
        ) {
            $exists = is_file($path->getPathname());

            try {
                $this->_File = $path->openFile('c+');
            } catch (Exception $e) {
                trigger_error($e->getMessage(), E_USER_WARNING);

                return false;
            }
            unset($path);

            if (! $exists && ! chmod($this->_File->getPathname(), (int) $this->_config['mask'])) {
                trigger_error(sprintf(
                    'Could not apply permission mask "%s" on cache file "%s"',
                    $this->_File->getPathname(),
                    $this->_config['mask']
                ), E_USER_WARNING);
            }
        }

        return true;
    }

    /**
     * Déterminer si le répertoire de cache est accessible en écriture
     */
    protected function _active(): bool
    {
        $dir     = new SplFileInfo($this->_config['path']);
        $path    = $dir->getPathname();
        $success = true;
        if (! is_dir($path)) {
            // phpcs:disable
            $success = @mkdir($path, 0775, true);
            // phpcs:enable
        }

        $isWritableDir = ($dir->isDir() && $dir->isWritable());
        if (! $success || ($this->_init && ! $isWritableDir)) {
            $this->_init = false;
            trigger_error(sprintf(
                '%s is not writable',
                $this->_config['path']
            ), E_USER_WARNING);
        }

        return $success;
    }

    /**
     * {@inheritDoc}
     */
    protected function _key($key): string
    {
        $key = parent::_key($key);

        if (preg_match('/[\/\\<>?:|*"]/', $key)) {
            throw new InvalidArgumentException(
                "Cache key `{$key}` contains invalid characters. " .
                'You cannot use /, \\, <, >, ?, :, |, *, or " in cache keys.'
            );
        }

        return $key;
    }

    /**
     * Supprime récursivement tous les fichiers sous n'importe quel répertoire nommé $group
     */
    public function clearGroup(string $group): bool
    {
        $this->_File = null;

        $prefix = (string) $this->_config['prefix'];

        $directoryIterator = new RecursiveDirectoryIterator($this->_config['path']);
        $contents          = new RecursiveIteratorIterator(
            $directoryIterator,
            RecursiveIteratorIterator::CHILD_FIRST
        );
        $filtered = new CallbackFilterIterator(
            $contents,
            static function (SplFileInfo $current) use ($group, $prefix) {
                if (! $current->isFile()) {
                    return false;
                }

                $hasPrefix = $prefix === ''
                    || strpos($current->getBasename(), $prefix) === 0;
                if ($hasPrefix === false) {
                    return false;
                }

                $pos = strpos(
                    $current->getPathname(),
                    DIRECTORY_SEPARATOR . $group . DIRECTORY_SEPARATOR
                );

                return $pos !== false;
            }
        );

        foreach ($filtered as $object) {
            $path = $object->getPathname();
            unset($object);
            // phpcs:ignore
            @unlink($path);
        }

        // unsetting iterators helps releasing possible locks in certain environments,
        // which could otherwise make `rmdir()` fail
        unset($directoryIterator, $contents, $filtered);

        return true;
    }
}
