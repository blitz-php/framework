<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Loader;

use BlitzPHP\Exceptions\LoadException;
use BlitzPHP\Traits\SingletonTrait;
use InvalidArgumentException;

/**
 * Chargeur de variables d'environnement depuis fichiers .env.
 *
 * Supporte parsing avancé (nested, quotes, multiline), validation, et multi-files.
 * Inspiré de phpdotenv.
 */
class DotEnv
{
    use SingletonTrait;

    /**
     * Variables parsées (cache interne).
     *
     * @var array<string, string>
     */
    protected array $env = [];

    /**
     * Chemin du fichier principal.
     *
     * @var string
     */
    protected string $path;

    /**
     * Cache TTL (secondes, 0=infini).
     *
     * @var int
     */
    protected int $cacheTtl = 300; // 5 min par défaut

    /**
     * Timestamp dernier load.
     *
     * @var int|null
     */
    protected ?int $lastLoad = null;

    /**
     * Constructeur privé (singleton).
     *
     * @param string $path Répertoire .env.
     * @param string $file Nom fichier (défaut '.env').
     */
    private function __construct(string $path, string $file = '.env')
    {
        $this->path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
        $this->env = []; // Init vide
    }

    /**
     * Initialise et charge (singleton).
     *
     * @param array<string> $overrides Fichiers supplémentaires (.env.local, etc.).
     *
	 * @throws LoadException Si fichiers illisibles.
     */
    public static function init(string $path, string $file = '.env', array $overrides = []): bool
    {
        return self::instance($path, $file)->load($overrides);
    }

    /**
     * Le point d'entrée principal chargera le fichier .env et le traitera
     * pour que nous nous retrouvions avec tous les paramètres dans l'environnement PHP vars
     * (c'est-à-dire getenv(), $_ENV et $_SERVER)
     *
     * @param array<string> $overrides Fichiers extra.
     */
    public function load(array $overrides = []): bool
    {
        if ($this->isCached()) {
            return true; // Cache hit
        }

        $this->env = []; // Reset

        // Charge principal
        if (!$this->parseFile($this->path)) {
            return false;
        }

        // Overrides (merge, last wins)
        foreach ($overrides as $overrideFile) {
            $overridePath = dirname($this->path) . DIRECTORY_SEPARATOR . $overrideFile;
            $this->parseFile($overridePath);
        }

        $this->syncEnv(); // Sync globals
        $this->lastLoad = time();

        return true;
    }

    /**
     * Recharge (ignore cache).
     *
     * @param list<string> $overrides Fichiers extra
     */
    public function reload(array $overrides = []): bool
    {
        $this->lastLoad = null;

        return $this->load($overrides);
    }

	/**
     * Remplace les valeurs dans le fichiers .env
     *
     * Si une valeur n'existe pas, elle est ajoutée au fichier
     *
     * @deprecated use self::update() instead
     */
    public function replace(array $data, bool $reload = true): bool
    {
        // OPTIM: Délégue à update pour cohérence et robustesse
        return $this->update($data, $reload);
    }

    /**
     * Modifie les valeurs dans le fichier .env
     *
     * @param array<string, string> $data Données à updater
     */
    public function update(array $data, bool $reload = true): bool
    {
        if ($data === []) {
            return false;
        }

        $content = file_get_contents($this->path);
        if ($content === false) {
            return false;
        }

        $lines   = explode("\n", $content);
        $updated = false;

        $keyMap = [];
        foreach ($lines as $i => $line) {
            $trimmed = trim($line);
            if (empty($trimmed) || str_starts_with($trimmed, '#')) {
                continue;
            }
            if (preg_match('/^\s*([A-Za-z0-9_.-]+)\s*=\s*(.*)$/u', $trimmed, $matches)) {
                $key          = $matches[1];
                $keyMap[$key] = $i; // Index pour update
            }
        }

        // Update ou add
        foreach ($data as $key => $value) {
            $entry = $key . '=' . (is_string($value) ? '"' . $value . '"' : $value);
            if (isset($keyMap[$key])) {
                // Update ligne existante (gère comments)
                $lineIndex = $keyMap[$key];
                $lines[$lineIndex] = $entry;
                $updated = true;
            } else {
                // Add à la fin
                $lines[] = $entry;
                $updated = true;
            }
        }

        if ($updated) {
            $content = implode("\n", $lines) . "\n"; // Ajout \n final
            if (file_put_contents($this->path, $content, LOCK_EX) === false) { // LOCK_EX pour atomicité
                return false;
            }

            chmod($this->path, 0600); // chmod pour sécurité
        }

        if ($reload) {
            return $this->reload();
        }

        return true;
    }

    /**
     * Parse un fichier .env.
     *
     * @throws LoadException Si illisible/malformed.
     */
    protected function parseFile(string $filePath): bool
    {
        if (!is_readable($filePath)) {
            if (!file_exists($filePath)) {
                return false; // Optionnel
            }
            throw new LoadException("Fichier .env '{$filePath}' non lisible.");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new LoadException("Impossible de lire '{$filePath}'.");
        }

        $lines     = explode("\n", $content);
        $parsed    = $this->parseLines($lines); //Two-pass : d'abord parse, puis resolve nested après tout collecté
        $this->env = array_merge($this->env, $parsed);

        // Resolve nested après tout parsing (pour deep nesting)
        foreach ($this->env as $key => $value) {
            $this->env[$key] = $this->resolveNestedVariables($value, true); // true pour itératif
        }

        return true;
    }

    /**
     * Parse lignes en vars.
     *
     * @param list<string> $lines Lignes du fichier
	 *
     * @return array<string, string>
	 *
     * @throws InvalidArgumentException Si malformed (e.g., unclosed quote).
     */
    protected function parseLines(array $lines): array
    {
        $result         = [];
        $inMultiline    = false;
        $multilineKey   = '';
        $multilineValue = '';

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue; // Skip empty/comments
            }

            if ($inMultiline) {
                // Fin multiline ? Multiline standard dotenv : utilise quotes ou indent ; ici, fin sur ligne vide ou # après |
                if (empty($line) || str_starts_with($line, '#') || str_starts_with($line, 'END')) {
                    $result[$multilineKey] = trim($multilineValue);
                    $inMultiline = false;
                    continue;
                }
                $multilineValue .= "\n" . $line;
                continue;
            }

            // Regex pour = dans value et comments
            if (preg_match('/^\s*([A-Za-z0-9_.-]+)\s*=\s*(.*)$/u', $line, $matches)) {
                $key = $matches[1];
                $value = $matches[2];

                // Trim quotes si présentes (gère escaped quotes)
                if (preg_match('/^"(.*)"$/s', $value, $q)) {
                    $value = str_replace('\\"', '"', $q[1]); // Unescape
                } elseif (preg_match("/^'(.*)'$/s", $value, $q)) {
                    $value = str_replace("\\'", "'", $q[1]);
                }

                // Escaped chars
                $value = str_replace(['\\n', '\\t', '\\\\'], ["\n", "\t", '\\'], $value);

                // Multiline : si value starts with | (folded) ou > (literal)
                if (str_starts_with(rtrim($value), '|') || str_starts_with(rtrim($value), '>')) {
                    $multilineKey = $key;
                    $multilineValue = substr(rtrim($value), 1); // Enlève | ou >
                    $inMultiline = true;
                    continue;
                }

                $result[$key] = $value;
            } else {
                throw new InvalidArgumentException("Ligne malformée : {$line}");
            }
        }

        // Fermeture multiline si pending
        if ($inMultiline) {
            $result[$multilineKey] = trim($multilineValue);
        }

        return $result;
    }

    /**
     * Résout variables nested (${VAR}). Itératif pour deep nesting.
     *
     * @param string $value Valeur à résoudre
     * @param bool $iterative Itératif ?
	 *
     * @return string Résolue
     */
    protected function resolveNestedVariables(string $value, bool $iterative = false): string
    {
        if (!str_contains($value, '${')) {
            return $value;
        }

        $original = $value;
        do {
            $value = preg_replace_callback(
                '/\$\{([a-zA-Z0-9_\.-]+)\}/', // Non-greedy, . autorisé
                function ($matches) {
                    $var = $this->getVariable($matches[1]);
                    return $var ?? $matches[0]; // Garde literal si null
                },
                $value
            );
        } while ($iterative && $value !== $original && str_contains($value, '${'));

        return $value;
    }

    /**
     * Obtient variable depuis sources.
     */
    protected function getVariable(string $name): ?string
    {
		$value = $_ENV[$name] ?? $_SERVER[$name] ?? $this->env[$name] ?? null;

		// getenv() retourne false si la variable n'existe pas, donc il faut la traiter differement
    	if (null === $value && false !== $envValue = getenv($name)) {
			$value = $envValue;
		}

    	return $value !== null ? $value : null;
    }

    /**
     * Sync parsed vers globals.
     */
    protected function syncEnv(): void
    {
        foreach ($this->env as $name => $value) {
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
            putenv("{$name}={$value}");
        }
    }

    /**
     * Définit/override variable (résout nested).
     *
     * @param string $name Nom valide (A-Z0-9_.-).
	 *
     * @throws InvalidArgumentException Si name invalide.
     */
    public function setValue(string $name, string $value): void
    {
        if (!preg_match('/^[A-Za-z0-9_.-]+$/', $name)) {
            throw new InvalidArgumentException("Nom invalide : {$name}");
        }

        $value = $this->resolveNestedVariables($value, true);
        $this->env[$name] = $value;
        $this->syncEnv(); // Sync immédiat
        $this->lastLoad = null; // Invalide cache
    }

    /**
     * Valide required vars.
     *
     * @param array<string> $required Vars requises
	 *
     * @throws LoadException Si manquante.
     */
    public function validate(array $required): void
    {
        foreach ($required as $var) {
            if (!isset($this->env[$var]) || $this->env[$var] === '') {
                throw new LoadException("Variable requise manquante : {$var}");
            }
        }
    }

    /**
     * Cache valide ?
     */
    protected function isCached(): bool
    {
        return $this->lastLoad !== null && (time() - $this->lastLoad) < $this->cacheTtl;
    }

    /**
     * Export vars parsées.
     *
     * @return array<string, string>
     */
    public function export(): array
    {
        return $this->env;
    }
}
