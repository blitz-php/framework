<?php

namespace BlitzPHP\Loader;

use BlitzPHP\Exceptions\LoadException;
use BlitzPHP\Traits\Macroable;
use ErrorException;
use FilesystemIterator;
use Symfony\Component\Finder\Finder;

/**
 * Filesystem
 */
class Filesystem
{
    use Macroable;

    /**
     * Déterminez si un fichier ou un répertoire existe.
     */
    public static function exists(string $path) : bool
    {
        return file_exists($path);
    }

    /**
     * Obtenir le contenu d'un fichier.
     */
    public static function get(string $path, bool $lock = false) : string
    {
        if (self::isFile($path)) {
            return $lock ? self::sharedGet($path) : file_get_contents($path);
        }

        throw LoadException::fileNotFound($path);
    }

    /**
     * Obtenir le contenu d'un fichier avec accès partagé.
     */
    public static function sharedGet(string $path) : string
    {
        $contents = '';

        $handle = fopen($path, 'rb');

        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $path);

                    $contents = fread($handle, self::size($path) ?: 1);

                    flock($handle, LOCK_UN);
                }
            }
            finally {
                fclose($handle);
            }
        }

        return $contents;
    }

    /**
     * Obtenir la valeur renvoyée d'un fichier.
     * 
     * @return mixed
     */
    public static function getRequire(string $path)
    {
        if (self::isFile($path))  {
            return require $path;
        }

        throw LoadException::fileNotFound($path);
    }

    /**
     * Exiger le fichier donné une fois.
     *
     * @return mixed
     */
    public static function requireOnce(string $file)
    {
        require_once $file;
    }

    /**
     * Obtenir le hachage MD5 du fichier au chemin donné.
     */
    public static function hash(string $path): string
    {
        return md5_file($path);
    }

    /**
     * Écrire le contenu d'un fichier.
     * 
     * @return int|false
     */
    public static function put(string $path, string $contents, bool $lock = false)
    {
        return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
    }

    /**
     * Écrire le contenu d'un fichier, en le remplaçant de manière atomique s'il existe déjà.
     *
     * @return void
     */
    public static function replace(string $path, string $content)
    {
        // If the path already exists and is a symlink, get the real path...
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;

        $tempPath = tempnam(dirname($path), basename($path));

        // Fix permissions of tempPath because `tempnam()` creates it with permissions set to 0600...
        chmod($tempPath, 0777 - umask());

        file_put_contents($tempPath, $content);

        rename($tempPath, $path);
    }

    /**
     * Prepend to a file.
     */
    public static function prepend(string $path, string $data): int
    {
        if (self::exists($path))  {
            return self::put($path, $data.self::get($path));
        }

        return self::put($path, $data);
    }

    /**
     * Append to a file.
     */
    public static function append(string $path, string $data): int
    {
        return file_put_contents($path, $data, FILE_APPEND);
    }

    /**
     * Get or set UNIX mode of a file or directory.
     * 
     * @return mixed
     */
    public static function chmod(string $path, ?int $mode = null)
    {
        if ($mode) {
            return chmod($path, $mode);
        }

        return substr(sprintf('%o', fileperms($path)), -4);
    }

    /**
     * Delete the file at a given path.
     *
     * @param  string|array  $paths
     */
    public static function delete($paths): bool
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            try {
                if (! @unlink($path)) {
                    $success = false;
                }
            }
            catch (ErrorException $e) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Move a file to a new location.
     */
    public static function move(string $path, string $target, bool $overwrite = true): bool
    {
        if (! is_file($target) || ($overwrite && is_file($target))) {
            return rename($path, $target);
        }
        
        return false;
        
    }

    /**
     * Copy a file to a new location.
     */
    public static function copy(string $path, string $target, bool $overwrite = true): bool
    {
        if (! is_file($target) || ($overwrite && is_file($target))) {
            return copy($path, $target);
        }
        
        return false;
    }

    /**
     * Create a hard link to the target file or directory.
     * 
     * @return bool|void
     */
    public static function link(string $target, string $link)
    {
        if (! is_windows()) {
            return symlink($target, $link);
        }

        $mode = self::isDirectory($target) ? 'J' : 'H';

        exec("mklink /{$mode} ".escapeshellarg($link).' '.escapeshellarg($target));
    }

    /**
     * Extract the file name from a file path.
     * 
     */
    public static function name(string $path): string
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Extract the trailing name component from a file path.
     */
    public static function basename(string $path): string
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Extract the parent directory from a file path.
     */
    public static function dirname(string $path): string
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * Extract the file extension from a file path.
     */
    public static function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Get the file type of a given file.
     */
    public static function type(string $path): string
    {
        return filetype($path);
    }

    /**
     * Get the mime-type of a given file.
     *
     * @return string|false
     */
    public static function mimeType(string $path)
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }

    /**
     * Get the file size of a given file.
     * 
     */
    public static function size(string $path): int
    {
        return filesize($path);
    }

    /**
     * Get the file's last modification time.
     */
    public static function lastModified(string $path): int
    {
        return filemtime($path);
    }

    /**
     * Determine if the given path is a directory.
     */
    public static function isDirectory(string $directory): bool
    {
        return is_dir($directory);
    }

    /**
     * Determine if the given path is readable.
     */
    public static function isReadable(string $path): bool
    {
        return is_readable($path);
    }

    /**
     * Determine if the given path is writable.
     */
    public static function isWritable(string $path): bool
    {
        return is_writable($path);
    }

    /**
     * Determine if the given path is a file.
     */
    public static function isFile(string $file): bool
    {
        return is_file($file);
    }

    /**
     * Find path names matching a given pattern.
     */
    public static function glob(string $pattern, int $flags = 0): array
    {
        return glob($pattern, $flags);
    }

    /**
     * Get an array of all files in a directory.
     * 
     * @return \Symfony\Component\Finder\SplFileInfo[]
     */
    public static function files(string $directory, bool $hidden = false, string $sortBy = 'name'): array
    {
		$files = Finder::create()->files()->ignoreDotFiles(! $hidden)->in($directory)->depth(0);

		switch (strtolower($sortBy)) {
			case 'type':
				$files = $files->sortByType();
				break;
			case 'modifiedtime':
			case 'modified':
				$files = $files->sortByModifiedTime();
				break;
			case 'changedtime':
			case 'changed':
				$files = $files->sortByChangedTime();
				break;
			case 'accessedtime':
			case 'accessed':
				$files = $files->sortByAccessedTime();
				break;
			default:
				$files = $files->sortByName();
				break;
		}

		return iterator_to_array($files, false);
    }

    /**
     * Get all of the files from the given directory (recursive).
     *
     * @return \Symfony\Component\Finder\SplFileInfo[]
     */
    public static function allFiles(string $directory, bool $hidden = false, string $sortBy = 'name'): array
    {
		$files = Finder::create()->files()->ignoreDotFiles(! $hidden)->in($directory);

		switch (strtolower($sortBy)) {
			case 'type':
				$files = $files->sortByType();
				break;
			case 'modifiedtime':
			case 'modified':
				$files = $files->sortByModifiedTime();
				break;
			case 'changedtime':
			case 'changed':
				$files = $files->sortByChangedTime();
				break;
			case 'accessedtime':
			case 'accessed':
				$files = $files->sortByAccessedTime();
				break;
			default:
				$files = $files->sortByName();
				break;
		}

        return iterator_to_array($files, false);
    }

    /**
     * Get all of the directories within a given directory.
     */
    public static function directories(string $directory, int $depth = 0, bool $hidden = false): array
    {
        $directories = [];

        foreach (Finder::create()->ignoreDotFiles(! $hidden)->in($directory)->directories()->depth($depth)->sortByName() as $dir) {
            $directories[] = $dir->getPathname();
        }

        return $directories;
    }

    /**
     * Create a directory.
     */
    public static function makeDirectory(string $path, int $mode = 0755, bool $recursive = false, bool $force = false): bool
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }

        return mkdir($path, $mode, $recursive);
    }

    /**
     * Move a directory.
     */
    public static function moveDirectory(string $from, string $to, bool $overwrite = false): bool
    {
        if ($overwrite && self::isDirectory($to) && ! self::deleteDirectory($to)) {
            return false;
        }

        return @rename($from, $to) === true;
    }

    /**
     * Copy a directory from one location to another.
     */
    public static function copyDirectory(string $directory, string $destination, bool $overwrite = true, ?int $options = null): bool
    {
        if (!self::isDirectory($directory)) {
            return false;
        }

        $options = $options ?: FilesystemIterator::SKIP_DOTS;

        // If the destination directory does not actually exist, we will go ahead and
        // create it recursively, which just gets the destination prepared to copy
        // the files over. Once we make the directory we'll proceed the copying.
        if (!self::isDirectory($destination)) {
            self::makeDirectory($destination, 0777, true);
        }

        $items = new FilesystemIterator($directory, $options);

        foreach ($items As $item) {
            // As we spin through items, we will check to see if the current file is actually
            // a directory or a file. When it is actually a directory we will need to call
            // back into this function recursively to keep copying these nested folders.
            $target = $destination.'/'.$item->getBasename();

            if ($item->isDir()) {
                $path = $item->getPathname();

                if (! self::copyDirectory($path, $target, $overwrite, $options)) {
                    return false;
                }
            }

            // If the current items is just a regular file, we will just copy this to the new
            // location and keep looping. If for some reason the copy fails we'll bail out
            // and return false, so the developer is aware that the copy process failed.
            else {
                if (! self::copy($item->getPathname(), $target, $overwrite)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Recursively delete a directory.
     *
     * The directory itself may be optionally preserved.
     */
    public static function deleteDirectory(string $directory, bool $preserve = false): bool
    {
        if (! self::isDirectory($directory)) {
            return false;
        }

        $items = new FilesystemIterator($directory);

        foreach ($items as $item) {
            // If the item is a directory, we can just recurse into the function and
            // delete that sub-directory otherwise we'll just delete the file and
            // keep iterating through each file until the directory is cleaned.
            if ($item->isDir() && ! $item->isLink()) {
                self::deleteDirectory($item->getPathname());
            }

            // If the item is just a file, we can go ahead and delete it since we're
            // just looping through and waxing all of the files in this directory
            // and calling directories recursively, so we delete the real path.
            else {
                self::delete($item->getPathname());
            }
        }

        if (! $preserve) {
            @rmdir($directory);
        }

        return true;
    }

    /**
     * Remove all of the directories within a given directory.
     */
    public static function deleteDirectories(string $directory): bool
    {
        $allDirectories = self::directories($directory);

        if (! empty($allDirectories)) {
            foreach ($allDirectories As $directoryName) {
                self::deleteDirectory($directoryName);
            }

            return true;
        }

        return false;
    }

    /**
     * Empty the specified directory of all files and folders.
     */
    public static function cleanDirectory(string $directory): bool
    {
        return self::deleteDirectory($directory, true);
    }
}
