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

use BlitzPHP\Traits\SingletonTrait;
use InvalidArgumentException;

/**
 * Environment-specific configuration
 */
class DotEnv
{
    use SingletonTrait;

    /**
     * Le répertoire où se trouve le fichier .env.
     *
     * @var string
     */
    protected $path;

    /**
     * Construit le chemin vers notre fichier.
     */
    private function __construct(string $path, string $file = '.env')
    {
        $this->path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
    }

    public static function init(string $path, string $file = '.env')
    {
        return self::instance($path, $file)->load();
    }

    /**
     * Le point d'entrée principal chargera le fichier .env et le traitera
     * pour que nous nous retrouvions avec tous les paramètres dans l'environnement PHP vars
     * (c'est-à-dire getenv(), $_ENV et $_SERVER)
     */
    public function load(): bool
    {
        $vars = $this->parse();

        if ($vars === null) {
            return false;
        }

        foreach ($vars as $name => $value) {
            $this->setVariable($name, $value);
        }

        return true; // notifie de la reussite de l'operation
    }

	/**
     * Remplace les valeurs dans le fichiers .env
	 * 
	 * Si une valeur n'existe pas, elle est ajoutée au fichier
     */
	public function replace(array $data, bool $reload = true): bool
	{	
		$oldFileContents = (string) file_get_contents($this->path);

		foreach ($data as $key => $value) {
			$replacementKey  = "\n{$key} = {$value}";
			if (strpos($oldFileContents, $key) === false) {
				if (file_put_contents($this->path, $replacementKey, FILE_APPEND) === false) {
					return false;
				}
				unset($data[$key]);
			}
		}

		if ($data === []) {
			if ($reload) {
				return $this->load();
			}
			return true;
		}

		return $this->update($data, $reload);
	}

    /**
     * Modifie les valeurs dans le fichiers .env
     */
    public function update(array $data = [], bool $reload = true): bool
    {
        foreach ($data as $key => $value) {
            if (env($key) === $value) {
                unset($data[$key]);
            }
        }

        if (! count($data)) {
            return false;
        }

        // ecrit seulement si il y'a des changements dans le contenu

        $env = file_get_contents($this->path);
        $env = explode("\n", $env);

        foreach ((array) $data as $key => $value) {
            foreach ($env as $env_key => $env_value) {
                $entry = explode('=', $env_value, 2);
                $entry = array_map('trim', $entry);
                if ($entry[0] === $key) {
                    $env[$env_key] = $key . '=' . (is_string($value) ? '"' . $value . '"' : $value);
                } else {
                    $env[$env_key] = $env_value;
                }
            }
        }

        $env = implode("\n", $env);
        file_put_contents($this->path, $env);

        if ($reload) {
            return $this->load();
        }

        return true;
    }

    /**
     * Parse le fichier .env file dans un tableau de cle => valeur
     */
    public function parse(): ?array
    {
        // Nous ne voulons pas imposer la présence d'un fichier .env, ils devraient être facultatifs.
        if (! is_file($this->path)) {
            return null;
        }

        // Assurez-vous que le fichier est lisible
        if (! is_readable($this->path)) {
            throw new InvalidArgumentException("The .env file is not readable: {$this->path}");
        }

        $vars = [];

        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // C'est un commentaire?
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // S'il y a un signe égal, alors nous savons que nous affectons une variable.
            if (strpos($line, '=') !== false) {
                [$name, $value] = $this->normaliseVariable($line);
                $vars[$name]    = $value;
            }
        }

        return $vars;
    }

    /**
     * Définit la variable dans l'environnement. Analysera la chaîne
     * premier à rechercher le modèle {name}={value}, assurez-vous que imbriqué
     * les variables sont gérées et débarrassées des guillemets simples et doubles.
     */
    protected function setVariable(string $name, string $value = '')
    {
        if (! getenv($name, true)) {
            putenv("{$name}={$value}");
        }
        if (empty($_ENV[$name])) {
            $_ENV[$name] = $value;
        }
        if (empty($_SERVER[$name])) {
            $_SERVER[$name] = $value;
        }
    }

    /**
     * Analyse l'affectation, nettoie le $name et la $value, et s'assure
     * que les variables imbriquées sont gérées.
     */
    public function normaliseVariable(string $name, string $value = ''): array
    {
        // Divisez notre chaîne composée en ses parties.
        if (strpos($name, '=') !== false) {
            [$name, $value] = explode('=', $name, 2);
        }

        $name  = trim($name);
        $value = trim($value);

        // Assainir le nom
        $name = str_replace(['export', '\'', '"'], '', $name);

        // Assainir la valeur
        $value = $this->sanitizeValue($value);

        $value = $this->resolveNestedVariables($value);

        return [
            $name,
            $value,
        ];
    }

    /**
     * Supprime les guillemets de la valeur de la variable d'environnement.
     *
     * Ceci a été emprunté à l'excellent phpdotenv avec très peu de modifications.
     * https://github.com/vlucas/phpdotenv
     *
     * @throws InvalidArgumentException
     */
    protected function sanitizeValue(string $value): string
    {
        if (! $value) {
            return $value;
        }

        // Commence-t-il par une citation ?
        if (strpbrk($value[0], '"\'') !== false) {
            // la valeur commence par un guillemet
            $quote        = $value[0];
            $regexPattern = sprintf(
                '/^
					%1$s          # match a quote at the start of the value
					(             # capturing sub-pattern used
								  (?:          # we do not need to capture this
								   [^%1$s\\\\] # any character other than a quote or backslash
								   |\\\\\\\\   # or two backslashes together
								   |\\\\%1$s   # or an escaped quote e.g \"
								  )*           # as many characters that match the previous rules
					)             # end of the capturing sub-pattern
					%1$s          # and the closing quote
					.*$           # and discard any string after the closing quote
					/mx',
                $quote
            );
            $value = preg_replace($regexPattern, '$1', $value);
            $value = str_replace("\\{$quote}", $quote, $value);
            $value = str_replace('\\\\', '\\', $value);
        } else {
            $parts = explode(' #', $value, 2);

            $value = trim($parts[0]);

            // Les valeurs sans guillemets ne peuvent pas contenir d'espaces
            if (preg_match('/\s+/', $value) > 0) {
                throw new InvalidArgumentException('.env values containing spaces must be surrounded by quotes.');
            }
        }

        return $value;
    }

    /**
     * Résolvez les variables imbriquées.
     *
     * Recherchez les modèles ${varname} dans la valeur de la variable et remplacez-les par un existant
     * variables d'environnement.
     *
     * Ceci a été emprunté à l'excellent phpdotenv avec très peu de modifications.
     * https://github.com/vlucas/phpdotenv
     */
    protected function resolveNestedVariables(string $value): string
    {
        if (strpos($value, '$') !== false) {
            $loader = $this;

            $value = preg_replace_callback(
                '/\${([a-zA-Z0-9_]+)}/',
                static function ($matchedPatterns) use ($loader) {
                    $nestedVariable = $loader->getVariable($matchedPatterns[1]);

                    if (null === $nestedVariable) {
                        return $matchedPatterns[0];
                    }

                    return $nestedVariable;
                },
                $value
            );
        }

        return $value;
    }

    /**
     * Rechercher les différents endroits pour les variables d'environnement et renvoyer la première valeur trouvée.
     *
     * Ceci a été emprunté à l'excellent phpdotenv avec très peu de modifications.
     * https://github.com/vlucas/phpdotenv
     */
    protected function getVariable(string $name): ?string
    {
        switch (true) {
            case array_key_exists($name, $_ENV):
                return $_ENV[$name];

            case array_key_exists($name, $_SERVER):
                return $_SERVER[$name];

            default:
                $value = getenv($name);

                // passe la valeur par défaut de getenv à null
                return $value === false ? null : $value;
        }
    }
}
