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
use BlitzPHP\Validation\ErrorBag;
use BlitzPHP\View\View;
use Kahlan\Plugin\Double;
use Mockery;

use function Kahlan\expect;

describe('Debug / Toolbar / Collectors / ViewsCollector', function (): void {
    beforeEach(function (): void {
        // Mock du service viewer
        $this->mockViewer = Mockery::mock(View::class);

        Services::injectMock('viewer', $this->mockViewer);

        $this->collector = new ViewsCollector();
    });

    afterEach(function (): void {
        Services::resetSingle('viewer');
    });

    it('a les bonnes propriétés de base', function (): void {
        expect($this->collector->getTitle())->toBe('Vues');
        expect($this->collector->hasTimelineData())->toBe(true);
        expect($this->collector->hasTabContent())->toBe(false);
        expect($this->collector->hasVarData())->toBe(true);
        expect($this->collector->hasLabel())->toBe(true);
    });

    it('formate les données de timeline correctement', function (): void {
        $mockPerformanceData = [
            [
                'view' => 'welcome.php',
                'start' => 0.1,
                'end'   => 0.15,
            ],
            [
                'view' => 'header.php',
                'start' => 0.05,
                'end'   => 0.08,
            ],
        ];
		$this->mockViewer->shouldReceive('getPerformanceData')->andReturn($mockPerformanceData);

        $timelineData = $this->collector->timelineData();

        expect($timelineData)->toHaveLength(2);

        // Vérifier le premier élément
        expect($timelineData[0])->toContainKey('name');
        expect($timelineData[0])->toContainKey('component');
        expect($timelineData[0])->toContainKey('start');
        expect($timelineData[0])->toContainKey('duration');
        expect($timelineData[0]['name'])->toBe('Vue: welcome.php');
        expect($timelineData[0]['component'])->toBe('Views');
        expect($timelineData[0]['duration'])->toBeCloseTo(0.05, 2);
    });

    it('retourne les données de vue sans ErrorBag', function (): void {
        $mockViewData = [
            'title' => 'Welcome',
            'user' => ['name' => 'John'],
            'errors' => Double::instance(['extends' => ErrorBag::class]),
            'messages' => ['success' => 'Operation completed'],
        ];
		$this->mockViewer->shouldReceive('getData')->andReturn($mockViewData);

		$collector = new ViewsCollector($this->mockViewer);
        $varData = $collector->getVarData();

        expect($varData)->toBeAn('array');
        expect($varData)->toContainKey('Données de la vues');
        expect($varData['Données de la vues'])->toContainKey('title');
        expect($varData['Données de la vues'])->toContainKey('user');
        expect($varData['Données de la vues'])->toContainKey('messages');
        expect($varData['Données de la vues'])->not->toContainKey('errors');
    });

    it('retourne un tableau vide quand toutes les données sont des ErrorBag', function (): void {
        $mockViewData = [
            'errors' => Mockery::mock(ErrorBag::class),
            'validation_errors' => Mockery::mock(ErrorBag::class),
        ];
		$this->mockViewer->shouldReceive('getData')->andReturn($mockViewData);

		$collector = new ViewsCollector($this->mockViewer);

        $varData = $collector->getVarData();

        expect($varData)->toBe(['Données de la vues' => []]);
    });

    it('retourne la valeur correcte du badge', function (): void {
        $mockPerformanceData = [
            ['view' => 'view1', 'start' => 0, 'end' => 1],
            ['view' => 'view2', 'start' => 1, 'end' => 2],
            ['view' => 'view3', 'start' => 2, 'end' => 3],
        ];
		$this->mockViewer->shouldReceive('getPerformanceData')->andReturn($mockPerformanceData);

		$collector = new ViewsCollector($this->mockViewer);

        expect($collector->getBadgeValue())->toBe(3);
    });

    it('retourne 0 pour le badge quand il n\'y a pas de vues', function (): void {
        $this->mockViewer->shouldReceive('getPerformanceData')->andReturn([]);

		$collector = new ViewsCollector($this->mockViewer);

        expect($collector->getBadgeValue())->toBe(0);
    });

    it('retourne une icône encodée en base64', function (): void {
        $icon = $this->collector->icon();

        expect($icon)->toBeA('string');
        expect($icon)->toMatch('/^data:image\/png;base64,/');
    });

    it('retourne un tableau complet avec getAsArray', function (): void {
		$this->mockViewer->shouldReceive('getPerformanceData')->andReturn([]);
		$this->mockViewer->shouldReceive('getData')->andReturn([]);

		$collector = new ViewsCollector($this->mockViewer);

        $array = $collector->getAsArray();

        expect($array)->toBeAn('array');
        expect($array['title'])->toBe('Vues');
        expect($array['hasTabContent'])->toBe(false);
        expect($array['hasTimelineData'])->toBe(true);
        expect($array['hasLabel'])->toBe(true);
        expect($array['timelineData'])->toBe([]);
        expect($array['badgeValue'])->toBe(0);
        expect($array['icon'])->toMatch('/^data:image\/png;base64,/');
    });

    it('filtre correctement les différentes instances d\'ErrorBag', function (): void {
		$mockErrorBag1 = Mockery::mock(ErrorBag::class);
		$mockErrorBag2 = Mockery::mock(ErrorBag::class);

        $mockViewData = [
            'errors' => $mockErrorBag1,
            'data' => 'normal data',
            'more_errors' => $mockErrorBag2,
            'array_data' => ['key' => 'value'],
        ];

		$this->mockViewer->shouldReceive('getData')->andReturn($mockViewData);

		$collector = new ViewsCollector($this->mockViewer);

        $varData = $collector->getVarData();

        expect($varData['Données de la vues'])->toContainKey('data');
        expect($varData['Données de la vues'])->toContainKey('array_data');
        expect($varData['Données de la vues'])->not->toContainKey('errors');
        expect($varData['Données de la vues'])->not->toContainKey('more_errors');
    });
});
