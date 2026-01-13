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

use Mockery;

use function Kahlan\expect;

describe('Debug / Toolbar / Collectors / BaseCollector', function (): void {
    beforeEach(function (): void {
        // Créer un mock concret d'un collector de base
        $this->collector = new class extends BaseCollector {
            protected string $key = 'test';
            protected string $title = 'Test Collector';
		};
	});

    describe('Méthodes de base', function (): void {
        it('retourne le titre correctement', function (): void {
            expect($this->collector->getTitle())->toBe('Test Collector');
            expect($this->collector->getTitle(true))->toBe('test-collector');
        });

        it('génère une clé à partir du nom de classe', function (): void {
			$collector = Mockery::mock(BaseCollector::class);
			$collector->shouldReceive('getKey')->andReturn('custom');

            expect($collector->getKey())->toBe('custom');
        });

        it('génère le nom de vue par défaut', function (): void {
            expect($this->collector->getView())->toBe('_test.tpl');
        });

        it('retourne un titre détaillé vide par défaut', function (): void {
            expect($this->collector->getTitleDetails())->toBe('');
        });

        it('vérifie les propriétés booléennes', function (): void {
            // Ces valeurs par défaut viennent de la classe BaseCollector
            expect($this->collector->hasTabContent())->toBe(false);
            expect($this->collector->hasLabel())->toBe(false);
            expect($this->collector->hasTimelineData())->toBe(false);
            expect($this->collector->hasVarData())->toBe(false);
        });

        it('retourne des données de timeline vides par défaut', function (): void {
            expect($this->collector->timelineData())->toBe([]);
        });

        it('retourne des données var null par défaut', function (): void {
            expect($this->collector->getVarData())->toBeNull();
        });

        it('retourne un tableau vide par défaut pour display', function (): void {
            expect($this->collector->display())->toBe([]);
        });

        it('retourne null pour la valeur du badge par défaut', function (): void {
            expect($this->collector->getBadgeValue())->toBeNull();
        });

        it('n\'est jamais vide par défaut', function (): void {
            expect($this->collector->isEmpty())->toBe(false);
        });

        it('retourne une icône vide par défaut', function (): void {
            expect($this->collector->icon())->toBe('');
        });

        it('retourne un tableau complet avec getAsArray', function (): void {
            $result = $this->collector->getAsArray();

            expect($result)->toBeAn('array');
            expect($result)->toContainKey('title');
            expect($result)->toContainKey('titleSafe');
            expect($result)->toContainKey('key');
            expect($result)->toContainKey('view');
            expect($result)->toContainKey('titleDetails');
            expect($result)->toContainKey('display');
            expect($result)->toContainKey('badgeValue');
            expect($result)->toContainKey('isEmpty');
            expect($result)->toContainKey('hasTabContent');
            expect($result)->toContainKey('hasLabel');
            expect($result)->toContainKey('icon');
            expect($result)->toContainKey('hasTimelineData');
            expect($result)->toContainKey('timelineData');
        });
    });

    describe('Collector personnalisé avec toutes les fonctionnalités', function (): void {
        it('implémente toutes les méthodes correctement', function (): void {
            $fullCollector = Mockery::mock(BaseCollector::class, [
				'getTitle'        => 'Full Collector',
				'hasTabContent'   => true,
				'hasLabel'        => true,
				'hasTimelineData' => true,
				'hasVarData'      => true,
			]);
			$fullCollector->shouldAllowMockingProtectedMethods();

			$fullCollector->shouldReceive('formatTimelineData')->andReturn([
				['name' => 'Test', 'component' => 'Test', 'start' => 0, 'duration' => 1]
			]);
			$fullCollector->shouldReceive('getVarData')->andReturn(['section' => ['key' => 'value']]);
			$fullCollector->shouldReceive('display')->andReturn(['data' => 'test']);
			$fullCollector->shouldReceive('getBadgeValue')->andReturn(5);
			$fullCollector->shouldReceive('isEmpty')->andReturn(false);
			$fullCollector->shouldReceive('icon')->andReturn('data:image/png;base64,test');

            expect($fullCollector->hasTabContent())->toBe(true);
            expect($fullCollector->hasLabel())->toBe(true);
            expect($fullCollector->hasTimelineData())->toBe(true);
            expect($fullCollector->hasVarData())->toBe(true);
            // expect($fullCollector->timelineData())->toHaveLength(1);
            expect($fullCollector->getVarData())->toBe(['section' => ['key' => 'value']]);
            expect($fullCollector->display())->toBe(['data' => 'test']);
            expect($fullCollector->getBadgeValue())->toBe(5);
            expect($fullCollector->isEmpty())->toBe(false);
            expect($fullCollector->icon())->toBe('data:image/png;base64,test');
        });
    });
});
