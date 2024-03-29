<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Traits;

use BlitzPHP\Container\Services;
use BlitzPHP\View\Adapters\NativeAdapter;

/**
 * GeneratorTrait contient une collection de méthodes
 * pour construire les commandes qui génèrent un fichier.
 */
trait GeneratorTrait
{
    /**
     * Nom du composant
     *
     * @var string
     */
    protected $component;

    /**
     * Répertoire de fichiers
     *
     * @var string
     */
    protected $directory;

    /**
     * Nom de la vue du template
     *
     * @var string
     */
    protected $template;

    /**
     * Chemin dans du dossier dans lequelle les vues de generation sont cherchees
     *
     * @var string
     */
    protected $templatePath = SYST_PATH . 'Cli/Commands/Generators/Views';

    /**
     * Clé de chaîne de langue pour les noms de classe requis.
     *
     * @var string
     */
    protected $classNameLang = '';

    /**
     * S'il faut exiger le nom de la classe.
     *
     * @internal
     *
     * @var bool
     */
    private $hasClassName = true;

    /**
     * S'il faut trier les importations de classe.
     *
     * @internal
     *
     * @var bool
     */
    private $sortImports = true;

    /**
     * Indique si l'option `--suffix` a un effet.
     *
     * @internal
     *
     * @var bool
     */
    private $enabledSuffixing = true;

    /**
     * Le tableau params pour un accès facile par d'autres méthodes.
     *
     * @internal
     *
     * @var array
     */
    private $params = [];

    /**
     * Exécute la commande.
     */
    protected function runGeneration(array $params): void
    {
        $this->params = $params;

        // Recupere le FQCN.
        $class = $this->qualifyClassName();

        // Recupere le chemin du fichier a partir du nom de la classe.
        $target = $this->buildPath($class);

        if (empty($target)) {
            return;
        }

        $this->generateFile($target, $this->buildContent($class));
    }

    /**
     * Handles writing the file to disk, and all of the safety checks around that.
     */
    private function generateFile(string $target, string $content): void
    {
        if ($this->option('namespace') === 'BlitzPHP') {
            // @codeCoverageIgnoreStart
            $this->colorize(lang('CLI.generator.usingBlitzNamespace'), 'yellow');

            if (! $this->confirm('Are you sure you want to continue?')) {
                $this->eol()->colorize(lang('CLI.generator.cancelOperation'), 'yellow');

                return;
            }

            $this->eol();
            // @codeCoverageIgnoreEnd
        }

        $isFile = is_file($target);

        // Écraser des fichiers sans le savoir est une gêne sérieuse, nous allons donc vérifier si nous dupliquons des choses,
        // si l'option "forcer" n'est pas fournie, nous renvoyons.
        if (! $this->option('force') && $isFile) {
            $this->io->error(lang('CLI.generator.fileExist', [clean_path($target)]), true);

            return;
        }

        // Vérifie si le répertoire pour enregistrer le fichier existe.
        $dir = dirname($target);

        if (! is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        helper('filesystem');

        // Construisez la classe en fonction des détails dont nous disposons.
        // Nous obtiendrons le contenu de notre fichier à partir du modèle,
        // puis nous effectuerons les remplacements nécessaires.
        if (! write_file($target, $content)) {
            // @codeCoverageIgnoreStart
            $this->io->error(lang('CLI.generator.fileError', [clean_path($target)]), true);

            return;
            // @codeCoverageIgnoreEnd
        }

        if ($this->option('force') && $isFile) {
            $this->colorize(lang('CLI.generator.fileOverwrite', [clean_path($target)]), 'yellow');

            return;
        }

        $this->colorize(lang('CLI.generator.fileCreate', [clean_path($target)]), 'green');
    }

    /**
     * Préparez les options et effectuez les remplacements nécessaires.
     */
    protected function prepare(string $class): string
    {
        return $this->parseTemplate($class);
    }

    /**
     * Changez le nom de base du fichier avant de l'enregistrer.
     *
     * Utile pour les composants dont le nom de fichier comporte une date.
     */
    protected function basename(string $filename): string
    {
        return basename($filename);
    }

    /**
     * Formatte le nom de base du fichier avant de l'enregistrer.
     *
     * Utile pour les composants dont le nom de fichier doit etre dans un format particulier.
     */
    protected function formatFilename(string $namespace, string $class): string
    {
        return str_replace('\\', DS, trim(str_replace($namespace . '\\', '', $class), '\\'));
    }

    /**
     * Analyse le nom de la classe et vérifie s'il est déjà qualifié.
     */
    protected function qualifyClassName(): string
    {
        // Obtient le nom de la classe à partir de l'entrée.
        $class = $this->params[0] ?? $this->params['name'] ?? null;

        if ($class === null && $this->hasClassName) {
            // @codeCoverageIgnoreStart
            $nameLang = $this->classNameLang ?: 'CLI.generator.className.default';
            $class    = $this->prompt(lang($nameLang));
            $this->eol();
            // @codeCoverageIgnoreEnd
        }

        helper('inflector');

        $component = singular($this->component);

        /**
         * @see https://regex101.com/r/a5KNCR/1
         */
        $pattern = sprintf('/([a-z][a-z0-9_\/\\\\]+)(%s)/i', $component);

        if (preg_match($pattern, $class, $matches) === 1) {
            $class = $matches[1] . ucfirst($matches[2]);
        }

        if ($this->enabledSuffixing && $this->getOption('suffix') && ! strripos($class, $component)) {
            $class .= ucfirst($component);
        }

        // Coupe l'entrée, normalise les séparateurs et s'assure que tous les chemins sont en Pascalcase.
        $class = ltrim(implode('\\', array_map('pascalize', explode('\\', str_replace('/', '\\', trim($class))))), '\\/');

        // Obtient l'espace de noms à partir de l'entrée. N'oubliez pas la barre oblique inverse finale !
        $namespace = trim(str_replace('/', '\\', $this->getOption('namespace', APP_NAMESPACE)), '\\') . '\\';

        if (strncmp($class, $namespace, strlen($namespace)) === 0) {
            return $class; // @codeCoverageIgnore
        }

        return $namespace . $this->directory . '\\' . str_replace('/', '\\', $class);
    }

    /**
     * Obtient la vue du générateur
     */
    protected function renderTemplate(array $data = []): string
    {
        $viewer = new NativeAdapter([], $this->templatePath, false);

        return $viewer->setData($data)->render($this->template);
    }

    /**
     * Exécute les pseudo-variables contenues dans le fichier de vue.
     */
    protected function parseTemplate(string $class, array $search = [], array $replace = [], array $data = []): string
    {
        // Récupère la partie de l'espace de noms à partir du nom de classe complet.
        $namespace = trim(implode('\\', array_slice(explode('\\', $class), 0, -1)), '\\');
        $search[]  = '<@php';
        $search[]  = '{namespace}';
        $search[]  = '{class}';
        $replace[] = '<?php';
        $replace[] = $namespace;
        $replace[] = str_replace($namespace . '\\', '', $class);

        return str_replace($search, $replace, $this->renderTemplate($data));
    }

    /**
     * Construit le contenu de la classe générée, effectue tous les remplacements nécessaires et
     * trie par ordre alphabétique les importations pour un modèle donné.
     */
    protected function buildContent(string $class): string
    {
        $template = $this->prepare($class);

        if ($this->sortImports && preg_match('/(?P<imports>(?:^use [^;]+;$\n?)+)/m', $template, $match)) {
            $imports = explode("\n", trim($match['imports']));
            sort($imports);

            return str_replace(trim($match['imports']), implode("\n", $imports), $template);
        }

        return $template;
    }

    /**
     * Construit le chemin du fichier à partir du nom de la classe.
     */
    protected function buildPath(string $class): string
    {
        $namespace = trim(str_replace('/', '\\', $this->option('namespace', APP_NAMESPACE)), '\\');

        $base = Services::autoloader()->getNamespace($namespace);

        if (! $base = reset($base)) {
            $this->io->error(lang('CLI.namespaceNotDefined', [$namespace]), true);

            return '';
        }

        $base = realpath($base) ?: $base;
        $file = $base . DS . $this->formatFilename($namespace, $class) . '.php';

        return implode(DS, array_slice(explode(DS, $file), 0, -1)) . DS . $this->basename($file);
    }

    /**
     * Permet aux générateurs enfants de modifier le drapeau interne `$hasClassName`.
     */
    protected function setHasClassName(bool $hasClassName): self
    {
        $this->hasClassName = $hasClassName;

        return $this;
    }

    /**
     * Permet aux générateurs enfants de modifier le drapeau interne `$sortImports`.
     */
    protected function setSortImports(bool $sortImports): self
    {
        $this->sortImports = $sortImports;

        return $this;
    }

    /**
     * Permet aux générateurs enfants de modifier le drapeau interne `$enabledSuffixing`.
     */
    protected function setEnabledSuffixing(bool $enabledSuffixing): self
    {
        $this->enabledSuffixing = $enabledSuffixing;

        return $this;
    }
}
