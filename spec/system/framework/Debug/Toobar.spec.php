<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Debug\Toolbar;
use BlitzPHP\Spec\Utilities\NativeHeadersStack;

use function Kahlan\expect;

describe('Toolbar', function(): void {
    beforeAll(function(): void {
        // Chargez la simulation une seule fois pour toute la classe de test.
        require_once APP_PATH . 'Mock/MockNativeHeaders.php';

		$this->request = null;
		$this->response = null;
    });

    beforeEach(function(): void {
        NativeHeadersStack::reset();
        is_cli(false);

		$this->config = (object) config('toolbar');
		$this->stats = [
			'startTime' => microtime(true),
			'totalTime' => 0.05,
		];
	});

    afterEach(function(): void {
        // Restaurer l'état is_cli
        is_cli(true);
    });

    describe('prepare', function(): void {
        it('doit respecter disable_on_headers', function(): void {
			$this->config->disable_onHeaders = ['HX-Request' => 'true'];

			$this->request  = single_service('request')->withHeader('HX-Request', 'true');
			$this->response = single_service('response')
				->withHeader('Content-Type', 'text/html')
				->withStringBody('<html><body>Content</body></html>');

            $toolbar = new Toolbar($this->config);
            $response = $toolbar->prepare($this->stats, $this->request, $this->response);

            expect($response->hasHeader('Debugbar-Time'))->toBeTruthy();
            expect((string) $response->getBody())->not->toContain('id="debugbar_loader"');
        });

        it("devrait s'injecter normalement sans en-tête ignoré", function(): void {
            $this->config->disable_onHeaders = ['HX-Request' => 'true'];

            $this->request = single_service('request');
            $this->response = single_service('response')
				->withHeader('Content-Type', 'text/html')
				->withStringBody('<html><body>Content</body></html>');

            $toolbar = new Toolbar($this->config);
            $response = $toolbar->prepare($this->stats, $this->request, $this->response);

            expect((string) $response->getBody())->toContain('id="debugbar_loader"');
        });

        it('doit avorter si les en-têtes ont déjà été envoyés', function(): void {
            // En-têtes explicitement envoyés (par exemple, echo avant l'exécution)
            NativeHeadersStack::$headersSent = true;

            $this->request = single_service('request');
            $this->response = single_service('response')
				->withStringBody('<html><body>Content</body></html>');

            $toolbar = new Toolbar($this->config);
            $response = $toolbar->prepare($this->stats, $this->request, $this->response);

            // NE PAS injecter car nous ne pouvons pas modifier le corps en toute sécurité.
            expect((string) $response->getBody())->not->toContain('id="debugbar_loader"');
        });

        it('doit avorter si le type de contenu natif n\'est pas HTML', function(): void {
            // Une bibliothèque (comme Dompdf) définit directement un en-tête PDF.
            NativeHeadersStack::push('Content-Type: application/pdf');

            $this->request = single_service('request');
            $this->response = single_service('response')
				->withStringBody('<html><body>Raw PDF Data</body></html>'); // Même si le corps ressemble à du HTML (avant rendu), l'en-tête indique PDF.

            $toolbar = new Toolbar($this->config);
            $response = $toolbar->prepare($this->stats, $this->request, $this->response);

            // NE PAS injecter dans du contenu non HTML
            expect((string) $response->getBody())->not->toContain('id="debugbar_loader"');
        });

        it('doit être interrompu si le contenu natif est une pièce jointe', function(): void {
            // Téléchargement d'un fichier (même s'il s'agit d'un fichier HTML)
            NativeHeadersStack::$headers = [
                'Content-Type: text/html',
                'Content-Disposition: attachment; filename="report.html"',
            ];

            $this->request = single_service('request');
            $this->response = single_service('response')
				->withStringBody('<html><body>Downloadable Report</body></html>');

            $toolbar = new Toolbar($this->config);
            $response = $toolbar->prepare($this->stats, $this->request, $this->response);

            // NE PAS injecter dans les téléchargements
            expect((string) $response->getBody())->not->toContain('id="debugbar_loader"');
        });

        it('devrait fonctionner avec l\'en-tête HTML natif', function(): void {
            // Scénario standard où l'en-tête PHP est text/html
            NativeHeadersStack::push('Content-Type: text/html; charset=UTF-8');

            $this->request = single_service('request');
            $this->response = single_service('response')
				->withStringBody('<html><body>Valid Page</body></html>');

            $toolbar = new Toolbar($this->config);
            $response = $toolbar->prepare($this->stats, $this->request, $this->response);

            // Doit injecter normalement
            expect((string) $response->getBody())->toContain('id="debugbar_loader"');
        });
    });
});
