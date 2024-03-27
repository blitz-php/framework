<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Utilities;

use Ahc\Cli\Output\Color;
use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Debug\Toolbar\Collectors\Config;
use BlitzPHP\Facades\Container;
use BlitzPHP\Utilities\Iterable\Collection;
use BlitzPHP\Utilities\String\Text;
use Closure;

/**
 * Affiche les informations de base sur de l'application
 */
class About extends Command
{
    /**
     * @var string Groupe
     */
    protected $group = 'BlitzPHP';

    protected $service = 'Service de configuration';

    /**
     * @var string Nom
     */
    protected $name = 'about';

    /**
     * @var string Description
     */
    protected $description = 'Affiche les informations de base sur de l\'application.';

    /**
     * Options de la commande
     *
     * @var array<string, string>
     */
    protected $options = [
        '--only' => 'La section à afficher.',
        '--json' => 'Afficher les informations au format json.',
    ];

    /**
     * Donnees a afficher.
     */
    protected static array $data = [];

    /**
     * Les callables enregistrés qui ajoutent des données personnalisées à la sortie de la commande.
     */
    protected static array $customDataResolvers = [];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $this->gatherApplicationInformation();

        $this->center('Information générales sur votre application', ['fg' => Color::YELLOW]);
        $this->border();


        collect(static::$data)
            ->map(fn ($items) => collect($items)
                ->map(function ($value) {
                    if (is_array($value)) {
                        return [$value];
                    }

                    if (is_string($value)) {
                        $value = Container::make($value);
                    }

                    return collect(Container::call($value))
                        ->map(fn ($value, $key) => [$key, $value])
                        ->values()
                        ->all();
                })->flatten(1)
            )
            ->sortBy(function ($data, $key) {
                $index = array_search($key, ['Environnement', 'Cache', 'Gestionnaires']);

                return $index === false ? 99 : $index;
            })
            ->filter(function ($data, $key) {
                return $this->option('only') ? in_array($this->toSearchKeyword($key), $this->sections()) : true;
            })
            ->pipe(fn ($data) => $this->display($data));


        return EXIT_SUCCESS;
    }
    
    /**
     * Affiche les informations sur l'application.
     */
    protected function display(Collection $data): void
    {
        $this->option('json') ? $this->displayJson($data) : $this->displayDetail($data);
    }

    /**
     * Affiche les informations sur l'application sous forme de vue détaillée.
     */
    protected function displayDetail(Collection $data): void
    {
        $data->each(function ($data, $section) {
            $this->newLine();

            $this->justify($section, '', ['first' => ['fg' => Color::GREEN]]);
 
            $data->pipe(fn ($data) => $section !== 'Environnement' ? $data->sort() : $data)->each(function ($detail) {
                [$label, $value] = $detail;

                $this->justify($label, value($value, false));
            });
        });
    }

    /**
     * Affiche les informations sur l'application sous forme de json.
     */
    protected function displayJson(Collection $data): void
    {
        $output = $data->flatMap(function ($data, $section) {
            return [
                (string) Text::of($section)->snake() => $data->mapWithKeys(fn ($item, $key) => [
                    $this->toSearchKeyword($item[0]) => value($item[1], true),
                ]),
            ];
        });

        $this->eol()->json($output);
    }

    /**
     * Rassemble des informations sur l’application.
     */
    protected function gatherApplicationInformation(): void
    {
        self::$data = [];

        $formatEnabledStatus = fn ($value) => $value ? $this->color->warn('ACTIVE') : $this->color->warn('DESACTIVE');
        $formatCachedStatus  = fn ($value) => $value ? $this->color->ok('MISE EN CACHE') : $this->color->warn('NON MISE EN CACHE');

        $config = (object) Config::display();

        static::addToSection('Environnement', fn () => [
            'Nom de l\'application' => $config->appName,
            'Version de BlitzPHP' => $config->blitzVersion,
            'Version de PHP'      => PHP_VERSION,
            // 'Composer Version' => $this->composer->getVersion() ?? '<fg=yellow;options=bold>-</>',
            'Environnement'      => $config->environment,
            'Mode debug'       => static::format(config('app.debug'), console: $formatEnabledStatus),
            'URL'              => Text::of($config->baseURL)->replace(['http://', 'https://'], ''),
            // 'Maintenance Mode' => static::format($this->laravel->isDownForMaintenance(), console: $formatEnabledStatus),
        ]);

        static::addToSection('Cache', fn () => [
            // 'Config' => static::format($this->laravel->configurationIsCached(), console: $formatCachedStatus),
            // 'Events' => static::format($this->laravel->eventsAreCached(), console: $formatCachedStatus),
            // 'Routes' => static::format($this->laravel->routesAreCached(), console: $formatCachedStatus),
            'Vues' => static::format($this->hasPhpFiles(storage_path('framework/cache/views')), console: $formatCachedStatus),
        ]);

        static::addToSection('Gestionnaires', fn () => array_filter([
            'Cache'    => config('cache.handler'),
            'Base de données' => config('database.default'),
            'Logs'     => function ($json) {
                $handlers = [];
                foreach (config('log.handlers') as $k => $v) {
                    $handlers[] = $k;
                }

                return implode(', ', $handlers);
            },
            'Mail'    => config('mail.handler'),
            'Session' => config('session.handler'),
        ]));

        collect(static::$customDataResolvers)->each->__invoke();
    }

    /**
     * Détermine si le répertoire donné contient des fichiers PHP.
     */
    protected function hasPhpFiles(string $path): bool
    {
        return count(glob($path.'/*.php')) > 0;
    }

    /**
     * Ajoute des données supplémentaires à la sortie de la commande "about".
     *
     * @param  callable|string|array  $data
     */
    public static function add(string $section, $data, ?string $value = null): void
    {
        static::$customDataResolvers[] = fn () => static::addToSection($section, $data, $value);
    }

    /**
     * Ajoute des données supplémentaires à la sortie de la commande "about".
     * 
     * @param  callable|string|array  $data
     */
    protected static function addToSection(string $section, $data, ?string $value = null): void
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                self::$data[$section][] = [$key, $value];
            }
        } elseif (is_callable($data) || ($value === null && class_exists($data))) {
            self::$data[$section][] = $data;
        } else {
            self::$data[$section][] = [$data, $value];
        }
    }

    /**
     * Récupère les sections fournies à la commande.
     */
    protected function sections(): array
    {
        return collect(explode(',', $this->option('only') ?? ''))
            ->filter()
            ->map(fn ($only) => $this->toSearchKeyword($only))
            ->all();
    }

    /**
     * Matérialise une fonction qui formate une valeur donnée pour la sortie CLI ou JSON.
     *
     * @param  (\Closure(mixed):(mixed))|null  $console
     * @param  (\Closure(mixed):(mixed))|null  $json
     * @return \Closure(bool):mixed
     */
    public static function format(mixed $value, Closure $console = null, Closure $json = null)
    {
        return function ($isJson) use ($value, $console, $json) {
            if ($isJson === true && $json instanceof Closure) {
                return value($json, $value);
            } elseif ($isJson === false && $console instanceof Closure) {
                return value($console, $value);
            }

            return value($value);
        };
    }

    /**
     * Formatez la chaîne donnée pour la recherche.
     */
    protected function toSearchKeyword(string $value): string
    {
        return (string) Text::of($value)->lower()->snake();
    }

    /**
     * Videz les données enregistrées.
     */
    public static function flushState(): void
    {
        static::$data = [];

        static::$customDataResolvers = [];
    }
}
