<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Contracts\Event\EventInterface;

use function Kahlan\expect;

describe('Events / Event', function (): void {
    beforeAll(function (): void {
		$this->eventManager = service('event');
		$this->eventManager->clearListeners();
    });

	afterEach(function (): void {
		$this->eventManager->clearListeners();
	});

	describe('Listeners', function (): void {
		it('Les callbacks sont bien enregistrés', function (): void {
			$callback1 = static function (): void {
			};
			$callback2 = static function (): void {
			};

			$this->eventManager->on('foo', $callback1);
			$this->eventManager->on('foo', $callback2);

			expect($this->eventManager->getListeners('foo')[0])->toBe([$callback1, $callback2]);
		});

		it('clearListeners', function (): void {
			$callback1 = static function (): void {
			};
			$callback2 = static function (): void {
			};
			$callback3 = static function (): void {
			};

			$this->eventManager->on('foo', $callback1);
			$this->eventManager->on('foo', $callback3);
			$this->eventManager->on('bar', $callback2);
			$this->eventManager->on('baz', $callback2);

			expect($this->eventManager->getListeners())->toBe([
				'foo' => [[$callback1, $callback3]],
				'bar' => [[$callback2]],
				'baz' => [[$callback2]],
			]);

			$this->eventManager->clearListeners('foo');

			expect($this->eventManager->getListeners())->toBe([
				'bar' => [[$callback2]],
				'baz' => [[$callback2]],
			]);

			$this->eventManager->clearListeners();

			expect($this->eventManager->getListeners())->toBe([]);
		});
	});

	describe('Execution', function(): void {
		it('Execute un event', function (): void {
			$result = null;
			$this->eventManager->on('foo', static function (EventInterface $event) use (&$result): void {
				$result = $event->getTarget();
			});

			$this->eventManager->emit('foo', 'bar');

			expect($result)->toBe('bar');
		});

		it('Execute un event avec une classe callable', function (): void {
			$box = new class () {
				public string $logged;

				public function hold($event): void
				{
					$this->logged = $event->getTarget();
				}
			};

			$this->eventManager->on('foo', $box->hold(...));

			$this->eventManager->emit('foo', 'bar');

			expect($box->logged)->toBe('bar');
		});
	});

	describe('Arret de l\'execution', function (): void {
		it('Arrete l\'execution des autres listeners lorsque FALSE est renvoyé', function (): void {
			$result = null;

			$this->eventManager->on('foo', static function () use (&$result): bool {
				$result = 1;

				return false;
			});
			$this->eventManager->on('foo', static function () use (&$result): void {
				$result = 2;
			});

			$this->eventManager->emit('foo');

			expect($result)->toBe(1);
		});

		it('Arrete l\'execution des autres listeners lorsque stopPropagation est utilisé', function (): void {
			$result = null;

			$this->eventManager->on('foo', static function (EventInterface $event) use (&$result): void {
				$result = 1;

				$event->stopPropagation();
			});
			$this->eventManager->on('foo', static function () use (&$result): void {
				$result = 2;
			});

			$this->eventManager->emit('foo');

			expect($result)->toBe(1);
		});
	});

	describe('Priorite', function (): void {
		it('Priorite', function (): void {
			$result = 0;

			$this->eventManager->on('foo', static function () use (&$result): bool {
				$result = 1;

				return false;
			}, EventInterface::PRIORITY_NORMAL);

			// Ceci doit etre lancer en premier car elle a une priorite elevee
			$this->eventManager->on('foo', static function () use (&$result): bool {
				$result = 2;

				return false;
			}, EventInterface::PRIORITY_HIGH);

			$this->eventManager->emit('foo');

			expect($result)->toBe(2);
		});

		it('Priorite multiple', function (): void {
			$result = [];

			$this->eventManager->on('foo', static function () use (&$result): void {
				$result[] = 'a';
			}, EventInterface::PRIORITY_NORMAL);

			$this->eventManager->on('foo', static function () use (&$result): void {
				$result[] = 'b';
			}, EventInterface::PRIORITY_LOW);

			$this->eventManager->on('foo', static function () use (&$result): void {
				$result[] = 'c';
			}, EventInterface::PRIORITY_HIGH);

			$this->eventManager->on('foo', static function () use (&$result): void {
				$result[] = 'd';
			}, 75);

			$this->eventManager->emit('foo');

			expect($result)->toBe(['c', 'd', 'a', 'b']);
		});
	});

	describe('Retrait de listener ', function (): void {
		it('Le retrait de listener fonctionne', function (): void {
			$result = false;

			$callback = static function () use (&$result): void {
				$result = true;
			};

        	$this->eventManager->on('foo', $callback);

       		$this->eventManager->emit('foo');
        	expect($result)->toBeTruthy();

        	$result = false;
			expect($this->eventManager->off('foo', $callback))->toBeTruthy();

			$this->eventManager->emit('foo');
		 	expect($result)->toBeFalsy();
		});

		it('Retire le listener une seule fois', function (): void {
			$result = false;

			$callback = static function () use (&$result): void {
				$result = true;
			};

        	$this->eventManager->on('foo', $callback);

       		$this->eventManager->emit('foo');
        	expect($result)->toBeTruthy();

        	$result = false;
			expect($this->eventManager->off('foo', $callback))->toBeTruthy();
			expect($this->eventManager->off('foo', $callback))->toBeFalsy();

			$this->eventManager->emit('foo');
		 	expect($result)->toBeFalsy();
		});

		it('Retrait d\'un listener inconnue', function (): void {
			$result = false;

			$callback = static function () use (&$result): void {
				$result = true;
			};

        	$this->eventManager->on('foo', $callback);

       		$this->eventManager->emit('foo');
        	expect($result)->toBeTruthy();

        	$result = false;
			expect($this->eventManager->off('bar', $callback))->toBeFalsy();

			$this->eventManager->emit('foo');
		 	expect($result)->toBeTruthy();
		});
	});
});
