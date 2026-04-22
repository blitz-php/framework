<?php

use BlitzPHP\Spec\Mock\MockCache;
use BlitzPHP\View\Components\ComponentLoader;
use BlitzPHP\View\Components\Slot;
use Spec\BlitzPHP\App\Views\Components\GreetingComponent;
use Spec\BlitzPHP\App\Views\Components\SlotTestComponent;
use Spec\BlitzPHP\App\Views\Components\SimpleNoSlotComponent;

use function Kahlan\expect;

describe('Views / Component / Slot', function (): void {
	beforeEach(function () {
		$this->cache = new MockCache();
		$this->cache->init();
		$this->componentLoader = new ComponentLoader($this->cache);
	});

	afterEach(function () {
		$this->cache->clear();
	});

	describe('Slots dans les composants', function () {
		it('extrait le slot par défaut et les slots nommés depuis un callback', function () {
			$html = component(SlotTestComponent::class, ['variant' => 'info'], function () {
				return <<<'HTML'
					<x-slot name="header">
						<h1>Titre important</h1>
					</x-slot>
					<p>Contenu principal du message.</p>
					<x-slot name="footer">
						<small>Pied de page</small>
					</x-slot>
					HTML;
			});

			expect($html)->toContain('alert-info');
			expect($html)->toContain('<div class="alert-header"><h1>Titre important</h1></div>');
			expect($html)->toContain('<div class="alert-body"><p>Contenu principal du message.</p></div>');
			expect($html)->toContain('<div class="alert-footer"><small>Pied de page</small></div>');
		});

		it('utilise le slot par défaut quand aucune balise x-slot', function () {
			$html = component(SlotTestComponent::class, ['variant' => 'success'], function () {
				return 'Un simple message sans balises spéciales.';
			});

			expect($html)->toContain('alert-success');
			expect($html)->toContain('<div class="alert-body">Un simple message sans balises spéciales.</div>');
			expect($html)->not->toContain('alert-header');
			expect($html)->not->toContain('alert-footer');
		});

		it('accepte les slots vides', function () {
			$html = component(SlotTestComponent::class, [], function () {
				return <<<'HTML'
					<x-slot name="header"></x-slot>
					<x-slot name="footer"></x-slot>
					HTML;
			});

			expect($html)->toContain('<div class="alert-header"></div>');
			expect($html)->toContain('<div class="alert-body"></div>');
			expect($html)->toContain('<div class="alert-footer"></div>');
		});

		it('préserve le HTML complexe à l’intérieur des slots', function () {
			$html = component(SlotTestComponent::class, [], function () {
				return <<<'HTML'
					<x-slot name="header">
						<ul><li>Item</li></ul>
					</x-slot>
					<div class="complex">
						<button>Click</button>
					</div>
					HTML;
			});

			expect($html)->toContain('<ul><li>Item</li></ul>');
			expect($html)->toContain("<div class=\"complex\">\n\t<button>Click</button>\n</div>");
		});

		it('permet d\'imbriquer des composants dans les slots', function () {
			$html = component(SlotTestComponent::class, [], function () {
				$greeting = component(GreetingComponent::class, 'greeting=Hi, name=Blitz PHP');

				return <<<HTML
					<x-slot name="header">{$greeting}</x-slot>
					HTML;
			});

			expect($html)->toContain('Hi Blitz PHP');
		});

		it('ne fournit pas les slots aux composants simples', function () {
			// Un composant simple ne doit pas recevoir les slots
			$result = component(SimpleNoSlotComponent::class, [], function () {
				return '<x-slot name="ignored">Ceci ne doit pas être utilisé</x-slot>';
			});

			expect($result)->toBe('No slots expected');
		});

		it('supporte la syntaxe avec $this->slot->start() dans les vues', function () {
			// Créer une vue temporaire qui utilise les méthodes de slot
			// (à adapter selon l’environnement de test)
			$viewContent = <<<'PHP'
				<?php $this->slot->start('header') ?>
					<h1>Slot manuel</h1>
				<?php $this->slot->stop() ?>
				<div>Contenu par défaut</div>
				PHP;

			// On doit simuler un rendu de vue avec NativeAdapter
			// Pour simplifier, on peut créer un composant contrôlé dont la vue utilise $this->slot
			// Ou alors on teste directement l’extraction via Slot::extractFromHtml()
		});

		it('extrait correctement les slots via Slot::extractFromHtml', function () {
			$html = <<<'HTML'
				<x-slot name="header">Header content</x-slot>
				<p>Default slot</p>
				<x-slot name="footer">Footer</x-slot>
				HTML;

			$extracted = Slot::extractFromHtml($html);

			expect($extracted['slots'])->toContainKeys('header');
			expect($extracted['slots']['header'])->toBe('Header content');
			expect($extracted['slots'])->toContainKeys('footer');
			expect($extracted['slots']['footer'])->toBe('Footer');
			expect(trim($extracted['default']))->toBe('<p>Default slot</p>');
		});

		it('gère les espaces et les sauts de ligne dans les balises x-slot', function () {
			$html = <<<'HTML'
				<x-slot
					name="header"
				>Header avec retour</x-slot>
				HTML;

			$extracted = Slot::extractFromHtml($html);
			expect($extracted['slots']['header'])->toBe('Header avec retour');
		});

		it('supprime les balises x-slot du contenu par défaut', function () {
			$html = <<<'HTML'
				<x-slot name="header">H</x-slot>
				<div>Default</div>
				<x-slot name="footer">F</x-slot>
				HTML;

			$extracted = Slot::extractFromHtml($html);
			expect($extracted['default'])->toBe('<div>Default</div>');
		});

		it('ne casse pas la rétrocompatibilité (appel sans callback)', function () {
			// Ancien appel avec TTL
			$result = component(GreetingComponent::class, 'greeting=Bonjour, name=Blitz PHP', 60);
			expect($result)->toBe('Bonjour Blitz PHP');
		});

		it('ne casse pas la rétrocompatibilité (appel avec tableau de paramètres)', function () {
			$result = component(GreetingComponent::class, ['greeting' => 'Salut', 'name' => 'Blitz PHP']);
			expect($result)->toBe('Salut Blitz PHP');
		});
	});

});
