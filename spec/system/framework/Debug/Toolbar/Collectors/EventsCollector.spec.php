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

use BlitzPHP\Event\EventManager;
use BlitzPHP\Utilities\Reflection\ReflectionClass;

use function Kahlan\expect;

describe('Debug / Toolbar / Collectors / EventsCollector', function (): void {
    beforeEach(function (): void {
        $this->collector = new EventsCollector();
    });

    it('a les bonnes propriétés de base', function (): void {
        expect($this->collector->getTitle())->toBe('Evénements');
        expect($this->collector->hasTimelineData())->toBe(true);
        expect($this->collector->hasTabContent())->toBe(true);
        expect($this->collector->hasVarData())->toBe(false);
        expect($this->collector->hasLabel())->toBe(false);
    });

    it('formate les données de timeline correctement', function (): void {
        // Mock des logs de performance
        $mockLogs = [
            [
                'event' => 'pre_system',
                'start' => 0.0,
                'end'   => 0.001,
            ],
            [
                'event' => 'post_controller',
                'start' => 0.1,
                'end'   => 0.102,
            ],
        ];

		$reflection = new ReflectionClass(EventManager::class);

		$reflection->setValue('performanceLog', $mockLogs);

        $timelineData = $this->collector->timelineData();

        expect($timelineData)->toHaveLength(2);
        expect($timelineData[0])->toContainKey('name');
        expect($timelineData[0])->toContainKey('component');
        expect($timelineData[0])->toContainKey('start');
        expect($timelineData[0])->toContainKey('duration');
        expect($timelineData[0]['name'])->toBe('Evénement: pre_system');
        expect($timelineData[0]['component'])->toBe('Events');
        expect($timelineData[0]['duration'])->toBe(0.001);

		$reflection->setValue('performanceLog', []);
    });

    it('affiche les données correctement formatées', function (): void {
        $mockLogs = [
            [
                'event' => 'pre_system',
                'start' => 0.0,
                'end'   => 0.001,
            ],
            [
                'event' => 'pre_system',
                'start' => 0.002,
                'end'   => 0.003,
            ],
            [
                'event' => 'post_controller',
                'start' => 0.1,
                'end'   => 0.102,
            ],
        ];

		$reflection = new ReflectionClass(EventManager::class);

		$reflection->setValue('performanceLog', $mockLogs);

        $display = $this->collector->display();

        expect($display)->toBeAn('array');
        expect($display)->toContainKey('events');
        expect($display['events'])->toContainKey('pre_system');
        expect($display['events'])->toContainKey('post_controller');

        // Vérifier l'agrégation des données
        expect($display['events']['pre_system']['count'])->toBe(2);
        expect($display['events']['pre_system']['duration'])->toBe('2.00'); // (0.001 + 0.001) * 1000
        expect($display['events']['post_controller']['count'])->toBe(1);
        expect($display['events']['post_controller']['duration'])->toBe('2.00'); // 0.002 * 1000

		$reflection->setValue('performanceLog', []);
    });

    it('retourne la valeur correcte du badge', function (): void {
        $mockLogs = [
            ['event' => 'event1', 'start' => 0, 'end' => 1],
            ['event' => 'event2', 'start' => 1, 'end' => 2],
            ['event' => 'event3', 'start' => 2, 'end' => 3],
        ];

        $reflection = new ReflectionClass(EventManager::class);

		$reflection->setValue('performanceLog', $mockLogs);

        expect($this->collector->getBadgeValue())->toBe(3);

		$reflection->setValue('performanceLog', []);
    });

    it('retourne 0 pour le badge quand il n\'y a pas d\'événements', function (): void {
        $reflection = new ReflectionClass(EventManager::class);

		$reflection->setValue('performanceLog', []);

        expect($this->collector->getBadgeValue())->toBe(0);
    });

    it('retourne une icône encodée en base64', function (): void {
        $icon = $this->collector->icon();

        expect($icon)->toBeA('string');
        expect($icon)->toMatch('/^data:image\/png;base64,/');
    });

    it('retourne un tableau complet avec getAsArray', function (): void {
		$reflection = new ReflectionClass(EventManager::class);

		$reflection->setValue('performanceLog', []);

        $array = $this->collector->getAsArray();

        expect($array)->toBeAn('array');
        expect($array['title'])->toBe('Evénements');
        expect($array['hasTabContent'])->toBe(true);
        expect($array['hasTimelineData'])->toBe(true);
        expect($array['timelineData'])->toBe([]);
        expect($array['display'])->toBe(['events' => []]);
    });
});
