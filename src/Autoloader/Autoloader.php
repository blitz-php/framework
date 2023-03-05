<?php

namespace BlitzPHP\Autoloader;

use BlitzPHP\Config\Config;
use BlitzPHP\Exceptions\ConfigException;
use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use InvalidArgumentException;
use RuntimeException;

/**
 * Un autoloader utilisant l'autoloading PSR4 autoloading et les classmaps traditionels.
 *
 * @credit 		<a href="https://codeigniter.com">CodeIgniter4 - CodeIgniter\Autoloader\Autoloader</a>
 */
class Autoloader
{
    /**
     * Sauvegarde les namespaces comme cle et les chemins correspondants comme valeurs.
     *
     * @var array<string, array<string>>
     */
    protected array $prefixes = [];

    /**
     * Sauvegarde les noms de classes comme cle et les chemins correspondants comme valeurs.
     *
     * @var array<string, string>
     */
    protected array $classmap = [];

    /**
     * Sauvegarde la liste des fichiers.
     *
     * @var string[]
     * @phpstan-var list<string>
     */
    protected array $files = [];

    /**
     * Sauvegarde la liste des helpers.
     * Toujours charger le helper URL car il est utilisee par plusieurs applications.
     *
     * @var string[]
     * @phpstan-var list<string>
     */
    protected array $helpers = ['url'];

    /**
     * Lit dans le tableau de configuration et garde les parties valides dont on a besoin.
     */
    public function initialize(): self
    {
        $this->prefixes = [];
        $this->classmap = [];
        $this->files    = [];

        $config = (object) Config::get('autoload');

        // Nous devons avoir au moins un, au cas contraire, 
        // on leve une exception pour forcer le programmeur a renseigner.
        if ($config->psr4 === [] && $config->classmap === []) {
            throw new InvalidArgumentException('Config array must contain either the \'psr4\' key or the \'classmap\' key.');
        }

        if ($config->psr4 !== []) {
            // $this->addNamespace($config->psr4);
        }

        if ($config->classmap !== []) {
            $this->classmap = $config->classmap;
        }

        if ($config->files !== []) {
            $this->files = $config->files;
        }

        if (isset($config->helpers)) { // @phpstan-ignore-line
            $this->helpers = [...$this->helpers, ...$config->helpers];
        }

        if (is_file(COMPOSER_PATH)) {
            $this->loadComposerInfo();
        }

        return $this;
    }

    private function loadComposerInfo(): void
    {
        /**
         * @var ClassLoader $composer
         */
        $composer = include COMPOSER_PATH;

        $this->loadComposerClassmap($composer);

        // @phpstan-ignore-next-line
        $this->loadComposerNamespaces($composer);
    
        unset($composer);
    }

    /**
     * Enregistre le chargeur avec la pile SPL autoloader.
     */
    public function register()
    {
        // Ajoute l'autoloader PSR4 pour un maximum de performance.
        spl_autoload_register([$this, 'loadClass'], true, true);

        // Maintenant ajoute un autre autoloader pour les fichier de notre class map.
        spl_autoload_register([$this, 'loadClassmap'], true, true);

        // Charge les fichiers non-class
        foreach ($this->files as $file) {
            $this->includeFile($file);
        }
    }

    /**
     * Enregistre les namespaces avec l'autoloader.
     *
     * @param array<string, array<int, string>|string>|string $namespace
     * @phpstan-param array<string, list<string>|string>|string $namespa
     */
    public function addNamespace($namespace, ?string $path = null): self
    {
        if (is_array($namespace)) {
            foreach ($namespace as $prefix => $namespacedPath) {
                $prefix = trim($prefix, '\\');

                if (is_array($namespacedPath)) {
                    foreach ($namespacedPath as $dir) {
                        $this->prefixes[$prefix][] = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR;
                    }

                    continue;
                }

                $this->prefixes[$prefix][] = rtrim($namespacedPath, '\\/') . DIRECTORY_SEPARATOR;
            }
        } else {
            $this->prefixes[trim($namespace, '\\')][] = rtrim($path, '\\/') . DIRECTORY_SEPARATOR;
        }
        
        return $this;
    }

    /**
     * Recupere les namespaces avec les prefixes en cles et les chemins en valeurs.
     *
     * Si le parametre prefix et defini, returne seulement le chemin correspondant au prefixe donnee.
     */
    public function getNamespace(?string $prefix = null): array
    {
        if ($prefix === null) {
            return $this->prefixes;
        }

        return $this->prefixes[trim($prefix, '\\')] ?? [];
    }

    /**
     * Retire un seul namespace des configurations psr4.
     */
    public function removeNamespace(string $namespace): self
    {
        if (isset($this->prefixes[trim($namespace, '\\')])) {
            unset($this->prefixes[trim($namespace, '\\')]);
        }

        return $this;
    }

    /**
     * Charge une classe en utilisant le classmap disponible.
     *
     * @return false|string
     */
    public function loadClassmap(string $class)
    {
        $file = $this->classmap[$class] ?? '';

        if (is_string($file) && $file !== '') {
            return $this->includeFile($file);
        }

        return false;
    }

    /**
     * Charge un fichier a partir du non de classe fourni.
     *
     * @param string $class Le nom complet (FQCN) de la calsse.
     *
     * @return false|string Le fichier correspondant en cas de succes, ou false en cas d'echec.
     */
    public function loadClass(string $class)
    {
        $class = trim($class, '\\');
        $class = str_ireplace('.php', '', $class);

        return $this->loadInNamespace($class);
    }

    /**
     * Charge un fichier a partir du non de classe fourni.
     *
     * @param string $class Le nom complet (FQCN) de la calsse.
     *
     * @return false|string Le fichier correspondant en cas de succes, ou false en cas d'echec.
     */
    protected function loadInNamespace(string $class)
    {
        if (strpos($class, '\\') === false) {
            return false;
        }

        foreach ($this->prefixes as $namespace => $directories) {
            foreach ($directories as $directory) {
                $directory = rtrim($directory, '\\/');

                if (strpos($class, $namespace) === 0) {
                    $filePath = $directory . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($namespace))) . '.php';
                    $filename = $this->includeFile($filePath);

                    if ($filename) {
                        return $filename;
                    }
                }
            }
        }

        // Aucun fichier trouve
        return false;
    }

    /**
     * La zone centrale pour inclure un fichier.
     *
     * @return false|string Le filename en cas de succes, false si le fichier n'est pas charger
     */
    protected function includeFile(string $file)
    {
        $file = $this->sanitizeFilename($file);

        if (is_file($file)) {
            include_once $file;

            return $file;
        }

        return false;
    }

    /**
     * Check file path.
     *
     * Checks special characters that are illegal in filenames on certain
     * operating systems and special characters requiring special escaping
     * to manipulate at the command line. Replaces spaces and consecutive
     * dashes with a single dash. Trim period, dash and underscore from beginning
     * and end of filename.
     */
    public function sanitizeFilename(string $filename): string
    {
        // Only allow characters deemed safe for POSIX portable filenames.
        // Plus the forward slash for directory separators since this might be a path.
        // http://pubs.opengroup.org/onlinepubs/9699919799/basedefs/V1_chap03.html#tag_03_278
        // Modified to allow backslash and colons for on Windows machines.
        $result = preg_match_all('/[^0-9\p{L}\s\/\-_.:\\\\]/u', $filename, $matches);

        if ($result > 0) {
            $chars = implode('', $matches[0]);

            throw new InvalidArgumentException(
                'The file path contains special characters "' . $chars
                . '" that are not allowed: "' . $filename . '"'
            );
        }
        if ($result === false) {
            if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
                $message = preg_last_error_msg();
            } else {
                $message = 'Regex error. error code: ' . preg_last_error();
            }

            throw new RuntimeException($message . '. filename: "' . $filename . '"');
        }

        $cleanFilename = trim($filename, '.-_');

        if ($filename !== $cleanFilename) {
            throw new InvalidArgumentException('The characters ".-_" are not allowed in filename edges: "' . $filename . '"');
        }

        return $cleanFilename;
    }

    private function loadComposerNamespaces(ClassLoader $composer): void
    {
        $namespacePaths = $composer->getPrefixesPsr4();
        
        if (! method_exists(InstalledVersions::class, 'getAllRawData')) {
            throw new RuntimeException(
                'Your Composer version is too old.'
                . ' Please update Composer (run `composer self-update`) to v2.0.14 or later'
                . ' and remove your vendor/ directory, and run `composer update`.'
            );
        }
        $allData     = InstalledVersions::getAllRawData();
        $packageList = [];

        foreach ($allData as $list) {
            $packageList = array_merge($packageList, $list['versions']);
        }

        $only    = [];
        $exclude = [];
        if ($only !== [] && $exclude !== []) {
            throw new ConfigException('Cannot use "only" and "exclude" at the same time in "Config\Modules::$composerPackages".');
        }

        // Recupere les chemins d'installation des packages pour ajouter les namespace pour la decouverte auto.
        $installPaths = [];
        if ($only !== []) {
            foreach ($packageList as $packageName => $data) {
                if (in_array($packageName, $only, true) && isset($data['install_path'])) {
                    $installPaths[] = $data['install_path'];
                }
            }
        } else {
            foreach ($packageList as $packageName => $data) {
                if (! in_array($packageName, $exclude, true) && isset($data['install_path'])) {
                    $installPaths[] = $data['install_path'];
                }
            }
        }

        $newPaths = [];

        foreach ($namespacePaths as $namespace => $srcPaths) {
            $add = false;

            foreach ($srcPaths as $path) {
                foreach ($installPaths as $installPath) {
                    if ($installPath === substr($path, 0, strlen($installPath))) {
                        $add = true;
                        break 2;
                    }
                }
            }

            if ($add) {
                // Composer garde les namespaces avec les trailing slash. On en a pas besoin.
                $newPaths[rtrim($namespace, '\\ ')] = $srcPaths;
            }
        }
       
        $this->addNamespace($newPaths);
    }

    private function loadComposerClassmap(ClassLoader $composer): void
    {
        $classes = $composer->getClassMap();

        $this->classmap = array_merge($this->classmap, $classes);
    }

    /**
     * Charge les helpers
     */
    public function loadHelpers(): void
    {
        helper($this->helpers);
    }
}
