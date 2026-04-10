<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Event\Event;
use BlitzPHP\Event\EventManager;

use function Kahlan\expect;

describe('Events / EventManager', function (): void {

    beforeEach(function (): void {
        $this->eventManager = new EventManager();
    });

    afterEach(function (): void {
        $this->eventManager->clearListeners();
    });

    describe('Constructeur', function (): void {
        it('Initialise avec les écouteurs fournis', function (): void {
            $callback = static function (): void {};
            $listeners = [
                'test.event' => [0 => [$callback]]
            ];

            $manager = new EventManager($listeners);

            expect($manager->getListeners('test.event')[0])->toContain($callback);
        });

        it('Initialise toujours le wildcard', function (): void {
            $manager = new EventManager();

            expect($manager->getListeners('*'))->toBe([]);
            expect($manager->getListeners())->not->toContainKey('*');
        });

        it('Conserve le wildcard lors de l\'initialisation avec des listeners', function (): void {
            $listeners = [
                '*' => [0 => [static function (): void {}]],
                'test.event' => [0 => [static function (): void {}]]
            ];

            $manager = new EventManager($listeners);

            expect($manager->getListeners('*'))->toBeAn('array');
            expect($manager->getListeners('test.event'))->toBeAn('array');
        });
    });

    describe('Méthode on()', function (): void {
        it('Ajoute un écouteur', function (): void {
            $callback = static function (): void {};

            $result = $this->eventManager->on('test.event', $callback);

            expect($result)->toBe(true);
            expect($this->eventManager->getListeners('test.event')[0])->toContain($callback);
        });

        it('Ajoute plusieurs écouteurs au même événement', function (): void {
            $callback1 = static function (): void {};
            $callback2 = static function (): void {};

            $this->eventManager->on('test', $callback1);
            $this->eventManager->on('test', $callback2);

            $listeners = $this->eventManager->getListeners('test')[0];

            expect($listeners)->toContain($callback1);
            expect($listeners)->toContain($callback2);
            expect(count($listeners))->toBe(2);
        });

        it('Retourne false si l\'écouteur existe déjà', function (): void {
            $callback = static function (): void {};

            $firstAdd = $this->eventManager->on('test', $callback);
            $secondAdd = $this->eventManager->on('test', $callback);

            expect($firstAdd)->toBe(true);
            expect($secondAdd)->toBe(false);
        });

        it('Gère les priorités', function (): void {
            $callback1 = static function (): void {};
            $callback2 = static function (): void {};

            $this->eventManager->on('test', $callback1, 10);
            $this->eventManager->on('test', $callback2, 5);

            $listeners = $this->eventManager->getListeners('test');

            expect($listeners)->toContainKey(10);
            expect($listeners)->toContainKey(5);
            expect($listeners[10])->toContain($callback1);
            expect($listeners[5])->toContain($callback2);
        });

        it('Trie les priorités automatiquement', function (): void {
            $callback1 = static function (): void {};
            $callback2 = static function (): void {};
            $callback3 = static function (): void {};

            $this->eventManager->on('test', $callback1, 15);
            $this->eventManager->on('test', $callback2, 5);
            $this->eventManager->on('test', $callback3, 10);

            $listeners = $this->eventManager->getListeners('test');
            $priorities = array_keys($listeners);

            expect($priorities)->toBe([5, 10, 15]);
        });

        it('Accepte différents types de callables', function (): void {
            // Fonction normale
            $this->eventManager->on('test', 'strlen');

            // Méthode d'objet
            $object = new class () {
                public function handle(): void {}
            };
            $this->eventManager->on('test', [$object, 'handle']);

            // Méthode statique
            $this->eventManager->on('test', [EventManager::class, 'getListeners']);

            // Closure
            $this->eventManager->on('test', static function (): void {});

            // Objet invocable
            $invocable = new class () {
                public function __invoke(): void {}
            };
            $this->eventManager->on('test', $invocable);

            expect($this->eventManager->countListeners('test'))->toBe(5);
        });

        it('Gère les événements avec wildcard dans le nom', function (): void {
            $callback = static function (): void {};

            $this->eventManager->on('user.*', $callback);
            $this->eventManager->on('*.created', $callback);
            $this->eventManager->on('*.*', $callback);

            expect($this->eventManager->hasListeners('user.*'))->toBe(true);
            expect($this->eventManager->hasListeners('*.created'))->toBe(true);
            expect($this->eventManager->hasListeners('*.*'))->toBe(true);
        });
    });

    describe('Méthode once()', function (): void {
        it('L\'écouteur ne s\'exécute qu\'une seule fois', function (): void {
            $executionCount = 0;

            $this->eventManager->once('test.event', static function () use (&$executionCount): void {
                $executionCount++;
            });

            // Premier déclenchement
            $this->eventManager->emit('test.event');
            expect($executionCount)->toBe(1);

            // Deuxième déclenchement - ne devrait plus s'exécuter
            $this->eventManager->emit('test.event');
            expect($executionCount)->toBe(1);
        });

        it('Supprime automatiquement l\'écouteur après exécution', function (): void {
            $executionCount = 0;

            $callback = static function () use (&$executionCount): void {
                $executionCount++;
            };

            $this->eventManager->once('test.event', $callback);

            // Vérifier que l'écouteur est présent avant exécution
            expect($this->eventManager->hasListeners('test.event'))->toBe(true);

            // Premier déclenchement
            $this->eventManager->emit('test.event');
            expect($executionCount)->toBe(1);

            // Vérifier que l'écouteur a été supprimé
            expect($this->eventManager->hasListeners('test.event'))->toBe(false);
        });

        it('Fonctionne avec les priorités', function (): void {
            $results = [];

            $this->eventManager->once('test', static function () use (&$results): void {
                $results[] = 'once-high';
            }, 10);

            $this->eventManager->on('test', static function () use (&$results): void {
                $results[] = 'normal';
            }, 0);

            $this->eventManager->once('test', static function () use (&$results): void {
                $results[] = 'once-low';
            }, -10);

            // Premier déclenchement
            $this->eventManager->emit('test');
            expect($results)->toBe(['once-high', 'normal', 'once-low']);

            // Réinitialiser pour second déclenchement
            $results = [];

            // Second déclenchement - seuls les écouteurs normaux doivent s'exécuter
            $this->eventManager->emit('test');
            expect($results)->toBe(['normal']);
        });

        it('Transmet correctement l\'événement au callback', function (): void {
            $receivedEvent = null;

            $this->eventManager->once('test', static function ($event) use (&$receivedEvent): void {
                $receivedEvent = $event;
            });

            $this->eventManager->emit('test', 'target-value', ['param' => 'value']);

            expect($receivedEvent)->not->toBe(null);
            expect($receivedEvent->getName())->toBe('test');
            expect($receivedEvent->getTarget())->toBe('target-value');
            expect($receivedEvent->getParam('param'))->toBe('value');
        });

        it('Retourne la valeur du callback', function (): void {
            $this->eventManager->once('test', static function (): string {
                return 'result-value';
            });

            $result = $this->eventManager->emit('test');
            expect($result)->toBe('result-value');

            // Second appel - ne devrait plus retourner de valeur
            $result = $this->eventManager->emit('test');
            expect($result)->toBe(null);
        });

        it('Fonctionne avec les wildcards', function (): void {
            $executionCount = 0;

            $this->eventManager->once('*', static function () use (&$executionCount): void {
                $executionCount++;
            });

            // Premier événement quelconque
            $this->eventManager->emit('event1');
            expect($executionCount)->toBe(1);

            // Deuxième événement - le wildcard once ne devrait plus s'exécuter
            $this->eventManager->emit('event2');
            expect($executionCount)->toBe(1);
        });

        it('Gère correctement plusieurs écouteurs once sur le même événement', function (): void {
            $executions = [];

            $this->eventManager->once('test', static function () use (&$executions): void {
                $executions[] = 'first';
            });

            $this->eventManager->once('test', static function () use (&$executions): void {
                $executions[] = 'second';
            });

            $this->eventManager->once('test', static function () use (&$executions): void {
                $executions[] = 'third';
            });

            // Premier déclenchement - tous doivent s'exécuter
            $this->eventManager->emit('test');
            expect($executions)->toBe(['first', 'second', 'third']);

            // Réinitialiser
            $executions = [];

            // Second déclenchement - aucun ne doit s'exécuter
            $this->eventManager->emit('test');
            expect($executions)->toBe([]);
        });

        it('Supprime seulement l\'écouteur once correspondant', function (): void {
            $normalExecutions = 0;
            $onceExecutions = 0;

            // Écouteur normal
            $normalCallback = static function () use (&$normalExecutions): void {
                $normalExecutions++;
            };

            // Écouteur once
            $onceCallback = static function () use (&$onceExecutions): void {
                $onceExecutions++;
            };

            $this->eventManager->on('test', $normalCallback);
            $this->eventManager->once('test', $onceCallback);

            // Premier déclenchement
            $this->eventManager->emit('test');
            expect($normalExecutions)->toBe(1);
            expect($onceExecutions)->toBe(1);

            // Vérifier que seul l'écouteur once a été supprimé
            expect($this->eventManager->hasListeners('test'))->toBe(true);

            // Second déclenchement
            $this->eventManager->emit('test');
            expect($normalExecutions)->toBe(2);
            expect($onceExecutions)->toBe(1); // Ne change pas
        });

        it('Gère les closures avec use() correctement', function (): void {
            $externalVar = 'initial';
            $executionCount = 0;

            $this->eventManager->once('test', static function () use (&$externalVar, &$executionCount): void {
                $externalVar = 'modified';
                $executionCount++;
            });

            $this->eventManager->emit('test');

            expect($externalVar)->toBe('modified');
            expect($executionCount)->toBe(1);

            // Ne devrait plus modifier
            $externalVar = 'reset';
            $this->eventManager->emit('test');
            expect($externalVar)->toBe('reset'); // Reste 'reset'
        });

        it('Fonctionne avec les méthodes d\'objet', function (): void {
            $listener = new class () {
                public int $count = 0;

                public function handle(): void
                {
                    $this->count++;
                }
            };

            $this->eventManager->once('test', $listener->handle(...));

            $this->eventManager->emit('test');
            expect($listener->count)->toBe(1);

            $this->eventManager->emit('test');
            expect($listener->count)->toBe(1); // Ne change pas
        });

        it('Fonctionne avec les fonctions anonymes invocables', function (): void {
            $listener = new class () {
                public int $count = 0;

                public function __invoke(): void
                {
                    $this->count++;
                }
            };

            $this->eventManager->once('test', $listener);

            $this->eventManager->emit('test');
            expect($listener->count)->toBe(1);

            $this->eventManager->emit('test');
            expect($listener->count)->toBe(1); // Ne change pas
        });

        it('Gère la propagation arrêtée', function (): void {
            $executions = [];

            $this->eventManager->once('test', static function ($event) use (&$executions): void {
                $executions[] = 'first';
                $event->stopPropagation();
            });

            $this->eventManager->once('test', static function () use (&$executions): void {
                $executions[] = 'second'; // Ne devrait pas s'exécuter
            });

            $this->eventManager->emit('test');
            expect($executions)->toBe(['first']);

            // Le deuxime écouteurs once doit toujours être la car la propagation de l'evenement s'est stopee.
            expect($this->eventManager->hasListeners('test'))->toBeTruthy();
        });

        it('Gère les erreurs dans l\'écouteur once', function (): void {
            $executionCount = 0;
			$errorLine = 0;

            $this->eventManager->once('test', static function () use (&$executionCount, &$errorLine): void {
                $executionCount++;
				$errorLine = __LINE__ + 1;
                throw new Exception('Test error');
            });

            $this->eventManager->emit('test');

			$logCache = array_map(fn($l) => $l['msg'], logger()->logCache);

            // L'erreur devrait être loggée mais l'écouteur supprimé
            expect($executionCount)->toBe(1);
            expect($logCache)->toContain('Event listener error: Test error in ' . __FILE__ . ':' . $errorLine);
            // expect($this->eventManager->hasListeners('test'))->toBeFalsy();
        });

        it('Ne supprime pas d\'autres écouteurs once en cas d\'erreur de suppression', function (): void {
            $executions = [];

            // Premier écouteur once
            $this->eventManager->once('test', static function () use (&$executions): void {
                $executions[] = 'first';
            });

            // Deuxième écouteur once
            $this->eventManager->once('test', static function () use (&$executions): void {
                $executions[] = 'second';
            });

            // Simuler une erreur lors de la suppression du premier
            $mockEventManager = Mockery::mock($this->eventManager);
			$mockEventManager->shouldReceive('off')->andReturn(function ($event, $callback) {
                static $count = 0;
                $count++;

                if ($count === 1) {
                    throw new Exception('Failed to remove listener');
                }

                return true;
            });

            // Devrait quand même exécuter les deux écouteurs
            $mockEventManager->emit('test');
            expect($executions)->toBe(['first', 'second']);
        });

        it('Fonctionne avec des middlewares once', function (): void {
            $preExecutions = 0;
            $postExecutions = 0;

            $this->eventManager->addMiddleware('test', static function () use (&$preExecutions): void {
                $preExecutions++;
            }, 'pre');

            $this->eventManager->once('pre.test', static function () use (&$preExecutions): void {
                $preExecutions++; // S'exécute une seule fois
            });

            $this->eventManager->once('test', static function (): void {});

            $this->eventManager->once('post.test', static function () use (&$postExecutions): void {
                $postExecutions++; // S'exécute une seule fois
            });

            // Déclencher les événements
            $this->eventManager->emit('pre.test');
            $this->eventManager->emit('test');
            $this->eventManager->emit('post.test');

            expect($preExecutions)->toBe(2); // 1 middleware + 1 once
            expect($postExecutions)->toBe(1); // 1 once

            // Second tour
            $this->eventManager->emit('pre.test');
            $this->eventManager->emit('test');
            $this->eventManager->emit('post.test');

            expect($preExecutions)->toBe(3); // +1 middleware seulement
            expect($postExecutions)->toBe(1); // inchangé
        });
    });

    describe('Méthode off()', function (): void {
        it('Supprime un écouteur', function (): void {
            $callback = static function (): void {};

            $this->eventManager->on('test', $callback);
            expect($this->eventManager->hasListeners('test'))->toBe(true);

            $result = $this->eventManager->off('test', $callback);
            expect($result)->toBe(true);
            expect($this->eventManager->hasListeners('test'))->toBe(false);
        });

        it('Retourne false si l\'écouteur n\'existe pas', function (): void {
            $callback = static function (): void {};

            $result = $this->eventManager->off('test', $callback);
            expect($result)->toBe(false);
        });

        it('Retourne false si l\'événement n\'existe pas', function (): void {
            $callback = static function (): void {};

            $result = $this->eventManager->off('nonexistent', $callback);
            expect($result)->toBe(false);
        });

        it('Supprime l\'écouteur spécifique seulement', function (): void {
            $callback1 = static function (): void {};
            $callback2 = static function (): void {};

            $this->eventManager->on('test', $callback1);
            $this->eventManager->on('test', $callback2);

            expect($this->eventManager->off('test', $callback1))->toBe(true);
            expect($this->eventManager->hasListeners('test'))->toBe(true);

            // Vérifier que callback2 est toujours là
            $listeners = $this->eventManager->getListeners('test')[0];
            expect($listeners)->toContain($callback2);
            expect($listeners)->not->toContain($callback1);
        });

        it('Supprime les écouteurs avec priorité', function (): void {
            $callback1 = static function (): void {};
            $callback2 = static function (): void {};

            $this->eventManager->on('test', $callback1, 10);
            $this->eventManager->on('test', $callback2, 5);

            expect($this->eventManager->off('test', $callback1))->toBe(true);

            $listeners = $this->eventManager->getListeners('test');
            expect($listeners)->toContainKey(5);
            expect($listeners)->not->toContainKey(10);
        });

        it('Supprime la priorité si elle devient vide', function (): void {
            $callback = static function (): void {};

            $this->eventManager->on('test', $callback, 10);
            expect($this->eventManager->off('test', $callback))->toBe(true);

            $listeners = $this->eventManager->getListeners('test');
            expect($listeners)->not->toContainKey(10);
        });

        it('Supprime l\'événement s\'il devient vide', function (): void {
            $callback = static function (): void {};

            $this->eventManager->on('test', $callback);
            expect($this->eventManager->off('test', $callback))->toBe(true);

            expect($this->eventManager->getListeners('test'))->toBe([]);
        });

        it('Fonctionne avec les wildcards', function (): void {
            $callback = static function (): void {};

            $this->eventManager->on('*', $callback);
            expect($this->eventManager->hasListeners('*'))->toBe(true);

            expect($this->eventManager->off('*', $callback))->toBe(true);
            expect($this->eventManager->hasListeners('*'))->toBe(false);
        });
    });

    describe('Méthode emit()', function (): void {
        it('Exécute les écouteurs', function (): void {
            $executed = false;

            $this->eventManager->on('test', static function () use (&$executed): void {
                $executed = true;
            });

            $this->eventManager->emit('test');
            expect($executed)->toBe(true);
        });

        it('Transmet l\'événement aux écouteurs', function (): void {
            $receivedEvent = null;

            $this->eventManager->on('test', static function ($event) use (&$receivedEvent): void {
                $receivedEvent = $event;
            });

            $this->eventManager->emit('test', 'target', ['param' => 'value']);

            expect($receivedEvent)->not->toBe(null);
            expect($receivedEvent->getName())->toBe('test');
            expect($receivedEvent->getTarget())->toBe('target');
            expect($receivedEvent->getParam('param'))->toBe('value');
        });

        it('Crée un objet Event si une chaîne est fournie', function (): void {
            $receivedEvent = null;

            $this->eventManager->on('test', static function ($event) use (&$receivedEvent): void {
                $receivedEvent = $event;
            });

            $this->eventManager->emit('test');

            expect($receivedEvent)->toBeAnInstanceOf(Event::class);
            expect($receivedEvent->getName())->toBe('test');
        });

        it('Accepte un objet Event directement', function (): void {
            $event = new Event('custom.event', 'target', ['data' => 'test']);
            $receivedEvent = null;

            $this->eventManager->on('custom.event', static function ($e) use (&$receivedEvent): void {
                $receivedEvent = $e;
            });

            $this->eventManager->emit($event);

            expect($receivedEvent)->toBe($event);
        });

        it('Met à jour la cible si fournie avec un objet Event', function (): void {
            $event = new Event('test');
            $newTarget = 'new-target';

            $receivedTarget = null;
            $this->eventManager->on('test', static function ($e) use (&$receivedTarget): void {
                $receivedTarget = $e->getTarget();
            });

            $this->eventManager->emit($event, $newTarget);

            expect($receivedTarget)->toBe($newTarget);
        });

        it('Met à jour les paramètres si fournis avec un objet Event', function (): void {
            $event = new Event('test');
            $newParams = ['key' => 'value'];

            $receivedParams = null;
            $this->eventManager->on('test', static function ($e) use (&$receivedParams): void {
                $receivedParams = $e->getParams();
            });

            $this->eventManager->emit($event, null, $newParams);

            expect($receivedParams)->toBe($newParams);
        });

        it('Fusionne les paramètres avec un objet Event existant', function (): void {
            $event = new Event('test', null, ['existing' => 'data']);
            $additionalParams = ['new' => 'value'];

            $receivedParams = null;
            $this->eventManager->on('test', static function ($e) use (&$receivedParams): void {
                $receivedParams = $e->getParams();
            });

            $this->eventManager->emit($event, null, $additionalParams);

            expect($receivedParams)->toBe(['existing' => 'data', 'new' => 'value']);
        });

        it('Retourne le résultat du dernier écouteur', function (): void {
            $this->eventManager->on('test', static function (): string {
                return 'first';
            });

            $this->eventManager->on('test', static function (): string {
                return 'second';
            });

            $result = $this->eventManager->emit('test');
            expect($result)->toBe('second');
        });

        it('Retourne null si aucun écouteur', function (): void {
            $result = $this->eventManager->emit('test');
            expect($result)->toBe(null);
        });

        it('Arrête l\'exécution si un écouteur retourne false', function (): void {
            $executions = [];

            $this->eventManager->on('test', static function () use (&$executions): bool {
                $executions[] = 'first';
                return false;
            });

            $this->eventManager->on('test', static function () use (&$executions): void {
                $executions[] = 'second'; // Ne devrait pas s'exécuter
            });

            $this->eventManager->emit('test');
            expect($executions)->toBe(['first']);
        });

        it('Arrête l\'exécution si stopPropagation() est appelé', function (): void {
            $executions = [];

            $this->eventManager->on('test', static function ($event) use (&$executions): void {
                $executions[] = 'first';
                $event->stopPropagation();
            });

            $this->eventManager->on('test', static function () use (&$executions): void {
                $executions[] = 'second'; // Ne devrait pas s'exécuter
            });

            $this->eventManager->emit('test');
            expect($executions)->toBe(['first']);
        });

        it('Exécute les écouteurs par ordre de priorité', function (): void {
            $executions = [];

            $this->eventManager->on('test', static function () use (&$executions): void {
                $executions[] = 'normal';
            }, 0);

            $this->eventManager->on('test', static function () use (&$executions): void {
                $executions[] = 'high';
            }, 10);

            $this->eventManager->on('test', static function () use (&$executions): void {
                $executions[] = 'low';
            }, -10);

            $this->eventManager->emit('test');
            expect($executions)->toBe(['high', 'normal', 'low']);
        });

        it('Gère les wildcards', function (): void {
            $executions = [];

            $this->eventManager->on('*', static function ($event) use (&$executions): void {
                $executions[] = 'wildcard:' . $event->getName();
            });

            $this->eventManager->on('user.*', static function ($event) use (&$executions): void {
                $executions[] = 'user-wildcard:' . $event->getName();
            });

            $this->eventManager->on('user.created', static function ($event) use (&$executions): void {
                $executions[] = 'specific:' . $event->getName();
            });

            $this->eventManager->emit('user.created');
            $this->eventManager->emit('user.updated');
            $this->eventManager->emit('product.created');

            expect($executions)->toBe([
                'specific:user.created',
                'user-wildcard:user.created',
                'wildcard:user.created',
                'user-wildcard:user.updated',
                'wildcard:user.updated',
                'wildcard:product.created'
            ]);
        });

        it('Gère les objets comme paramètres', function (): void {
            $params = new stdClass();
            $params->key = 'value';

            $receivedParams = null;
            $this->eventManager->on('test', static function ($event) use (&$receivedParams): void {
                $receivedParams = $event->getParams();
            });

            $this->eventManager->emit('test', null, $params);

            expect($receivedParams)->toBe(['key' => 'value']);
        });

        it('Gère les tableaux vides comme paramètres', function (): void {
            $receivedParams = null;
            $this->eventManager->on('test', static function ($event) use (&$receivedParams): void {
                $receivedParams = $event->getParams();
            });

            $this->eventManager->emit('test', null, []);
            expect($receivedParams)->toBe([]);
        });
    });

    describe('Méthodes utilitaires', function (): void {
        describe('getListeners()', function (): void {
            it('Retourne tous les écouteurs', function (): void {
                $callback1 = static function (): void {};
                $callback2 = static function (): void {};

                $this->eventManager->on('event1', $callback1);
                $this->eventManager->on('event2', $callback2);

                $listeners = $this->eventManager->getListeners();

                expect($listeners)->toContainKey('event1');
                expect($listeners)->toContainKey('event2');
                expect($listeners)->not->toContainKey('*');
            });

            it('Retourne les écouteurs d\'un événement spécifique', function (): void {
                $callback1 = static function (): void {};
                $callback2 = static function (): void {};

                $this->eventManager->on('test', $callback1);
                $this->eventManager->on('test', $callback2, 5);

                $listeners = $this->eventManager->getListeners('test');

                expect($listeners)->toBeAn('array');
                expect($listeners)->toContainKey(0);
                expect($listeners)->toContainKey(5);
                expect($listeners[0])->toContain($callback1);
                expect($listeners[5])->toContain($callback2);
            });

            it('Retourne un tableau vide pour un événement inexistant', function (): void {
                $listeners = $this->eventManager->getListeners('nonexistent');
                expect($listeners)->toBe([]);
            });

            it('Exclut les wildcards quand on demande tous les écouteurs', function (): void {
                $this->eventManager->on('*', static function (): void {});
                $this->eventManager->on('test', static function (): void {});

                $listeners = $this->eventManager->getListeners();

                expect($listeners)->toContainKey('test');
                expect($listeners)->not->toContainKey('*');
            });
        });

        describe('clearListeners()', function (): void {
            it('Supprime tous les écouteurs', function (): void {
                $this->eventManager->on('event1', static function (): void {});
                $this->eventManager->on('event2', static function (): void {});
                $this->eventManager->on('*', static function (): void {});

                $this->eventManager->clearListeners();

                expect($this->eventManager->getListeners())->toBe([]);
                expect($this->eventManager->getListeners('*'))->toBe([]);
            });

            it('Supprime les écouteurs d\'un événement spécifique', function (): void {
                $this->eventManager->on('event1', static function (): void {});
                $this->eventManager->on('event2', static function (): void {});

                $this->eventManager->clearListeners('event1');

                expect($this->eventManager->hasListeners('event1'))->toBe(false);
                expect($this->eventManager->hasListeners('event2'))->toBe(true);
            });

            it('Conserve les wildcards quand on supprime un événement spécifique', function (): void {
                $this->eventManager->on('*', static function (): void {});
                $this->eventManager->on('test', static function (): void {});

                $this->eventManager->clearListeners('test');

                expect($this->eventManager->hasListeners('test'))->toBe(false);
                expect($this->eventManager->hasListeners('*'))->toBe(true);
            });

            it('Supprime les wildcards quand on supprime tout', function (): void {
                $this->eventManager->on('*', static function (): void {});

                $this->eventManager->clearListeners();

                expect($this->eventManager->getListeners('*'))->toBe([]);
            });
        });

        describe('hasListeners()', function (): void {
            it('Retourne true si des écouteurs existent', function (): void {
                $this->eventManager->on('test', static function (): void {});
                expect($this->eventManager->hasListeners('test'))->toBe(true);
            });

            it('Retourne false si aucun écouteur', function (): void {
                expect($this->eventManager->hasListeners('test'))->toBe(false);
            });

            it('N\'inclut pas les wildcards dans la vérification', function (): void {
                $this->eventManager->on('*', static function (): void {});
                expect($this->eventManager->hasListeners('test'))->toBeFalsy();
            });

            it('Retourne false si seulement les wildcards existent', function (): void {
                $this->eventManager->on('*', static function (): void {});
                // Note: hasListeners() ne vérifie que les écouteurs spécifiques
                expect($this->eventManager->hasListeners('test'))->toBe(false);
            });
        });

        describe('countListeners()', function (): void {
            it('Compte les écouteurs d\'un événement', function (): void {
                $this->eventManager->on('test', static function (): void {});
                $this->eventManager->on('test', static function (): void {}, 5);
                $this->eventManager->on('test', static function (): void {}, 10);

                expect($this->eventManager->countListeners('test'))->toBe(3);
            });

            it('Retourne 0 si aucun écouteur', function (): void {
                expect($this->eventManager->countListeners('test'))->toBe(0);
            });

            it('Inclut les wildcards dans le compte', function (): void {
                $this->eventManager->on('*', static function (): void {});
                $this->eventManager->on('test', static function (): void {});

                expect($this->eventManager->countListeners('test'))->toBe(2);
            });

            it('Ne compte pas les wildcards seuls', function (): void {
                $this->eventManager->on('*', static function (): void {});
                $this->eventManager->on('*', static function (): void {}, 5);

                // Les wildcards comptent pour tous les événements
                expect($this->eventManager->countListeners('test'))->toBe(2);
                expect($this->eventManager->countListeners('another'))->toBe(2);
            });
        });

        describe('addMiddleware()', function (): void {
            it('Ajoute un middleware pré-exécution', function (): void {
                $executed = false;

                $this->eventManager->addMiddleware('test', static function () use (&$executed): void {
                    $executed = true;
                }, 'pre');

                $this->eventManager->emit('pre.test');
                expect($executed)->toBe(true);
            });

            it('Ajoute un middleware post-exécution', function (): void {
                $executed = false;

                $this->eventManager->addMiddleware('test', static function () use (&$executed): void {
                    $executed = true;
                }, 'post');

                $this->eventManager->emit('post.test');
                expect($executed)->toBe(true);
            });

            it('Utilise "pre" par défaut', function (): void {
                $executed = false;

                $this->eventManager->addMiddleware('test', static function () use (&$executed): void {
                    $executed = true;
                });

                $this->eventManager->emit('pre.test');
                expect($executed)->toBe(true);
            });

            it('Fonctionne avec les priorités', function (): void {
                $results = [];

                $this->eventManager->addMiddleware('test', static function () use (&$results): void {
                    $results[] = 'low';
                }, 'pre', 0);

                $this->eventManager->addMiddleware('test', static function () use (&$results): void {
                    $results[] = 'high';
                }, 'pre', 10);

                $this->eventManager->emit('pre.test');
                expect($results)->toBe(['high', 'low']);
            });

            it('N\'interfère pas avec l\'événement principal', function (): void {
                $mainExecuted = false;
                $middlewareExecuted = false;

                $this->eventManager->addMiddleware('test', static function () use (&$middlewareExecuted): void {
                    $middlewareExecuted = true;
                }, 'pre');

                $this->eventManager->on('test', static function () use (&$mainExecuted): void {
                    $mainExecuted = true;
                });

                $this->eventManager->emit('pre.test');
                $this->eventManager->emit('test');

                expect($middlewareExecuted)->toBe(true);
                expect($mainExecuted)->toBe(true);
            });
        });
    });

    describe('Gestion des erreurs', function (): void {
        it('Continue après une erreur non critique', function (): void {
            $results = [];
			$errorLine = 0;

            $this->eventManager->on('test', static function () use (&$results, &$errorLine): void {
                $results[] = 'first';
				$errorLine = __LINE__ + 1;
                throw new Exception('Test error');
            });

            $this->eventManager->on('test', static function () use (&$results): void {
                $results[] = 'second';
            });

            $this->eventManager->emit('test');

            $loggedErrors = array_map(fn($l) => $l['msg'], logger()->logCache);

            expect($results)->toBe(['first', 'second']);
            expect($loggedErrors)->toContain('Event listener error: Test error in ' . __FILE__ . ':' . $errorLine);
        });

        it('Arrête sur une erreur critique', function (): void {
            $results = [];

            $this->eventManager->on('test', static function () use (&$results): void {
                $results[] = 'first';
                throw new Error('Critical error');
            });

            $this->eventManager->on('test', static function () use (&$results): void {
                $results[] = 'second'; // Ne devrait pas s'exécuter
            });

            expect( fn () => $this->eventManager->emit('test'))
				->toThrow(new Error('Critical error'));

            expect($results)->toBe(['first']);
        });
    });

    describe('Performance logging', function (): void {
        beforeEach(function (): void {
            $this->eventManager->setPerformanceLogging(true);
        });

        it('Active/désactive le logging', function (): void {
            $this->eventManager->setPerformanceLogging(false);

            $this->eventManager->on('test', static function (): void {});
            $this->eventManager->emit('test');

            $logs = $this->eventManager->getPerformanceLogs();
            expect($logs)->toBe([]);
        });

        it('Log les performances des écouteurs', function (): void {
            $this->eventManager->on('test', static function (): void {
                usleep(100); // 0.1ms
            });

            $this->eventManager->emit('test');

            $logs = $this->eventManager->getPerformanceLogs();

            expect($logs)->toBeAn('array');
            expect($logs)->toHaveLength(1);
            expect($logs[0])->toContainKey('start');
            expect($logs[0])->toContainKey('end');
            expect($logs[0])->toContainKey('event');
            expect($logs[0])->toContainKey('listener');
            expect($logs[0])->toContainKey('duration');
            expect($logs[0])->toContainKey('priority');
            expect($logs[0]['event'])->toBe('test');
            expect($logs[0]['duration'])->toBeGreaterThan(0);
        });

        it('Log plusieurs exécutions', function (): void {
            $this->eventManager->on('test', static function (): void {});
            $this->eventManager->on('test', static function (): void {}, 5);

            $this->eventManager->emit('test');

            $logs = $this->eventManager->getPerformanceLogs();
            expect($logs)->toHaveLength(2);
        });

        it('Efface les logs', function (): void {
            $this->eventManager->on('test', static function (): void {});
            $this->eventManager->emit('test');

            expect($this->eventManager->getPerformanceLogs())->toHaveLength(1);

            $this->eventManager->clearPerformanceLogs();
            expect($this->eventManager->getPerformanceLogs())->toBe([]);
        });

        it('Identifie correctement les types de callbacks', function (): void {
            // Fonction
            $this->eventManager->on('test', 'strlen');

            // Méthode
            $object = new class () {
                public function handle(): void {}
            };
            $this->eventManager->on('test', [$object, 'handle']);

            // Closure
            $this->eventManager->on('test', static function (): void {});

            // Invocable
            $invocable = new class () {
                public function __invoke(): void {}
            };
            $this->eventManager->on('test', $invocable);

            $this->eventManager->emit('test');

            $logs = $this->eventManager->getPerformanceLogs();
            $listenerNames = array_column($logs, 'listener');

            expect($listenerNames)->toContain('strlen');
            expect($listenerNames)->toContain('Closure');
            expect($listenerNames[1])->toMatch('/::handle$/');
            expect($listenerNames[3])->toMatch('/::__invoke$/');
        });
    });

    describe('Méthodes dépréciées', function (): void {
        it('attach() est un alias de on()', function (): void {
            $callback = static function (): void {};

            // Test que attach fonctionne comme on
            expect($this->eventManager->attach('test', $callback))->toBe(true);
            expect($this->eventManager->getListeners('test')[0])->toContain($callback);

            // Test que detach fonctionne comme off
            expect($this->eventManager->detach('test', $callback))->toBe(true);
            expect($this->eventManager->getListeners('test'))->toBe([]);
        });

        it('detach() est un alias de off()', function (): void {
            $callback = static function (): void {};

            $this->eventManager->on('test', $callback);
            expect($this->eventManager->detach('test', $callback))->toBe(true);
            expect($this->eventManager->hasListeners('test'))->toBe(false);
        });

        it('trigger() est un alias de emit()', function (): void {
            $result = false;
            $this->eventManager->on('test', static function () use (&$result): void {
                $result = true;
            });

            $this->eventManager->trigger('test');
            expect($result)->toBe(true);
        });

        it('Les méthodes dépréciées déclenchent des warnings', function (): void {
			config()->set('app.environment', 'dev');

            $warnings = [];
            set_error_handler(static function ($errno, $errstr) use (&$warnings): bool {
				$warnings[] = $errstr;
                return true;
				}, E_USER_DEPRECATED);

            $this->eventManager->attach('test', static function (): void {});
            $this->eventManager->trigger('test');
            $this->eventManager->detach('test', static function (): void {});

            restore_error_handler();
			config()->set('app.environment', 'test');

            expect($warnings)->toHaveLength(3);
            expect($warnings[0])->toContain('attach() est obsolète');
            expect($warnings[1])->toContain('trigger() est obsolète');
            expect($warnings[2])->toContain('detach() est obsolète');
        });
    });

    describe('Cas limites', function (): void {
        it('Gère les événements avec des noms vides', function (): void {
            // Création d'un événement avec nom vide devrait échouer
            expect(static fn() => new Event(''))
				->toThrow(new InvalidArgumentException('Le nom de l\'événement ne peut pas être vide.'));


			// Pareil pour un événement vide
            expect(fn () => $this->eventManager->emit(''))
				->toThrow(new InvalidArgumentException('Le nom de l\'événement ne peut pas être vide.'));
		});

        it('Gère les priorités extrêmes', function (): void {
            $results = [];

            $this->eventManager->on('test', static function () use (&$results): void {
                $results[] = 'normal';
            }, 0);

            $this->eventManager->on('test', static function () use (&$results): void {
                $results[] = 'min';
            }, PHP_INT_MIN);

            $this->eventManager->on('test', static function () use (&$results): void {
                $results[] = 'max';
            }, PHP_INT_MAX);

            $this->eventManager->emit('test');

            // PHP_INT_MAX devrait être en premier, PHP_INT_MIN en dernier
            expect($results[0])->toBe('max');
            expect($results[1])->toBe('normal');
            expect($results[2])->toBe('min');
        });

        it('Gère un grand nombre d\'écouteurs', function (): void {
            $count = 100;
            $executed = 0;

            for ($i = 0; $i < $count; $i++) {
                $this->eventManager->on('test', static function () use (&$executed): void {
                    $executed++;
                }, $i);
            }

            $this->eventManager->emit('test');
            expect($executed)->toBe($count);
            expect($this->eventManager->countListeners('test'))->toBe($count);
        });

        it('Gère les écouteurs qui s\'ajoutent eux-mêmes', function (): void {
            $executionCount = 0;

            $this->eventManager->on('test', function () use (&$executionCount): void {
                $executionCount++;

                // S'ajoute à nouveau - devrait créer une boucle infinie potentielle
                if ($executionCount < 3) {
                    $this->eventManager->on('test', static function () use (&$executionCount): void {
                        $executionCount++;
                    });
                }
            });

            $this->eventManager->emit('test');

            // Devrait s'exécuter une seule fois (les nouveaux écouteurs ne sont pas exécutés dans la même passe)
            expect($executionCount)->toBe(1);
        });

        it('Gère les écouteurs qui se suppriment eux-mêmes', function (): void {
            $executionCount = 0;
            $callback = null;

            $callback = function () use (&$executionCount, &$callback): void {
                $executionCount++;
                $this->eventManager->off('test', $callback);
            };

            $this->eventManager->on('test', $callback);
            $this->eventManager->on('test', static function () use (&$executionCount): void {
                $executionCount++;
            });

            $this->eventManager->emit('test');

            // Les deux devraient s'exécuter (la suppression ne prend effet qu'après)
            expect($executionCount)->toBe(2);
        });

        it('Gère les objets complexes comme paramètres', function (): void {
            $complexObject = new class () {
                public $nested;

                public function __construct()
                {
                    $this->nested = new stdClass();
                    $this->nested->value = 'test';
                }
            };

            $receivedParams = null;
            $this->eventManager->on('test', static function ($event) use (&$receivedParams): void {
                $receivedParams = $event->getParams();
            });

            $this->eventManager->emit('test', null, ['obj' => $complexObject]);

            expect($receivedParams['obj'])->toBe($complexObject);
            expect($receivedParams['obj']->nested->value)->toBe('test');
        });

        it('Gère les ressources comme paramètres', function (): void {
            $resource = fopen('php://memory', 'r+');

            $receivedParams = null;
            $this->eventManager->on('test', static function ($event) use (&$receivedParams): void {
                $receivedParams = $event->getParams();
            });

            $this->eventManager->emit('test', null, ['resource' => $resource]);

            expect($receivedParams['resource'])->toBe($resource);
            expect(is_resource($receivedParams['resource']))->toBe(true);

            fclose($resource);
        });

        it('Gère les callables qui lancent des exceptions dans getCallbackName()', function (): void {
            // Créer un callable bizarre qui lance une exception quand on essaie de l'identifier
            $weirdCallable = new class () {
                public function __call($name, $args)
                {
                    // Simuler un comportement bizarre
                }

                public function __toString()
                {
                    throw new Exception('Cannot stringify');
                }
            };

            $this->eventManager->on('test', [$weirdCallable, 'someMethod']);

            // Ne devrait pas planter
            $this->eventManager->emit('test');

            // Le logging devrait quand même fonctionner
            expect(true)->toBe(true); // Juste vérifier qu'on arrive ici
        });
    });

    describe('Intégration avec PHP', function (): void {
        xit('Fonctionne avec les générateurs', function (): void {
            $results = [];

            $this->eventManager->on('test', static function () use (&$results): Generator {
                $results[] = 'generator-start';
                yield 'yielded-value';
                $results[] = 'generator-end';
            });

            $result = $this->eventManager->emit('test');

            expect($results)->toBe(['generator-start']);
            expect($result)->toBeAnInstanceOf(Generator::class);
        });

        it('Fonctionne avec les itérateurs', function (): void {
            $iterator = new ArrayIterator(['a', 'b', 'c']);

            $this->eventManager->on('test', static function () use ($iterator): ArrayIterator {
                return $iterator;
            });

            $result = $this->eventManager->emit('test');

            expect($result)->toBe($iterator);
        });

        it('Fonctionne avec les fonctions variadiques', function (): void {
            $receivedArgs = [];

            $variadicFunction = static function (...$args) use (&$receivedArgs): void {
                $receivedArgs = $args;
            };

            $this->eventManager->on('test', $variadicFunction);

            $event = new Event('test', null, ['a', 'b', 'c']);
            $this->eventManager->emit($event);

            expect($receivedArgs)->toBe([$event]);
        });

        it('Fonctionne avec les références', function (): void {
            $value = 'original';

            $this->eventManager->on('test', static function ($event) use (&$value): void {
                $value = 'modified';
            });

            $this->eventManager->emit('test');

            expect($value)->toBe('modified');
        });
    });
});
