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
        $this->verbose      = array_key_exists('verbose', $params);
        $this->showNew      = array_key_exists('show-new', $params);
        $optionLocale       = $params['locale'] ?? null;
        $optionDir          = $params['dir'] ?? null;
        $currentLocale      = Locale::getDefault();
        $currentDir         = APP_PATH;
        $this->languagePath = $currentDir . 'Translations';

        if (ENVIRONMENT === 'testing') {
            $currentDir         = SUPPORT_PATH . 'Services' . DS;
            $this->languagePath = APP_PATH . 'Translations';
        }

        if (is_string($optionLocale)) {
            if (! in_array($optionLocale, config('app.supported_locales'), true)) {
                $this->error(
                    'Erreur: "' . $optionLocale . '" n\'est pas supporté. Les langues supportées sont: '
                    . implode(', ', config('app.supported_locales'))
                );

                return EXIT_USER_INPUT;
            }

            $currentLocale = $optionLocale;
        }

        if (is_string($optionDir)) {
            $tempCurrentDir = realpath($currentDir . $optionDir);

            if ($tempCurrentDir === false) {
                $this->error('Erreur: Le dossier doit se trouvé dans "' . $currentDir . '"');

                return EXIT_USER_INPUT;
            }

            if ($this->isSubDirectory($tempCurrentDir, $this->languagePath)) {
                $this->error('Erreur: Le dossier "' . $this->languagePath . '" est restreint à l\'analyse.');

                return EXIT_USER_INPUT;
            }

            $currentDir = $tempCurrentDir;
        }

        $this->process($currentDir, $currentLocale);

        $this->ok('Opérations terminées!');

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
            }

            $languageDiff = Arr::diffRecursive($foundLanguageKeys[$langFileName], $languageStoredKeys);
            $countNewKeys += Arr::countRecursive($languageDiff);

            if ($this->showNew) {
                $tableRows = array_merge($this->arrayToTableRows($langFileName, $languageDiff), $tableRows);
            } else {
                $newLanguageKeys = array_replace_recursive($foundLanguageKeys[$langFileName], $languageStoredKeys);

                if ($languageDiff !== []) {
                    if (file_put_contents($languageFilePath, $this->templateFile($newLanguageKeys)) === false) {
                        $this->writeIsVerbose('Fichier de traduction ' . $langFileName . ' (error write).', 'red');
                    } else {
                        $this->writeIsVerbose('Le fichier de traduction "' . $langFileName . '" a été modifié avec succès!', 'green');
                    }
                }
            }
        }

        if ($this->showNew && $tableRows !== []) {
            sort($tableRows);
            $table = [];

            foreach ($tableRows as $body) {
                $table[] = array_combine(['File', 'Key'], $body);
            }
            $this->table($table);
        }

        if (! $this->showNew && $countNewKeys > 0) {
            $this->writer->bgRed('Note: Vous devez utiliser votre outil de linting pour résoudre les problèmes liés aux normes de codage.');
        }

        $this->writeIsVerbose('Fichiers trouvés: ' . $countFiles);
        $this->writeIsVerbose('Nouvelles traductions trouvées: ' . $countNewKeys);
        $this->writeIsVerbose('Mauvaises traductions trouvées: ' . count($badLanguageKeys));

        if ($this->verbose && $badLanguageKeys !== []) {
            $tableBadRows = [];

            foreach ($badLanguageKeys as $value) {
                $tableBadRows[] = [$value[1], $value[0]];
            }

            usort($tableBadRows, static fn ($currentValue, $nextValue): int => strnatcmp((string) $currentValue[0], (string) $nextValue[0]));

            $table = [];

            foreach ($tableBadRows as $body) {
                $table[] = array_combine(['Bad Key', 'Filepath'], $body);
            }

            $this->table($table);
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
        preg_match_all('/lang\(\'([._a-z0-9\-]+)\'\)/ui', $fileContent, $matches);

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

    /**
     * Affiche les détails dans la console si l'indicateur est défini
     */
    private function writeIsVerbose(string $text = '', ?string $foreground = null, ?string $background = null): void
    {
        if ($this->verbose) {
            $this->write($this->color->line($text, ['fg' => $foreground, 'bg' => $background]));
        }
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

            $this->writeIsVerbose('Ficher trouvé: ' . mb_substr($file->getRealPath(), mb_strlen(APP_PATH)));
            $countFiles++;

            $findInFile = $this->findTranslationsInFile($file);

            $foundLanguageKeys = array_replace_recursive($findInFile['foundLanguageKeys'], $foundLanguageKeys);
            $badLanguageKeys   = array_merge($findInFile['badLanguageKeys'], $badLanguageKeys);
        }

        return compact('foundLanguageKeys', 'badLanguageKeys', 'countFiles');
    }
}
