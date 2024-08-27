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
     * Namespace a utiliser pour la classe.
     * Laisser a null pour utiliser le namespace par defaut.
     */
    protected ?string $namespace = null;

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
     * @var array<int|string, string|null>
     */
    private $params = [];

    /**
     * Exécute la generation.
	 *
     * @param array<int|string, string|null> $params
	 *
	 * @deprecated use generateClass() instead
     */
    protected function runGeneration(array $params): void
    {
        $this->generateClass($params);
    }

    /**
     * Génère un fichier de classe à partir d'un template existant.
     *
     * @param array<int|string, string|null> $params
     *
     * @return string|null Nom de la classe
     */
    protected function generateClass(array $params): ?string
    {
        $this->params = $params;

        // Récupère le nom complet de la classe à partir de l'entrée.
        $class = $this->qualifyClassName();

        // Obtenir le chemin d'accès au fichier à partir du nom de la classe.
        $target = $this->buildPath($class);

        // Vérifier si le chemin est vide.
        if ($target === '') {
            return null;
        }

        $this->generateFile($target, $this->buildContent($class));

        return $class;
    }

    /**
     * Générer un fichier de vue à partir d'un template existant.
     *
     * @param string                         $view   nom de la vue à espace de noms qui est générée
     * @param array<int|string, string|null> $params
     */
    protected function generateView(string $view, array $params): void
    {
        $this->params = $params;

        $target = $this->buildPath($view);

        // Vérifier si le chemin est vide.
        if ($target === '') {
            return;
        }

        $this->generateFile($target, $this->buildContent($view));
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
     *
     * @param string $class nom de classe avec namespace ou vue avec namespace.
     *
     * @return string contenu du fichier généré
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
        $class = $this->normalizeInputClassName();

        // Récupère l'espace de noms à partir de l'entrée. N'oubliez pas le backslash finale !
        $namespace = $this->getNamespace() . '\\';

        if (str_starts_with($class, $namespace)) {
            return $class; // @codeCoverageIgnore
        }

        $directory = ($this->directory !== null) ? $this->directory . '\\' : '';

        return $namespace . $directory . str_replace('/', '\\', $class);
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
	 *
	 * @param string $class nom de classe avec namespace ou vue avec namespace.
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

		// Vérifier que le namespace est réellement défini et que nous ne sommes pas en train de taper du charabia.
        $base = service('autoloader')->getNamespace($namespace);

        if (! $base = reset($base)) {
            $this->io->error(lang('CLI.namespaceNotDefined', [$namespace]), true);

            return '';
        }

        $base = realpath($base) ?: $base;
        $file = $base . DS . $this->formatFilename($namespace, $class) . '.php';

        return implode(DS, array_slice(explode(DS, $file), 0, -1)) . DS . $this->basename($file);
    }

    /**
     * Recupere le namespace a partir de l'option du cli ou le namespace par defaut si l'option n'est pas defini.
     * Peut etre directement modifier directement par la propriete $this->namespace.
     */
    protected function getNamespace(): string
    {
        return $this->namespace ?? trim(str_replace('/', '\\', $this->option('namespace') ?? APP_NAMESPACE), '\\');
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

    /**
     * Normaliser le nom de classe entrée.
     */
    private function normalizeInputClassName(): string
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
        $pattern = sprintf('/([a-z][a-z0-9_\/\\\\]+)(%s)$/i', $component);

        if (preg_match($pattern, $class, $matches) === 1) {
            $class = $matches[1] . ucfirst($matches[2]);
        }

		$suffix = $this->option('suffix') ?? array_key_exists('suffix', $this->params);

        if ($this->enabledSuffixing && $suffix && preg_match($pattern, $class) !== 1) {
            $class .= ucfirst($component);
        }

        // Coupe l'entrée, normalise les séparateurs et s'assure que tous les chemins sont en Pascalcase.
        return ltrim(implode('\\', array_map(pascalize(...), explode('\\', str_replace('/', '\\', trim($class))))), '\\/');
    }
}
