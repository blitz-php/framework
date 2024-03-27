<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Debug;

use Closure;

/**
 * Iterateur pour le debuggage.
 */
class Iterator
{
    /**
     * Stocke les tests que nous devons exécuter.
     *
     * @var array<string, Closure>
     */
    protected array $tests = [];

    /**
     * Stocke les résultats de chacun des tests.
     *
     * @var array<string, array>
     */
    protected array $results = [];

    /**
     * Ajoute un test à exécuter.
     *
     * Les tests sont simplement des fermetures permettant à l'utilisateur de définir n'importe quelle séquence de choses qui se produiront pendant le test.
     */
    public function add(string $name, Closure $closure): self
    {
        $name = strtolower($name);

        $this->tests[$name] = $closure;

        return $this;
    }

    /**
     * Exécute tous les tests qui ont été ajoutés, en enregistrant le temps nécessaire pour exécuter
     * le nombre d'itérations souhaité et l'utilisation approximative de la mémoire utilisée au cours
     * de ces itérations.
     */
    public function run(int $iterations = 1000, bool $output = true): ?string
    {
        foreach ($this->tests as $name => $test) {
            // clear memory before start
            gc_collect_cycles();

            $start    = microtime(true);
            $startMem = $maxMemory = memory_get_usage(true);

            for ($i = 0; $i < $iterations; $i++) {
                $result    = $test();
                $maxMemory = max($maxMemory, memory_get_usage(true));

                unset($result);
            }

            $this->results[$name] = [
                'time'   => microtime(true) - $start,
                'memory' => $maxMemory - $startMem,
                'n'      => $iterations,
            ];
        }

        if ($output) {
            return $this->getReport();
        }

        return null;
    }

    /**
     * Recupere le resultat du test
     */
    public function getReport(): string
    {
        if ($this->results === []) {
            return 'No results to display.';
        }

        helper('number');

        // Template
        $tpl = '<table>
			<thead>
				<tr>
					<td>Test</td>
					<td>Time</td>
					<td>Memory</td>
				</tr>
			</thead>
			<tbody>
				{rows}
			</tbody>
		</table>';

        $rows = '';

        foreach ($this->results as $name => $result) {
            $memory = number_to_size($result['memory'], 4);

            $rows .= "<tr>
				<td>{$name}</td>
				<td>" . number_format($result['time'], 4) . "</td>
				<td>{$memory}</td>
			</tr>";
        }

        $tpl = str_replace('{rows}', $rows, $tpl);

        return $tpl . '<br/>';
    }
}
