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
use BlitzPHP\Event\Event;
use BlitzPHP\Event\EventManager;

use function Kahlan\expect;

describe('Events / Event', function (): void {
    beforeAll(function (): void {
        $this->eventManager = new EventManager();
    });

    afterEach(function (): void {
        $this->eventManager->clearListeners();
    });

    describe('Constructeur', function (): void {
        it('Crée un événement avec nom, cible et paramètres', function (): void {
            $event = new Event('test.event', 'target', ['param1' => 'value1']);

            expect($event->getName())->toBe('test.event');
            expect($event->getTarget())->toBe('target');
            expect($event->getParams())->toBe(['param1' => 'value1']);
        });

        it('Lève une exception si le nom est vide', function (): void {
            expect(static function (): void {
                new Event('');
            })->toThrow(new InvalidArgumentException('Le nom de l\'événement ne peut pas être vide.'));
        });
    });

    describe('Getters et Setters', function (): void {
        beforeEach(function (): void {
            $this->event = new Event('test.event', 'initial', ['p1' => 'v1']);
        });

        it('Modifie le nom de l\'événement', function (): void {
            $this->event->setName('new.name');
            expect($this->event->getName())->toBe('new.name');
        });

        it('Modifie la cible de l\'événement', function (): void {
            $this->event->setTarget('new.target');
            expect($this->event->getTarget())->toBe('new.target');
        });

        it('Modifie les paramètres', function (): void {
            $this->event->setParams(['p2' => 'v2']);
            expect($this->event->getParams())->toBe(['p2' => 'v2']);
        });

        it('Récupère un paramètre spécifique', function (): void {
            expect($this->event->getParam('p1'))->toBe('v1');
            expect($this->event->getParam('inexistant'))->toBe(null);
            expect($this->event->getParam('inexistant', 'defaut'))->toBe('defaut');
        });

        it('Vérifie si un paramètre existe', function (): void {
            expect($this->event->hasParam('p1'))->toBe(true);
            expect($this->event->hasParam('inexistant'))->toBe(false);
        });

        it('Définit un paramètre spécifique', function (): void {
            $this->event->setParam('nouveau', 'valeur');
            expect($this->event->getParam('nouveau'))->toBe('valeur');
        });

        it('Fusionne des paramètres', function (): void {
            $this->event->mergeParams(['p2' => 'v2', 'p3' => 'v3']);
            expect($this->event->getParams())->toBe([
                'p1' => 'v1',
                'p2' => 'v2',
                'p3' => 'v3'
            ]);
        });
    });

    describe('Immutabilité', function (): void {
        beforeEach(function (): void {
            $this->event = new Event('test.event', 'target', ['p' => 'v']);
        });

        it('Rend l\'événement immuable', function (): void {
			$this->event->setImmutable();

            expect(fn () => $this->event->setName('nouveau'))
				->toThrow(new RuntimeException('Impossible de modifier un événement immuable'));
        });

        it('Rend l\'événement mutable après immuabilité', function (): void {
            $this->event->setImmutable();
            $this->event->setImmutable(false);

            $this->event->setName('nouveau');
            expect($this->event->getName())->toBe('nouveau');
        });
    });

    describe('Propagation', function (): void {
        beforeEach(function (): void {
            $this->event = new Event('test.event');
        });

        it('Arrête la propagation', function (): void {
            $this->event->stopPropagation();
            expect($this->event->isPropagationStopped())->toBe(true);
        });

        it('Lève une exception si on tente de redémarrer une propagation arrêtée', function (): void {
            $this->event->stopPropagation();

            expect(fn() => $this->event->stopPropagation(false))
				->toThrow(new LogicException('Impossible de redémarrer la propagation d\'un événement arrêté'));
        });
    });

    describe('Sérialisation', function (): void {
        it('Convertit en tableau', function (): void {
            $event = new Event('test', 'target', ['p' => 'v']);
            $event->stopPropagation();

            $array = $event->toArray();

            expect($array)->toContainKey('name');
            expect($array)->toContainKey('target');
            expect($array)->toContainKey('params');
            expect($array)->toContainKey('isPropagationStopped');

            expect($array['name'])->toBe('test');
            expect($array['target'])->toBe('target');
            expect($array['params'])->toBe(['p' => 'v']);
            expect($array['isPropagationStopped'])->toBe(true);
        });

        it('Crée à partir d\'un tableau', function (): void {
            $data = [
                'name' => 'test',
                'target' => 'target',
                'params' => ['p' => 'v'],
                'isPropagationStopped' => true
            ];

            $event = Event::fromArray($data);

            expect($event->getName())->toBe('test');
            expect($event->getTarget())->toBe('target');
            expect($event->getParams())->toBe(['p' => 'v']);
            expect($event->isPropagationStopped())->toBe(true);
        });
    });

    describe('Clonage', function (): void {
        it('Clone les objets dans les paramètres', function (): void {
            $obj = new stdClass();
            $obj->value = 'test';

            $event = new Event('test', $obj, ['obj' => $obj]);
            $clone = clone $event;

            expect($clone->getTarget())->not->toBe($event->getTarget());
            expect($clone->getParam('obj'))->not->toBe($event->getParam('obj'));
        });
    });

    describe('Conversion en chaîne', function (): void {
        it('Convertit en représentation textuelle', function (): void {
            $event = new Event('test.event', 'target', ['param' => 'value']);

            $string = (string) $event;

            expect($string)->toContain('Event[name="test.event"');
            expect($string)->toContain('"param":"value"');
        });
    });

    describe('Listeners', function (): void {
        it('Les callbacks sont bien enregistrés', function (): void {
            $callback1 = static function (): void {};
            $callback2 = static function (): void {};

            $this->eventManager->on('foo', $callback1);
            $this->eventManager->on('foo', $callback2);

            expect($this->eventManager->getListeners('foo')[0])->toBe([$callback1, $callback2]);
        });

        it('clearListeners', function (): void {
            $callback1 = static function (): void {};
            $callback2 = static function (): void {};
            $callback3 = static function (): void {};

            $this->eventManager->on('foo', $callback1);
            $this->eventManager->on('foo', $callback3);
            $this->eventManager->on('bar', $callback2);
            $this->eventManager->on('baz', $callback2);

            expect($this->eventManager->getListeners())->toBe([
                'foo' => [0 => [$callback1, $callback3]],
                'bar' => [0 => [$callback2]],
                'baz' => [0 => [$callback2]],
            ]);

            $this->eventManager->clearListeners('foo');

            expect($this->eventManager->getListeners())->toBe([
                'bar' => [0 => [$callback2]],
                'baz' => [0 => [$callback2]],
            ]);

            $this->eventManager->clearListeners();

            expect(invade($this->eventManager)->listeners)->toBe(['*' => []]);
        });
    });

    describe('Execution', function(): void {
        it('Execute un event', function (): void {
            $result = null;
            $this->eventManager->on('foo', static function ($event) use (&$result): void {
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

            $this->eventManager->on('foo', static function ($event) use (&$result): void {
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
            }, 0);

            // Ceci doit etre lancer en premier car elle a une priorite elevee
            $this->eventManager->on('foo', static function () use (&$result): bool {
                $result = 2;
                return false;
            }, 10);

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
            }, 7);

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

    describe('Wildcards', function (): void {
        it('Les wildcards capturent tous les événements', function (): void {
            $results = [];

            $this->eventManager->on('*', static function ($event) use (&$results): void {
                $results[] = 'wildcard:' . $event->getName();
            });

            $this->eventManager->on('specific.event', static function ($event) use (&$results): void {
                $results[] = 'specific:' . $event->getName();
            });

            $this->eventManager->emit('specific.event');
            $this->eventManager->emit('another.event');

            expect($results)->toBe([
                'specific:specific.event',
                'wildcard:specific.event',
                'wildcard:another.event',
            ]);
        });
    });

    describe('Méthodes utilitaires', function (): void {
        it('hasListeners vérifie si des écouteurs existent', function (): void {
            expect($this->eventManager->hasListeners('foo'))->toBe(false);

            $this->eventManager->on('foo', static function (): void {});
            expect($this->eventManager->hasListeners('foo'))->toBe(true);
        });

        it('countListeners compte les écouteurs', function (): void {
            expect($this->eventManager->countListeners('foo'))->toBe(0);

            $this->eventManager->on('foo', static function (): void {});
            $this->eventManager->on('foo', static function (): void {}, 1);

            expect($this->eventManager->countListeners('foo'))->toBe(2);
        });

        it('addMiddleware ajoute des middlewares', function (): void {
            $preExecuted = false;
            $postExecuted = false;

            $this->eventManager->addMiddleware('foo', static function () use (&$preExecuted): void {
                $preExecuted = true;
            }, 'pre');

            $this->eventManager->addMiddleware('foo', static function () use (&$postExecuted): void {
                $postExecuted = true;
            }, 'post');

            $this->eventManager->on('foo', static function (): void {});

            $this->eventManager->emit('foo');
            $this->eventManager->emit('pre.foo');
            $this->eventManager->emit('post.foo');

            expect($preExecuted)->toBe(true);
            expect($postExecuted)->toBe(true);
        });
    });

    describe('Gestion des erreurs', function (): void {
        it('Continue après une erreur dans un écouteur', function (): void {
            $results = [];

            $this->eventManager->on('test', static function () use (&$results): void {
                $results[] = 'first';
                throw new Exception('Test error');
            });

            $this->eventManager->on('test', static function () use (&$results): void {
                $results[] = 'second';
            });

            $this->eventManager->emit('test');

            expect($results)->toBe(['first', 'second']);
        });

        it('Relance les erreurs critiques', function (): void {
            $this->eventManager->on('test', static function (): void {
                throw new Error('Critical error');
            });

            expect(fn() => $this->eventManager->emit('test'))
				->toThrow(new Error('Critical error'));
        });
    });

    describe('Logging de performance', function (): void {
        it('Active/désactive le logging', function (): void {
            $this->eventManager->setPerformanceLogging(true);

            $this->eventManager->on('test', static function (): void {});
            $this->eventManager->emit('test');

            $logs = $this->eventManager->getPerformanceLogs();
            expect($logs)->toBeAn('array');
            expect(count($logs))->toBe(1);

            $this->eventManager->clearPerformanceLogs();
            expect($this->eventManager->getPerformanceLogs())->toBe([]);
        });
    });

    describe('Méthodes dépréciées', function (): void {
        it('attach/detach sont des alias de on/off', function (): void {
            $callback = static function (): void {};

            // Test que attach fonctionne comme on
            expect($this->eventManager->attach('test', $callback))->toBe(true);
            expect($this->eventManager->getListeners('test')[0])->toContain($callback);

            // Test que detach fonctionne comme off
            expect($this->eventManager->detach('test', $callback))->toBe(true);
            expect($this->eventManager->getListeners('test'))->toBe([]);
        });

        it('trigger est un alias de emit', function (): void {
            $result = false;
            $this->eventManager->on('test', static function () use (&$result): void {
                $result = true;
            });

            $this->eventManager->trigger('test');
            expect($result)->toBe(true);
        });
    });
});
