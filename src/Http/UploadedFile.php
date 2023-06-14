<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Http;

use BlitzPHP\Filesystem\FileNotFoundException;
use BlitzPHP\Http\Concerns\FileHelpers;
use BlitzPHP\Loader\Services;
use BlitzPHP\Traits\Macroable;
use BlitzPHP\Utilities\Iterable\Arr;
use GuzzleHttp\Psr7\UploadedFile as GuzzleUploadedFile;

class UploadedFile extends GuzzleUploadedFile
{
    use FileHelpers;
    use Macroable;

    /**
     * Stockez le fichier téléchargé sur un disque de système de fichiers.
     *
     * @return false|string
     */
    public function store(string $path, array|string $options = [])
    {
        return $this->storeAs($path, $this->hashName(), $this->parseOptions($options));
    }

    /**
     * Stockez le fichier téléchargé sur un disque de système de fichiers avec une visibilité publique.
     *
     * @return false|string
     */
    public function storePublicly(string $path, array|string $options = [])
    {
        return $this->storePubliclyAs($path, $this->hashName(), $options);
    }

    /**
     * Stockez le fichier téléchargé sur un disque de système de fichiers avec une visibilité publique.
     *
     * @return false|string
     */
    public function storePubliclyAs(string $path, string $name, array|string $options = [])
    {
        $options = $this->parseOptions($options);

        $options['visibility'] = 'public';

        return $this->storeAs($path, $name, $options);
    }

    /**
     * Stockez le fichier téléchargé sur un disque de système de fichiers.
     *
     * @return false|string
     */
    public function storeAs(string $path, string $name, array|string $options = [])
    {
        $options = $this->parseOptions($options);

        $disk = Arr::pull($options, 'disk');

        Services::storage()->disk($disk)->putFileAs(
            $path,
            $this,
            $name,
            $options
        );
    }

    /**
     * Obtenez le contenu du fichier téléchargé.
     *
     * @return false|string
     *
     * @throws FileNotFoundException
     */
    public function get()
    {
        if (! $this->isValid()) {
            throw new FileNotFoundException("Le fichier n'existe pas dans le chemin {$this->getPathname()}.");
        }

        return file_get_contents($this->getPathname());
    }

    /**
     * Obtenez l'extension du fichier fournie par le client.
     */
    public function clientExtension(): string
    {
        return pathinfo($this->getClientFilename(), PATHINFO_EXTENSION);
    }

    public function getPathname(): string
    {
        return $this->getStream()->getMetadata('uri');
    }

    /**
     * Créez une nouvelle instance de fichier à partir d'une instance de base.
     *
     * @return static
     */
    public static function createFromBase(GuzzleUploadedFile $file)
    {
        return $file instanceof static ? $file : new static(
            $file->getStream(),
            $file->getSize(),
            $file->getError(),
            $file->getClientFilename(),
            $file->getClientMediaType()
        );
    }

    /**
     * Analysez et formatez les options données.
     */
    protected function parseOptions(string|array $options): array
    {
        if (is_string($options)) {
            $options = ['disk' => $options];
        }

        return $options;
    }
}
