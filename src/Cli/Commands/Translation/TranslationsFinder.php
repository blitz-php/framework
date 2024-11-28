<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Translation;

use Ahc\Cli\Output\Color;
use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Utilities\Iterable\Arr;
use Locale;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @credit <a href="https://codeigniter.com">CodeIgniter 4 - \CodeIgniter\Commands\Translation\LocalizationFinder</a>
 */
class TranslationsFinder extends Command
{
    protected $group       = 'Translation';
    protected $name        = 'translations:find';
    protected $description = 'Trouver et sauvegarder les phrases disponibles à traduire';
    protected $options     = [
        '--locale'   => 'Spécifier la locale (en, ru, etc.) pour enregistrer les fichiers',
        '--dir'      => 'Répertoire de recherche des traductions relatif à APP_PATH.',
        '--show-new' => 'N\'affiche que les nouvelles traductions dans le tableau. N\'écrit pas dans les fichiers.',
        '--verbose'  => 'Affiche des informations détaillées',
    ];

    /**
     * Indicateur pour afficher des informations détaillées
     */
    private bool $verbose = false;

    /**
     * Indicateur pour afficher uniquement les traductions, sans sauvegarde
     */
    private bool $showNew = false;

    private string $languagePath;

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $this->verbose      = $this->option('verbose', false);
        $this->showNew      = $this->option('show-new', false);
        $optionLocale       = $params['locale'] ?? null;
        $optionDir          = $params['dir'] ?? null;
        $currentLocale      = Locale::getDefault();
        $currentDir         = APP_PATH;
        $this->languagePath = $currentDir . 'Translations';

        if (on_test()) {
            $currentDir         = ROOTPATH . 'Services' . DS;
            $this->languagePath = ROOTPATH . 'Translations';
        }

        if (is_string($optionLocale)) {
            if (! in_array($optionLocale, config('app.supported_locales'), true)) {
                $this->error(
                    $this->color->error('"' . $optionLocale . '" n\'est pas supporté. Les langues supportées sont: '
                    . implode(', ', config('app.supported_locales')))
                );

                return EXIT_USER_INPUT;
            }

            $currentLocale = $optionLocale;
        }

        if (is_string($optionDir)) {
            $tempCurrentDir = realpath($currentDir . $optionDir);

            if ($tempCurrentDir === false) {
                $this->error($this->color->error('Le dossier doit se trouvé dans "' . $currentDir . '"'));

                return EXIT_USER_INPUT;
            }

            if ($this->isSubDirectory($tempCurrentDir, $this->languagePath)) {
                $this->error($this->color->error('Le dossier "' . $this->languagePath . '" est restreint à l\'analyse.'));

                return EXIT_USER_INPUT;
            }

            $currentDir = $tempCurrentDir;
        }

        $this->process($currentDir, $currentLocale);

        $this->eol()->ok('Opérations terminées!');

        return EXIT_SUCCESS;
    }

    private function process(string $currentDir, string $currentLocale): void
    {
        $tableRows    = [];
        $countNewKeys = 0;

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($currentDir));
        $files    = iterator_to_array($iterator, true);
        ksort($files);

        [
            'foundLanguageKeys' => $foundLanguageKeys,
            'badLanguageKeys'   => $badLanguageKeys,
            'countFiles'        => $countFiles
        ] = $this->findLanguageKeysInFiles($files);

        ksort($foundLanguageKeys);

        $languageDiff        = [];
        $languageFoundGroups = array_unique(array_keys($foundLanguageKeys));

        foreach ($languageFoundGroups as $langFileName) {
            $languageStoredKeys = [];
            $languageFilePath   = $this->languagePath . DIRECTORY_SEPARATOR . $currentLocale . DIRECTORY_SEPARATOR . $langFileName . '.php';

            if (is_file($languageFilePath)) {
                // Charge les anciennes traductions
                $languageStoredKeys = require $languageFilePath;
            } elseif (! is_dir($dir = dirname($languageFilePath))) {
                // Si le dossier n'existe pas, on le cree
                @mkdir($dir, 0777, true);
            }

            $languageDiff = Arr::diffRecursive($foundLanguageKeys[$langFileName], $languageStoredKeys);
            $countNewKeys += Arr::countRecursive($languageDiff);

            if ($this->showNew) {
                $tableRows = array_merge($this->arrayToTableRows($langFileName, $languageDiff), $tableRows);
            } else {
                $newLanguageKeys = array_replace_recursive($foundLanguageKeys[$langFileName], $languageStoredKeys);

                if ($languageDiff !== []) {
                    if (file_put_contents($languageFilePath, $this->templateFile($newLanguageKeys)) === false) {
                        if ($this->verbose) {
                            $this->justify('Fichier de traduction "' . $langFileName . '"', 'Erreur lors de la modification', [
                                'second' => ['fg' => Color::RED],
                            ]);
                        }
                    } else {
                        if ($this->verbose) {
                            $this->justify('Fichier de traduction "' . $langFileName . '"', 'Modification éffectuée avec succès', [
                                'second' => ['fg' => Color::GREEN],
                            ]);
                        }
                    }
                }
            }
        }

        if ($this->showNew && $tableRows !== []) {
            sort($tableRows);
            $table = [];

            foreach ($tableRows as $body) {
                $table[] = array_combine(['Fichier', 'Clé'], $body);
            }
            $this->table($table);
        }

        if ($this->verbose) {
            $this->eol()->border(char: '*');
        }

        $this->justify('Fichiers analysés', $countFiles, ['second' => ['fg' => Color::GREEN]]);
        $this->justify('Nouvelles traductions trouvées', $countNewKeys, ['second' => ['fg' => Color::GREEN]]);
        $this->justify('Mauvaises traductions trouvées', count($badLanguageKeys), ['second' => ['fg' => Color::RED]]);

        if ($this->verbose && $badLanguageKeys !== []) {
            $tableBadRows = [];

            foreach ($badLanguageKeys as $value) {
                $tableBadRows[] = [$value[1], $value[0]];
            }

            usort($tableBadRows, static fn ($currentValue, $nextValue): int => strnatcmp((string) $currentValue[0], (string) $nextValue[0]));

            $table = [];

            foreach ($tableBadRows as $body) {
                $table[] = array_combine(['Mauvaise clé', 'Fichier'], $body);
            }

            $this->table($table);
        }

        if (! $this->showNew && $countNewKeys > 0) {
            $this->eol()->writer->bgRed('Note: Vous devez utiliser votre outil de linting pour résoudre les problèmes liés aux normes de codage.', true);
        }
    }

    /**
     * @param SplFileInfo|string $file
     *
     * @return array<string, array>
     */
    private function findTranslationsInFile($file): array
    {
        $foundLanguageKeys = [];
        $badLanguageKeys   = [];

        if (is_string($file) && is_file($file)) {
            $file = new SplFileInfo($file);
        }

        $fileContent = file_get_contents($file->getRealPath());

        preg_match_all('/\_\_\(\'([_a-z0-9À-ÿ\-]+)\'\)/ui', $fileContent, $matches);

        if ($matches[1] !== []) {
            $fileContent = str_replace($matches[0], array_map(static fn ($val) => "lang('App.{$val}')", $matches[1]), $fileContent);
        }

        preg_match_all('/lang\(\'([._a-z0-9À-ÿ\-]+)\'\)/ui', $fileContent, $matches);

        if ($matches[1] === []) {
            return compact('foundLanguageKeys', 'badLanguageKeys');
        }

        foreach ($matches[1] as $phraseKey) {
            $phraseKeys = explode('.', $phraseKey);

            // Le code langue n'a pas de nom de fichier ou de code langue.
            if (count($phraseKeys) < 2) {
                $badLanguageKeys[] = [mb_substr($file->getRealPath(), mb_strlen(ROOTPATH)), $phraseKey];

                continue;
            }

            $languageFileName   = array_shift($phraseKeys);
            $isEmptyNestedArray = ($languageFileName !== '' && $phraseKeys[0] === '')
                || ($languageFileName === '' && $phraseKeys[0] !== '')
                || ($languageFileName === '' && $phraseKeys[0] === '');

            if ($isEmptyNestedArray) {
                $badLanguageKeys[] = [mb_substr($file->getRealPath(), mb_strlen(ROOTPATH)), $phraseKey];

                continue;
            }

            if (count($phraseKeys) === 1) {
                $foundLanguageKeys[$languageFileName][$phraseKeys[0]] = $phraseKey;
            } else {
                $childKeys = $this->buildMultiArray($phraseKeys, $phraseKey);

                $foundLanguageKeys[$languageFileName] = array_replace_recursive($foundLanguageKeys[$languageFileName] ?? [], $childKeys);
            }
        }

        return compact('foundLanguageKeys', 'badLanguageKeys');
    }

    private function isIgnoredFile(SplFileInfo $file): bool
    {
        if ($file->isDir() || $this->isSubDirectory($file->getRealPath(), $this->languagePath)) {
            return true;
        }

        return $file->getExtension() !== 'php';
    }

    private function templateFile(array $language = []): string
    {
        if ($language !== []) {
            $languageArrayString = var_export($language, true);

            $code = <<<PHP
                <?php

                return {$languageArrayString};

                PHP;

            return $this->replaceArraySyntax($code);
        }

        return <<<'PHP'
            <?php

            return [];

            PHP;
    }

    private function replaceArraySyntax(string $code): string
    {
        $tokens    = token_get_all($code);
        $newTokens = $tokens;

        foreach ($tokens as $i => $token) {
            if (is_array($token)) {
                [$tokenId, $tokenValue] = $token;

                // Remplace "array (" par "["
                if (
                    $tokenId === T_ARRAY
                    && $tokens[$i + 1][0] === T_WHITESPACE
                    && $tokens[$i + 2] === '('
                ) {
                    $newTokens[$i][1]     = '[';
                    $newTokens[$i + 1][1] = '';
                    $newTokens[$i + 2]    = '';
                }

                // Remplace les indentations
                if ($tokenId === T_WHITESPACE && preg_match('/\n([ ]+)/u', $tokenValue, $matches)) {
                    $newTokens[$i][1] = "\n{$matches[1]}{$matches[1]}";
                }
            } // Remplace ")"
            elseif ($token === ')') {
                $newTokens[$i] = ']';
            }
        }

        $output = '';

        foreach ($newTokens as $token) {
            $output .= $token[1] ?? $token;
        }

        return $output;
    }

    /**
     * Crée un tableau multidimensionnel à partir d'autres clés
     */
    private function buildMultiArray(array $fromKeys, string $lastArrayValue = ''): array
    {
        $newArray  = [];
        $lastIndex = array_pop($fromKeys);
        $current   = &$newArray;

        foreach ($fromKeys as $value) {
            $current[$value] = [];
            $current         = &$current[$value];
        }

        $current[$lastIndex] = $lastArrayValue;

        return $newArray;
    }

    /**
     * Convertit les tableaux multidimensionnels en lignes de table CLI spécifiques (tableau plat)
     */
    private function arrayToTableRows(string $langFileName, array $array): array
    {
        $rows = [];

        foreach ($array as $value) {
            if (is_array($value)) {
                $rows = array_merge($rows, $this->arrayToTableRows($langFileName, $value));

                continue;
            }

            if (is_string($value)) {
                $rows[] = [$langFileName, $value];
            }
        }

        return $rows;
    }

    private function isSubDirectory(string $directory, string $rootDirectory): bool
    {
        return 0 === strncmp($directory, $rootDirectory, strlen($directory));
    }

    /**
     * @param list<SplFileInfo> $files
     *
     * @return         array<string, array|int>
     * @phpstan-return array{'foundLanguageKeys': array<string, array<string, string>>, 'badLanguageKeys': array<int, array<int, string>>, 'countFiles': int}
     */
    private function findLanguageKeysInFiles(array $files): array
    {
        $foundLanguageKeys = [];
        $badLanguageKeys   = [];
        $countFiles        = 0;

        foreach ($files as $file) {
            if ($this->isIgnoredFile($file)) {
                continue;
            }

            if ($this->verbose) {
                $this->justify(mb_substr($file->getRealPath(), mb_strlen(APP_PATH)), 'Analysé', [
                    'second' => ['fg' => Color::YELLOW],
                ]);
            }
            $countFiles++;

            $findInFile = $this->findTranslationsInFile($file);

            $foundLanguageKeys = array_replace_recursive($findInFile['foundLanguageKeys'], $foundLanguageKeys);
            $badLanguageKeys   = array_merge($findInFile['badLanguageKeys'], $badLanguageKeys);
        }

        return compact('foundLanguageKeys', 'badLanguageKeys', 'countFiles');
    }
}
