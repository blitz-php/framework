<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Debug\Toolbar\Collectors;

use BlitzPHP\Container\Services;
use BlitzPHP\Debug\Timer;
use Mockery;

use function Kahlan\expect;

describe('Debug / Toolbar / Collectors / TimersCollector', function (): void {
    beforeEach(function (): void {
        // Mock du service timer
        $this->mockTimer = Mockery::mock(Timer::class);

        Services::injectMock('timer', $this->mockTimer);

        $this->collector = new TimersCollector();
    });

    afterEach(function (): void {
        Services::resetSingle('timer');
    });

    it('a les bonnes propriétés de base', function (): void {
        expect($this->collector->getTitle())->toBe('Timers');
        expect($this->collector->hasTimelineData())->toBe(true);
        expect($this->collector->hasTabContent())->toBe(false);
        expect($this->collector->hasVarData())->toBe(false);
        expect($this->collector->hasLabel())->toBe(false);
    });

    it('formate les données de timeline correctement', function (): void {
        $mockTimers = [
            'total_execution' => [
                'start' => 0.0,
                'end'   => 1.0,
                'duration' => 1.0,
            ],
            'database_query' => [
                'start' => 0.1,
                'end'   => 0.15,
                'duration' => 0.05,
            ],
            'view_rendering' => [
                'start' => 0.2,
                'end'   => 0.25,
                'duration' => 0.05,
            ],
        ];
		$this->mockTimer->shouldReceive('getTimers')->andReturn($mockTimers);

        $timelineData = $this->collector->timelineData();

        // total_execution devrait être exclu
        expect($timelineData)->toHaveLength(2);

	   // Vérifier le premier timer
        expect($timelineData[0])->toContainKey('name');
        expect($timelineData[0])->toContainKey('component');
        expect($timelineData[0])->toContainKey('start');
        expect($timelineData[0])->toContainKey('duration');
        expect($timelineData[0]['name'])->toBe('Database Query');
        expect($timelineData[0]['component'])->toBe('Timer');
        expect($timelineData[0]['duration'])->toBeCloseTo(0.05, 2);

        // Vérifier le formatage du nom
        expect($timelineData[1]['name'])->toBe('View Rendering');
    });

    it('exclue toujours total_execution des données de timeline', function (): void {
        $mockTimers = [
            'total_execution' => ['start' => 0, 'end' => 1],
            'other_timer' => ['start' => 0.1, 'end' => 0.2],
        ];
		$this->mockTimer->shouldReceive('getTimers')->with(6)->andReturn($mockTimers);

        $timelineData = $this->collector->timelineData();

        expect($timelineData)->toHaveLength(1);
        expect($timelineData[0]['name'])->toBe('Other Timer');
    });

    it('gère les noms de timer avec différents formats', function (): void {
        $mockTimers = [
            'timer_with_underscore' => ['start' => 0, 'end' => 0.1],
            'TimerWithCaps' => ['start' => 0.1, 'end' => 0.2],
            'timer-with-dash' => ['start' => 0.2, 'end' => 0.3],
        ];
		$this->mockTimer->shouldReceive('getTimers')->with(6)->andReturn($mockTimers);

        $timelineData = $this->collector->timelineData();

        expect($timelineData[0]['name'])->toBe('Timer With Underscore');
        expect($timelineData[1]['name'])->toBe('TimerWithCaps'); // Note: ucwords ne gère pas les majuscules au milieu
        expect($timelineData[2]['name'])->toBe('Timer-with-dash');
    });

    it('retourne un tableau vide quand il n\'y a pas de timers', function (): void {
        $this->mockTimer->shouldReceive('getTimers')->with(6)->andReturn([]);

        $timelineData = $this->collector->timelineData();

        expect($timelineData)->toBe([]);
    });

    it('ne contient que des timers valides', function (): void {
        $mockTimers = [
            'timer1' => ['start' => 0, 'end' => 0.1],
            'timer2' => ['start' => 0.1], // Pas de 'end'
            'timer3' => ['end' => 0.3],   // Pas de 'start'
            'timer4' => ['start' => 0.3, 'end' => 0.4],
        ];
		$this->mockTimer->shouldReceive('getTimers')->with(6)->andReturn($mockTimers);

        $timelineData = $this->collector->timelineData();

        // Seuls les timers complets devraient être inclus
        expect($timelineData)->toHaveLength(2);
        expect($timelineData[0]['name'])->toBe('Timer1');
        expect($timelineData[1]['name'])->toBe('Timer4');
    });

    it('retourne un tableau complet avec getAsArray', function (): void {
		$this->mockTimer->shouldReceive('getTimers')->with(6)->andReturn([]);

        $array = $this->collector->getAsArray();

        expect($array)->toBeAn('array');
        expect($array['title'])->toBe('Timers');
        expect($array['hasTabContent'])->toBe(false);
        expect($array['hasTimelineData'])->toBe(true);
        expect($array['timelineData'])->toBe([]);
    });
});
