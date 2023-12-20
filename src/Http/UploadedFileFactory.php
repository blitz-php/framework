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

use BlitzPHP\Filesystem\Files\UploadedFile;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Classe d'usine pour la création d'instances de fichiers téléchargés.
 *
 * @credit <a href="https://docs.laminas.dev/laminas-diactoros/">Laminas\Diactoros</a>
 */
class UploadedFileFactory implements UploadedFileFactoryInterface
{
    /**
     * Créer un nouveau fichier téléchargé.
     *
     * Si une taille n'est pas fournie, elle sera déterminée en vérifiant la taille du flux.
     *
     * @see http://php.net/manual/features.file-upload.post-method.php
     * @see http://php.net/manual/features.file-upload.errors.php
     *
     * @param \Psr\Http\Message\StreamInterface $stream          Le flux sous-jacent représentant le contenu du fichier téléchargé.
     * @param int|null                          $size            La taille du fichier en octets.
     * @param int                               $error           L'erreur de téléchargement du fichier PHP.
     * @param string|null                       $clientFilename  Le nom du fichier tel qu'il est fourni par le client, le cas échéant.
     * @param string|null                       $clientMediaType Le type de média tel qu'il est fourni par le client, le cas échéant.
     *
     * @throws InvalidArgumentException Si la ressource du fichier n'est pas lisible.
     */
    public function createUploadedFile(
        StreamInterface $stream,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    ): UploadedFileInterface {
        if ($size === null) {
            $size = $stream->getSize() ?? 0;
        }

        return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }

    /**
     * Créer une instance de fichier téléchargé à partir d'un tableau de valeurs.
     *
     * @param array $spec Une seule entrée $_FILES.
     *
     * @throws InvalidArgumentException Si une ou plusieurs des clés tmp_name, size ou error sont manquantes dans $spec.
     */
    public static function makeUploadedFile(array $spec): UploadedFile
    {
        if (! isset($spec['tmp_name']) || ! isset($spec['size']) || ! isset($spec['error'])) {
            throw new InvalidArgumentException(sprintf(
                '$spec fourni à %s DOIT contenir chacune des clés "tmp_name", "size", et "error" ; une ou plusieurs étaient manquantes',
                __FUNCTION__
            ));
        }

        return new UploadedFile(
            $spec['tmp_name'],
            (int) $spec['size'],
            $spec['error'],
            $spec['name'] ?? null,
            $spec['type'] ?? null
        );
    }

    /**
     * Normaliser les fichiers téléchargés
     *
     * Transforme chaque valeur en une instance UploadedFile, et s'assure que les tableaux imbriqués sont normalisés.
     *
     * @see https://github.com/laminas/laminas-diactoros/blob/3.4.x/src/functions/normalize_uploaded_files.php
     *
     * @return UploadedFileInterface[]
     *
     * @throws InvalidArgumentException Pour les valeurs non reconnues.
     */
    public static function normalizeUploadedFiles(array $files): array
    {
        /**
         * Traverse une arborescence imbriquée de spécifications de fichiers téléchargés.
         *
         * @param array[]|string[]      $tmpNameTree
         * @param array[]|int[]         $sizeTree
         * @param array[]|int[]         $errorTree
         * @param array[]|string[]|null $nameTree
         * @param array[]|string[]|null $typeTree
         *
         * @return array[]|UploadedFile[]
         */
        $recursiveNormalize = static function (
            array $tmpNameTree,
            array $sizeTree,
            array $errorTree,
            ?array $nameTree = null,
            ?array $typeTree = null
        ) use (&$recursiveNormalize): array {
            $normalized = [];

            foreach ($tmpNameTree as $key => $value) {
                if (is_array($value)) {
                    // Traverse
                    $normalized[$key] = $recursiveNormalize(
                        $tmpNameTree[$key],
                        $sizeTree[$key],
                        $errorTree[$key],
                        $nameTree[$key] ?? null,
                        $typeTree[$key] ?? null
                    );

                    continue;
                }

                $normalized[$key] = static::makeUploadedFile([
                    'tmp_name' => $tmpNameTree[$key],
                    'size'     => $sizeTree[$key],
                    'error'    => $errorTree[$key],
                    'name'     => $nameTree[$key] ?? null,
                    'type'     => $typeTree[$key] ?? null,
                ]);
            }

            return $normalized;
        };

        /**
         * Normaliser un tableau de spécifications de fichiers.
         *
         * Boucle sur tous les fichiers imbriqués (déterminés par la réception d'un tableau à la clé `tmp_name` d'une spécification `$_FILES`) et renvoie un tableau normalisé d'instances UploadedFile.
         *
         * Cette fonction normalise un tableau `$_FILES` représentant un ensemble imbriqué de fichiers téléchargés tels que produits par les SAPI php-fpm, CGI SAPI, ou mod_php SAPI.
         *
         * @return UploadedFile[]
         */
        $normalizeUploadedFileSpecification = static function (array $files = []) use (&$recursiveNormalize): array {
            if (
                ! isset($files['tmp_name']) || ! is_array($files['tmp_name'])
                                            || ! isset($files['size']) || ! is_array($files['size'])
                                            || ! isset($files['error']) || ! is_array($files['error'])
            ) {
                throw new InvalidArgumentException(sprintf(
                    'Les fichiers fournis à %s DOIVENT contenir chacune des clés "tmp_name", "size" et "error",
				chacune étant représentée sous la forme d\'un tableau ;
				une ou plusieurs valeurs manquaient ou n\'étaient pas des tableaux.',
                    __FUNCTION__
                ));
            }

            return $recursiveNormalize(
                $files['tmp_name'],
                $files['size'],
                $files['error'],
                $files['name'] ?? null,
                $files['type'] ?? null
            );
        };

        $normalized = [];

        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalized[$key] = $value;

                continue;
            }

            if (is_array($value) && isset($value['tmp_name']) && is_array($value['tmp_name'])) {
                $normalized[$key] = $normalizeUploadedFileSpecification($value);

                continue;
            }

            if (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = self::makeUploadedFile($value);

                continue;
            }

            if (is_array($value)) {
                $normalized[$key] = self::normalizeUploadedFiles($value);

                continue;
            }

            throw new InvalidArgumentException('Valeur non valide dans la spécification des fichiers');
        }

        return $normalized;
    }
}
