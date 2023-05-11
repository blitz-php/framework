<?php 

namespace BlitzPHP\Facades;

use BlitzPHP\Loader\Services;

/**
 * @method static \BlitzPHP\Filesystem\FilesystemInterface drive(string|null $name = null)
 * @method static \BlitzPHP\Filesystem\FilesystemInterface disk(string|null $name = null)
 * @method static \BlitzPHP\Filesystem\FilesystemInterface cloud()
 * @method static \BlitzPHP\Filesystem\FilesystemInterface build(string|array $config)
 * @method static \BlitzPHP\Filesystem\FilesystemInterface createLocalDriver(array $config)
 * @method static \BlitzPHP\Filesystem\FilesystemInterface createFtpDriver(array $config)
 * @method static \BlitzPHP\Filesystem\FilesystemInterface createSftpDriver(array $config)
 * @method static \BlitzPHP\Filesystem\FilesystemInterface createS3Driver(array $config)
 * @method static \BlitzPHP\Filesystem\FilesystemInterface createScopedDriver(array $config)
 * @method static \BlitzPHP\Filesystem\FilesystemManager set(string $name, mixed $disk)
 * @method static string getDefaultDriver()
 * @method static string getDefaultCloudDriver()
 * @method static \BlitzPHP\Filesystem\FilesystemManager forgetDisk(array|string $disk)
 * @method static void purge(string|null $name = null)
 * @method static \BlitzPHP\Filesystem\FilesystemManager extend(string $driver, \Closure $callback)
 * @method static \BlitzPHP\Filesystem\FilesystemManager setApplication(\Illuminate\Contracts\Foundation\Application $app)
 * @method static bool exists(string $path)
 * @method static string|null get(string $path)
 * @method static resource|null readStream(string $path)
 * @method static bool put(string $path, string|resource $contents, mixed $options = [])
 * @method static bool writeStream(string $path, resource $resource, array $options = [])
 * @method static string getVisibility(string $path)
 * @method static bool setVisibility(string $path, string $visibility)
 * @method static bool prepend(string $path, string $data)
 * @method static bool append(string $path, string $data)
 * @method static bool delete(string|array $paths)
 * @method static bool copy(string $from, string $to)
 * @method static bool move(string $from, string $to)
 * @method static int size(string $path)
 * @method static int lastModified(string $path)
 * @method static array files(string|null $directory = null, bool $recursive = false)
 * @method static array allFiles(string|null $directory = null)
 * @method static array directories(string|null $directory = null, bool $recursive = false)
 * @method static array allDirectories(string|null $directory = null)
 * @method static bool makeDirectory(string $path)
 * @method static bool deleteDirectory(string $directory)
 * @method static bool missing(string $path)
 * @method static bool fileExists(string $path)
 * @method static bool fileMissing(string $path)
 * @method static bool directoryExists(string $path)
 * @method static bool directoryMissing(string $path)
 * @method static string path(string $path)
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse response(string $path, string|null $name = null, array $headers = [], string|null $disposition = 'inline')
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse download(string $path, string|null $name = null, array $headers = [])
 * @method static string|false putFile(string $path, \Illuminate\Http\File|\Illuminate\Http\UploadedFile|string $file, mixed $options = [])
 * @method static string|false putFileAs(string $path, \Illuminate\Http\File|\Illuminate\Http\UploadedFile|string $file, string $name, mixed $options = [])
 * @method static string|false checksum(string $path, array $options = [])
 * @method static string|false mimeType(string $path)
 * @method static string url(string $path)
 * @method static bool providesTemporaryUrls()
 * @method static string temporaryUrl(string $path, \DateTimeInterface $expiration, array $options = [])
 * @method static \League\Flysystem\FilesystemOperator getDriver()
 * @method static \League\Flysystem\FilesystemAdapter getAdapter()
 * @method static array getConfig()
 * @method static void buildTemporaryUrlsUsing(\Closure $callback)
 * @method static \BlitzPHP\Filesystem\FilesystemAdapter|mixed when(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \BlitzPHP\Filesystem\FilesystemAdapter|mixed unless(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * @method static mixed macroCall(string $method, array $parameters)
 * @method static bool has(string $location)
 * @method static string read(string $location)
 * @method static \League\Flysystem\DirectoryListing listContents(string $location, bool $deep = false)
 * @method static int fileSize(string $path)
 * @method static string visibility(string $path)
 * @method static void write(string $location, string $contents, array $config = [])
 * @method static void createDirectory(string $location, array $config = [])
 *
 * @see \BlitzPHP\Filesystem\FilesystemManager
 */
final class Storage extends Facade
{
    protected static function accessor(): object
    {
        return Services::storage();
    }
}