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

use function Kahlan\expect;

describe('Debug / Toolbar / Collectors / FilesCollector', function (): void {
    beforeEach(function (): void {
        $this->collector = new FilesCollector();
    });

    it('a les bonnes propriétés de base', function (): void {
        expect($this->collector->getTitle())->toBe('Fichiers');
        expect($this->collector->hasTimelineData())->toBe(false);
        expect($this->collector->hasTabContent())->toBe(true);
        expect($this->collector->hasVarData())->toBe(false);
        expect($this->collector->hasLabel())->toBe(false);
    });

    it('retourne le nombre de fichiers dans le titre détaillé', function (): void {
        $collector = new class extends FilesCollector {
			protected function includeFiles(): array
			{
				return ['/path/file1.php', '/path/file2.php', '/path/file3.php'];
			}
		};

        expect($collector->getTitleDetails())->toBe('( 3 )');
    });

    it('affiche les fichiers catégorisés correctement', function (): void {
        $collector = new class extends FilesCollector {
			protected function includeFiles(): array
			{
				// Simuler des fichiers inclus
				return [
					SYST_PATH . 'Class.php',
					SYST_PATH . 'Helper.php',
					VENDOR_PATH . 'blitz-php' . DS . 'Debug/Collector.php',
					VENDOR_PATH . 'composer/autoload.php',
					APP_PATH . 'Controllers/Home.php',
					APP_PATH . 'Models/User.php',
				];
			}
		};

        $display = $collector->display();

        expect($display)->toBeAn('array');
        expect($display)->toContainKey('coreFiles');
        expect($display)->toContainKey('blitzFiles');
        expect($display)->toContainKey('userFiles');
        expect($display)->toContainKey('vendorFiles');
        expect($display)->toContainKey('countCoreFiles');
        expect($display)->toContainKey('countBlitzFiles');
        expect($display)->toContainKey('countUserFiles');
        expect($display)->toContainKey('countVendorFiles');

        // Vérifier les comptes
        expect($display['countCoreFiles'])->toBe(2);
        expect($display['countBlitzFiles'])->toBe(1);
        expect($display['countUserFiles'])->toBe(2);
        expect($display['countVendorFiles'])->toBe(1);

        // Vérifier que les fichiers sont triés
        expect($display['coreFiles'][0]['name'])->toBe('Class.php');
        expect($display['coreFiles'][1]['name'])->toBe('Helper.php');
    });

    it('retourne la valeur correcte du badge', function (): void {
        $collector = new class extends FilesCollector {
			protected function includeFiles(): array
			{
				// Simuler des fichiers inclus
				return ['file1.php', 'file2.php', 'file3.php', 'file4.php'];
			}
		};

        expect($collector->getBadgeValue())->toBe(4);
    });

    it('retourne une icône encodée en base64', function (): void {
        $icon = $this->collector->icon();

        expect($icon)->toBeA('string');
        expect($icon)->toMatch('/^data:image\/png;base64,/');
    });

    it('retourne un tableau complet avec getAsArray', function (): void {
        $collector = new class extends FilesCollector {
			protected function includeFiles(): array
			{
				// Simuler des fichiers inclus
				return [];
			}
		};

        $array = $collector->getAsArray();

        expect($array)->toBeAn('array');
        expect($array['title'])->toBe('Fichiers');
        expect($array['titleDetails'])->toBe('( 0 )');
        expect($array['hasTabContent'])->toBe(true);
        expect($array['hasTimelineData'])->toBe(false);
        expect($array['badgeValue'])->toBe(0);
    });
});
