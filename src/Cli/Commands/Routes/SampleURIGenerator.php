<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Routes;

use BlitzPHP\Container\Services;
use BlitzPHP\Router\RouteCollection;

/**
 * Générez un exemple de chemin d'URI à partir de l'expression régulière de la clé de route.
 */
final class SampleURIGenerator
{
    private RouteCollection $routes;

    /**
     * Exemple de chemin URI pour l'espace réservé.
     *
     * @var array<string, string>
     */
    private array $samples = [
        'any'      => '123/abc',
        'segment'  => 'abc_123',
        'alphanum' => 'abc123',
        'num'      => '123',
        'alpha'    => 'abc',
        'hash'     => 'abc_123',
        'slug'     => 'abc-123',
    ];

    public function __construct(?RouteCollection $routes = null)
    {
        $this->routes = $routes ?? Services::routes();
    }

    /**
     * @param string $routeKey clé de routage regex
     *
     * @return string exemple de chemin URI
     */
    public function get(string $routeKey): string
    {
        $sampleUri = $routeKey;

        foreach ($this->routes->getPlaceholders() as $placeholder => $regex) {
            $sample = $this->samples[$placeholder] ?? '::unknown::';

            $sampleUri = str_replace('(' . $regex . ')', $sample, $sampleUri);
        }

        // auto route
        return str_replace('[/...]', '/1/2/3/4/5', $sampleUri);
    }
}
