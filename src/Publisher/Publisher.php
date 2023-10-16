<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Publisher;

use BlitzPHP\Container\Services;
use BlitzPHP\Exceptions\PublisherException;
use BlitzPHP\Filesystem\Files\FileCollection;
use BlitzPHP\Http\Uri;
use RuntimeException;
use Throwable;

/**
 * Les éditeurs lisent les chemins d'accès aux fichiers à partir de diverses sources et copient les fichiers vers différentes destinations.
 * Cette classe sert à la fois de base pour les directives de publication individuelles et de mode de découverte pour lesdites instances.
 * Dans cette classe, un "fichier" est un chemin complet vers un fichier vérifié tandis qu'un "chemin" est relatif à sa source ou à sa destination et peut indiquer soit un fichier, soit un répertoire dont l'existence n'est pas confirmée.
 *
 * Les échecs de classe lancent l'exception PublisherException,
 * mais certaines méthodes sous-jacentes peuvent percoler différentes exceptions,
 * comme FileException, FileNotFoundException ou InvalidArgumentException.
 *
 * Les opérations d'écriture intercepteront toutes les erreurs dans le fichier spécifique
 * Propriété $errors pour minimiser l'impact des opérations par lots partielles.
 *
 * @credit <a href="http://codeigniter.com">CodeIgniter 4 - \CodeIgniter\Publisher\Publisher</a>
 */
class Publisher extends FileCollection
{
    /**
     * Tableau des éditeurs découverts.
     *
     * @var array<string, self[]|null>
     */
    private static array $discovered = [];

    /**
     * Répertoire à utiliser pour les méthodes nécessitant un stockage temporaire.
     * Créé à la volée selon les besoins.
     */
    private ?string $scratch = null;

    /**
     * Exceptions pour des fichiers spécifiques de la dernière opération d'écriture.
     *
     * @var array<string, Throwable>
     */
    private array $errors = [];

    /**
     * Liste des fichiers publiés traitant la dernière opération d'écriture.
     *
     * @var string[]
     */
    private array $published = [];

    /**
     * Liste des répertoires autorisés et leur regex de fichiers autorisés.
     * Les restrictions sont intentionnellement privées pour éviter qu'elles ne soient dépassées.
     *
     * @var array<string,string>
     */
    private array $restrictions;

    private ContentReplacer $replacer;

    /**
     * Chemin de base à utiliser pour la source.
     */
    protected string $source = ROOTPATH;

    /**
     * Chemin de base à utiliser pour la destination.
     */
    protected string $destination = WEBROOT;

    // --------------------------------------------------------------------
    // Méthodes d'assistance
    // --------------------------------------------------------------------

    /**
     * Découvre et renvoie tous les éditeurs dans le répertoire d'espace de noms spécifié.
     *
     * @return self[]
     */
    final public static function discover(string $directory = 'Publishers'): array
    {
        if (isset(self::$discovered[$directory])) {
            return self::$discovered[$directory];
        }

        self::$discovered[$directory] = [];

        $locator = Services::locator();

        if ([] === $files = $locator->listFiles($directory)) {
            return [];
        }

        // Boucle sur chaque fichier en vérifiant s'il s'agit d'un Publisher
        foreach (array_unique($files) as $file) {
            $className = $locator->findQualifiedNameFromPath($file);

            if ($className !== false && class_exists($className) && is_a($className, self::class, true)) {
                self::$discovered[$directory][] = Services::factory($className);
            }
        }

        sort(self::$discovered[$directory]);

        return self::$discovered[$directory];
    }

    /**
     * Supprime un répertoire et tous ses fichiers et sous-répertoires.
     */
    private static function wipeDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            // Essayez plusieurs fois en cas de mèches persistantes
            $attempts = 10;

            while ((bool) $attempts && ! delete_files($directory, true, false, true)) {
                // @codeCoverageIgnoreStart
                $attempts--;
                usleep(100000); // .1s
                // @codeCoverageIgnoreEnd
            }

            @rmdir($directory);
        }
    }

    /**
     * Charge l'assistant et vérifie les répertoires source et destination.
     */
    public function __construct(?string $source = null, ?string $destination = null)
    {
        helper('filesystem');

        $this->source      = self::resolveDirectory($source ?? $this->source);
        $this->destination = self::resolveDirectory($destination ?? $this->destination);

        $this->replacer = new ContentReplacer();

        // Les restrictions ne sont intentionnellement pas injectées pour empêcher le dépassement
        $this->restrictions = config('publisher.restrictions');

        // Assurez-vous que la destination est autorisée
        foreach (array_keys($this->restrictions) as $directory) {
            if (str_starts_with($this->destination, $directory)) {
                return;
            }
        }

        throw PublisherException::destinationNotAllowed($this->destination);
    }

    /**
     * Nettoie tous les fichiers temporaires dans l'espace de travail.
     */
    public function __destruct()
    {
        if (isset($this->scratch)) {
            self::wipeDirectory($this->scratch);

            $this->scratch = null;
        }
    }

    /**
     * Lit les fichiers à partir des sources et les copie vers leurs destinations.
     * Cette méthode devrait être réimplémentée par les classes filles destinées à la découverte.
     *
     * @throws RuntimeException
     */
    public function publish(): bool
    {
        // Protection contre une mauvaise utilisation accidentelle
        if ($this->source === ROOTPATH && $this->destination === WEBROOT) {
            throw new RuntimeException('Les classes enfants de Publisher doivent fournir leur propre méthode de publication ou une source et une destination.');
        }

        return $this->addPath('/')->merge(true);
    }

    // --------------------------------------------------------------------
    // Accesseurs de propriété
    // --------------------------------------------------------------------

    /**
     * Renvoie le répertoire source.
     */
    final public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Renvoie le répertoire de destination.
     */
    final public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * Renvoie l'espace de travail temporaire, en le créant si nécessaire.
     */
    final public function getScratch(): string
    {
        if ($this->scratch === null) {
            $this->scratch = rtrim(sys_get_temp_dir(), DS) . DS . bin2hex(random_bytes(6)) . DS;
            mkdir($this->scratch, 0o700);
            $this->scratch = realpath($this->scratch) ? realpath($this->scratch) . DS
                : $this->scratch;
        }

        return $this->scratch;
    }

    /**
     * Renvoie les erreurs de la dernière opération d'écriture, le cas échéant.
     *
     * @return array<string,Throwable>
     */
    final public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Renvoie les fichiers publiés par la dernière opération d'écriture.
     *
     * @return string[]
     */
    final public function getPublished(): array
    {
        return $this->published;
    }

    // --------------------------------------------------------------------
    // Gestionnaires supplémentaires
    // --------------------------------------------------------------------

    /**
     * Vérifie et ajoute des chemins à la liste.
     *
     * @param string[] $paths
     */
    final public function addPaths(array $paths, bool $recursive = true): self
    {
        foreach ($paths as $path) {
            $this->addPath($path, $recursive);
        }

        return $this;
    }

    /**
     * Ajoute un chemin unique à la liste de fichiers.
     */
    final public function addPath(string $path, bool $recursive = true): self
    {
        $this->add($this->source . $path, $recursive);

        return $this;
    }

    /**
     * Télécharge et met en scène des fichiers à partir d'un tableau d'URI.
     *
     * @param string[] $uris
     */
    final public function addUris(array $uris): self
    {
        foreach ($uris as $uri) {
            $this->addUri($uri);
        }

        return $this;
    }

    /**
     * Télécharge un fichier à partir de l'URI et l'ajoute à la liste des fichiers.
     *
     * @param string $uri Parce que HTTP\URI est stringable, il sera toujours accepté
     */
    final public function addUri(string $uri): self
    {
        // Trouvez un bon nom de fichier (en utilisant des requêtes et des fragments de bandes d'URI)
        $file = $this->getScratch() . basename((new Uri($uri))->getPath());

        // Obtenez le contenu et écrivez-le dans l'espace de travail
        write_file($file, service('httpclient')->get($uri)->body());

        return $this->addFile($file);
    }

    // --------------------------------------------------------------------
    // Méthodes d'écriture
    // --------------------------------------------------------------------

    /**
     * Supprime la destination et tous ses fichiers et dossiers.
     */
    final public function wipe(): self
    {
        self::wipeDirectory($this->destination);

        return $this;
    }

    /**
     * Copie tous les fichiers dans la destination, ne crée pas de structure de répertoire.
     *
     * @param bool $replace S'il faut écraser les fichiers existants.
     *
     * @return bool Si tous les fichiers ont été copiés avec succès
     */
    final public function copy(bool $replace = true): bool
    {
        $this->errors = $this->published = [];

        foreach ($this->get() as $file) {
            $to = $this->destination . basename($file);

            try {
                $this->safeCopyFile($file, $to, $replace);
                $this->published[] = $to;
            } catch (Throwable $e) {
                $this->errors[$file] = $e;
            }
        }

        return $this->errors === [];
    }

    /**
     * Fusionne tous les fichiers dans la destination.
     * Crée une structure de répertoires en miroir uniquement pour les fichiers de la source.
     *
     * @param bool $replace Indique s'il faut écraser les fichiers existants.
     *
     * @return bool Si tous les fichiers ont été copiés avec succès
     */
    final public function merge(bool $replace = true): bool
    {
        $this->errors = $this->published = [];

        // Obtenez les fichiers de la source pour un traitement spécial
        $sourced = self::filterFiles($this->get(), $this->source);

        // Obtenez les fichiers de la source pour un traitement spécial
        $this->files = array_diff($this->files, $sourced);
        $this->copy($replace);

        // Copiez chaque fichier source vers sa destination relative
        foreach ($sourced as $file) {
            // Résoudre le chemin de destination
            $to = $this->destination . substr($file, strlen($this->source));

            try {
                $this->safeCopyFile($file, $to, $replace);
                $this->published[] = $to;
            } catch (Throwable $e) {
                $this->errors[$file] = $e;
            }
        }

        return $this->errors === [];
    }

    /**
     * Remplacer le contenu
     *
     * @param array $replaces [search => replace]
     */
    public function replace(string $file, array $replaces): bool
    {
        $this->verifyAllowed($file, $file);

        $content = file_get_contents($file);

        $newContent = $this->replacer->replace($content, $replaces);

        $return = file_put_contents($file, $newContent);

        return $return !== false;
    }

    /**
     * Ajouter une ligne après la ligne avec la chaîne
     *
     * @param string $after Chaîne à rechercher.
     */
    public function addLineAfter(string $file, string $line, string $after): bool
    {
        $this->verifyAllowed($file, $file);

        $content = file_get_contents($file);

        $result = $this->replacer->addAfter($content, $line, $after);

        if ($result !== null) {
            $return = file_put_contents($file, $result);

            return $return !== false;
        }

        return false;
    }

    /**
     * Ajouter une ligne avant la ligne avec la chaîne
     *
     * @param string $before String à rechercher.
     */
    public function addLineBefore(string $file, string $line, string $before): bool
    {
        $this->verifyAllowed($file, $file);

        $content = file_get_contents($file);

        $result = $this->replacer->addBefore($content, $line, $before);

        if ($result !== null) {
            $return = file_put_contents($file, $result);

            return $return !== false;
        }

        return false;
    }

    /**
     * Vérifiez qu'il s'agit d'un fichier autorisé pour sa destination
     */
    private function verifyAllowed(string $from, string $to)
    {
        // Vérifiez qu'il s'agit d'un fichier autorisé pour sa destination
        foreach ($this->restrictions as $directory => $pattern) {
            if (str_starts_with($to, $directory) && self::matchFiles([$to], $pattern) === []) {
                throw PublisherException::fileNotAllowed($from, $directory, $pattern);
            }
        }
    }

    /**
     * Copie un fichier avec création de répertoire et reconnaissance de fichier identique.
     * Permet intentionnellement des erreurs.
     *
     * @throws PublisherException Pour les collisions et les violations de restriction
     */
    private function safeCopyFile(string $from, string $to, bool $replace): void
    {
        // Vérifiez qu'il s'agit d'un fichier autorisé pour sa destination
        $this->verifyAllowed($from, $to);

        // Rechercher un fichier existant
        if (file_exists($to)) {
            // S'il n'est pas remplacé ou si les fichiers sont identiques, envisagez de réussir
            if (! $replace || same_file($from, $to)) {
                return;
            }

            // S'il s'agit d'un répertoire, n'essayez pas de le supprimer
            if (is_dir($to)) {
                throw PublisherException::collision($from, $to);
            }

            // Essayez de supprimer autre chose
            unlink($to);
        }

        // Assurez-vous que le répertoire existe
        if (! is_dir($directory = pathinfo($to, PATHINFO_DIRNAME))) {
            mkdir($directory, 0o775, true);
        }

        // Autoriser copy() à générer des erreurs
        copy($from, $to);
    }
}
