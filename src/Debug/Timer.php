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

use RuntimeException;

class Timer
{
    /**
     * Liste de toutes les minuteries.
     *
     * @var array
     */
    protected $timers = [];

    /**
     * Démarre une minuterie en cours d'exécution.
     *
     * Plusieurs appels peuvent être effectués vers cette méthode afin que plusieurs
     * les points d'exécution peuvent être mesurés.
     *
     * @param string $name Le nom de cette minuterie.
     * @param float  $time Permet à l'utilisateur de fournir du temps.
     */
    public function start(string $name, ?float $time = null): self
    {
        $this->timers[strtolower($name)] = [
            'start' => ! empty($time) ? $time : microtime(true),
            'end'   => null,
        ];

        return $this;
    }

    /**
     * Arrête une minuterie en cours d'exécution.
     *
     * Si le minuteur n'est pas arrêté avant l'appel de la méthode timers(),
     * il sera automatiquement arrêté à ce point.
     *
     * @param string $name Le nom de cette minuterie.
     */
    public function stop(string $name): self
    {
        $name = strtolower($name);

        if (empty($this->timers[$name])) {
            throw new RuntimeException('Cannot stop timer: invalid name given.');
        }

        $this->timers[$name]['end'] = microtime(true);

        return $this;
    }

    /**
     * Renvoie la durée d'une minuterie enregistrée.
     *
     * @param string $name     Le nom de la minuterie.
     * @param int    $decimals Nombre de décimales.
     *
     * @return float|null Renvoie null si timer existe sous ce nom.
     *                    Renvoie un flottant représentant le nombre de
     *                    secondes se sont écoulées pendant que cette minuterie fonctionnait.
     */
    public function getElapsedTime(string $name, int $decimals = 4): ?float
    {
        $name = strtolower($name);

        if (empty($this->timers[$name])) {
            return null;
        }

        return $this->getDuration($this->timers[$name], $decimals);
    }

    /**
     * Renvoie le tableau des minuteurs, avec la durée pré-calculée pour vous.
     */
    public function getTimers(int $decimals = 4): array
    {
        $timers = $this->timers;

        foreach ($timers as &$timer) {
            $timer['duration'] = $this->getDuration($timer, $decimals);
        }

        return $timers;
    }

    /**
     * Vérifie si oui ou non un minuteurs avec le nom spécifié existe.
     */
    public function has(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->timers);
    }

    /**
     * Exécute la fonction callable et mesure son temps d'exécution.
     * Renvoie sa valeur de retour, le cas échéant.
     *
     * @param string            $name     Le nom du minuteur.
     * @param callable(): mixed $callable Fonction callable à exécuter.
     */
    public function record(string $name, callable $callable):mixed
    {
        $this->start($name);
        $returnValue = $callable();
        $this->stop($name);

        return $returnValue;
    }

    /**
     * Renvoie la durée d'une minuterie enregistrée.
     *
     * @return float Renvoie un nombre flottant représentant le nombre de
     *               secondes qui se sont écoulées pendant que cette minuterie fonctionnait.
     */
    private function getDuration(array $timer, int $decimals = 4): float
    {
        if (empty($timer['end'])) {
            $timer['end'] = microtime(true);
        }

        return (float) number_format($timer['end'] - $timer['start'], $decimals);
    }
}
