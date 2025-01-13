<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Facades;

use BlitzPHP\Utilities\Iterable\LazyCollection;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @method static false|int      append(string $path, string $data)                                                          Ajouter à un fichier.
 * @method static string         basename(string $path)                                                                      Extraire le composant de nom de fin d'un chemin de fichier.
 * @method static mixed          chmod(string $path, ?int $mode = null)                                                      Obtenir ou définir le mode UNIX d'un fichier ou d'un répertoire.
 * @method static bool           cleanDirectory(string $directory)                                                           Vide le répertoire spécifié de tous les fichiers et dossiers.
 * @method static bool           copy(string $path, string $target, bool $overwrite = true)                                  Copiez un fichier vers un nouvel emplacement.
 * @method static bool           copyDirectory(string $directory, string $destination, ?int $options = null)                 Copiez un répertoire d'un emplacement à un autre.
 * @method static bool           delete(array|string $paths)                                                                 Supprimer le fichier à un chemin donné.
 * @method static bool           deleteDirectories(string $directory)                                                        Supprimez tous les répertoires d'un répertoire donné.
 * @method static bool           deleteDirectory(string $directory, bool $preserve = false)                                  Supprimer récursivement un répertoire.
 * @method static array          directories(string $directory, int $depth = 0, bool $hidden = false)                        Récupère tous les répertoires d'un répertoire donné.
 * @method static string         dirname(string $path)                                                                       Extraire le répertoire parent d'un chemin de fichier.
 * @method static SplFileInfo[]  allFiles(string $directory, bool $hidden = false, string $sortBy = 'name')                  Récupère tous les fichiers du répertoire donné (récursif).
 * @method static void           ensureDirectoryExists(string $path, int $mode = 0755, bool $recursive = true)               Assurez-vous qu'un répertoire existe.
 * @method static bool           exists(string $path)                                                                        Déterminez si un fichier ou un répertoire existe.
 * @method static string         extension(string $path)                                                                     Extrayez l'extension de fichier d'un chemin de fichier.
 * @method static SplFileInfo[]  files(string $directory, bool $hidden = false, string $sortBy = 'name')                     Récupère un tableau de tous les fichiers d'un répertoire.
 * @method static string         get(string $path, bool $lock = false)                                                       Obtenir le contenu d'un fichier.
 * @method static mixed          getRequire(string $path, array $data = [])                                                  Obtenir la valeur renvoyée d'un fichier.
 * @method static array          glob(string $pattern, int $flags = 0)                                                       Trouver les noms de chemin correspondant à un modèle donné.
 * @method static string         hash(string $path, string $algorithm = 'md5')                                               Obtenir le hachage MD5 du fichier au chemin donné.
 * @method static bool           hasSameHash(string $firstFile, string $secondFile)                                          Déterminez si deux fichiers sont identiques en comparant leurs hachages.
 * @method static bool           isDirectory(string $path)                                                                   Déterminer si le chemin donné est un répertoire.
 * @method static bool           isEmptyDirectory(string $directory, bool $ignoreDotFiles = false)                           Déterminer si le chemin donné est un répertoire qui ne contient aucun autre fichier ou répertoire.
 * @method static bool           isFile(string $file)                                                                        Déterminez si le chemin donné est un fichier.
 * @method static bool           isReadable(string $path)                                                                    Déterminez si le chemin donné est lisible.
 * @method static bool           isWritable(string $path)                                                                    Détermine si le chemin donné est accessible en écriture.
 * @method static int            lastModified(string $path)                                                                  Obtenir l'heure de la dernière modification du fichier.
 * @method static LazyCollection lines(string $path)                                                                         Obtenir le contenu d'un fichier une ligne à la fois.
 * @method static bool|void      link(string $target, string $link)                                                          Créez un lien symbolique vers le fichier ou le répertoire cible. Sous Windows, un lien physique est créé si la cible est un fichier.
 * @method static bool           makeDirectory(string $path, int $mode = 0755, bool $recursive = false, bool $force = false) Créez un répertoire.
 * @method static false|string   mimeType(string $path)                                                                      Récupère le type mime d'un fichier donné.
 * @method static bool           missing(string $path)                                                                       Déterminez si un fichier ou un répertoire est manquant.
 * @method static bool           move(string $path, string $target, bool $overwrite = true)                                  Déplacer un fichier vers un nouvel emplacement.
 * @method static bool           moveDirectory(string $from, string $to, bool $overwrite = false)                            Déplacer un répertoire.
 * @method static string         name(string $path)                                                                          Extraire le nom de fichier d'un chemin de fichier.
 * @method static false|int      prepend(string $path, string $data)                                                         Ajouter au début d'un fichier.
 * @method static false|int      put(string $path, string $contents, bool $lock = false)                                     Écrire le contenu d'un fichier.
 * @method static void           replace(string $path, string $contents, ?int $mode = null)                                  Écrire le contenu d'un fichier, en le remplaçant de manière atomique s'il existe déjà.
 * @method static void           replaceInFile(array|string $search, array|string $replace, string $path)                    Remplace une chaîne donnée dans un fichier donné.
 * @method static mixed          requireOne(string $path, array $data = [])                                                  Exiger le fichier donné une fois.
 * @method static string         sharedGet(string $path)                                                                     Obtenir le contenu d'un fichier avec accès partagé.
 * @method static int            size(string $path)                                                                          Obtenir la taille de fichier d'un fichier donné.
 * @method static string         type(string $path)                                                                          Obtenir le type de fichier d'un fichier donné.
 *
 * @see \BlitzPHP\Filesystem\Filesystem
 */
final class Fs extends Facade
{
    protected static function accessor(): object
    {
        return service('fs');
    }
}
