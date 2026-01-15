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
use BlitzPHP\Event\EventListenerManagerTrait;
use BlitzPHP\Utilities\Reflection\ReflectionClass;

use function Kahlan\expect;

describe('Events / EventListenerManagerTrait', function (): void {

    // Classe de test qui utilise le trait
    $testClass = new class () {
        use EventListenerManagerTrait;

        public string $publicProperty = 'test';
        private string $privateProperty = 'private';

        public function getPrivateProperty(): string
        {
            return $this->privateProperty;
        }
    };

    beforeEach(function () use ($testClass): void {
        $this->testObject = clone $testClass;
        $this->eventManager = new EventManager();
        $this->testObject->setEventManager($this->eventManager);
    });

    describe('Gestion du gestionnaire d\'événements', function (): void {
        it('Définit et récupère le gestionnaire d\'événements', function (): void {
            expect($this->testObject->getEventManager())->toBe($this->eventManager);
        });

        it('Vérifie si un gestionnaire est défini', function (): void {
            expect($this->testObject->hasEventManager())->toBe(true);

            $objectWithoutManager = clone $this->testObject;
			ReflectionClass::make($objectWithoutManager)->setValue('eventManager', null);

            expect($objectWithoutManager->hasEventManager())->toBe(false);
        });

        it('Lève une exception si aucun gestionnaire n\'est défini', function (): void {
            $objectWithoutManager = clone $this->testObject;
			ReflectionClass::make($objectWithoutManager)->setValue('eventManager', null);

            expect(static function () use ($objectWithoutManager): void {
                $objectWithoutManager->getEventManager();
            })->toThrow(new RuntimeException('Aucun gestionnaire d\'événements n\'a été défini.'));
        });
    });

    describe('Ajout d\'écouteurs', function (): void {
        it('Ajoute un écouteur d\'événement', function (): void {
            $executed = false;

            $result = $this->testObject->addEventListener('test.event', static function () use (&$executed): void {
                $executed = true;
            });

            expect($result)->toBe(true);

            $this->testObject->fireEvent('test.event');
            expect($executed)->toBe(true);
        });

        it('Ajoute un écouteur avec binding de contexte', function (): void {
            $resultValue = null;

            $this->testObject->addEventListener('test.event', function () use (&$resultValue): void {
                $resultValue = $this->publicProperty;
            }, 0, true);

            $this->testObject->fireEvent('test.event');
            expect($resultValue)->toBe('test');
        });

        it('Ajoute un écouteur qui ne s\'exécute qu\'une fois', function (): void {
            $executionCount = 0;

            $this->testObject->addEventListenerOnce('test.event', static function () use (&$executionCount): void {
                $executionCount++;
            });

            // Premier déclenchement
            $this->testObject->fireEvent('test.event');
            expect($executionCount)->toBe(1);

            // Deuxième déclenchement - ne devrait plus s'exécuter
            $this->testObject->fireEvent('test.event');
            expect($executionCount)->toBe(1);
        });

        xit('Ajoute un écouteur unique avec binding de contexte', function (): void {
            $resultValue = null;
            $executionCount = 0;

            $this->testObject->addEventListenerOnce('test.event', function () use (&$resultValue, &$executionCount): void {
                $resultValue = $this->getPrivateProperty();
                $executionCount++;
            }, 0, true);

            $this->testObject->fireEvent('test.event');
            expect($resultValue)->toBe('private');
            expect($executionCount)->toBe(1);

            // Ne devrait plus s'exécuter
            $this->testObject->fireEvent('test.event');
            expect($executionCount)->toBe(1);
        });

        it('Ajoute un écouteur avec priorité', function (): void {
            $results = [];

            $this->testObject->addEventListener('test', static function () use (&$results): void {
                $results[] = 'low';
            }, 0);

            $this->testObject->addEventListener('test', static function () use (&$results): void {
                $results[] = 'high';
            }, 10);

            $this->testObject->fireEvent('test');
            expect($results)->toBe(['high', 'low']);
        });
    });

    describe('Déclenchement d\'événements', function (): void {
        it('Déclenche un événement avec nom', function (): void {
            $result = null;

            $this->testObject->addEventListener('test', static function ($event) use (&$result): void {
                $result = $event->getTarget();
            });

            $this->testObject->fireEvent('test', 'target-value');
            expect($result)->toBe('target-value');
        });

        it('Déclenche un événement avec objet Event', function (): void {
            $result = null;

            $this->testObject->addEventListener('test', static function ($event) use (&$result): void {
                $result = $event->getParam('data');
            });

            $event = new Event('test', null, ['data' => 'test-data']);
            $this->testObject->fireEvent($event);
            expect($result)->toBe('test-data');
        });

        it('Déclenche un événement avec paramètres', function (): void {
            $receivedParams = null;

            $this->testObject->addEventListener('test', static function ($event) use (&$receivedParams): void {
                $receivedParams = $event->getParams();
            });

            $this->testObject->fireEvent('test', null, ['key' => 'value', 'number' => 42]);
            expect($receivedParams)->toBe(['key' => 'value', 'number' => 42]);
        });

        it('Déclenche un événement avec cible et paramètres', function (): void {
            $target = new stdClass();
            $target->id = 123;

            $receivedTarget = null;
            $receivedParams = null;

            $this->testObject->addEventListener('test', static function ($event) use (&$receivedTarget, &$receivedParams): void {
                $receivedTarget = $event->getTarget();
                $receivedParams = $event->getParams();
            });

            $this->testObject->fireEvent('test', $target, ['action' => 'update']);
            expect($receivedTarget)->toBe($target);
            expect($receivedParams)->toBe(['action' => 'update']);
        });
    });

    describe('Gestion des écouteurs', function (): void {
        it('Supprime un écouteur', function (): void {
            $executed = false;
            $callback = static function () use (&$executed): void {
                $executed = true;
            };

            $this->testObject->addEventListener('test', $callback);

            // Premier déclenchement
            $this->testObject->fireEvent('test');
            expect($executed)->toBe(true);

            // Réinitialisation et suppression
            $executed = false;
            expect($this->testObject->removeEventListener('test', $callback))->toBe(true);

            // Ne devrait plus s'exécuter
            $this->testObject->fireEvent('test');
            expect($executed)->toBe(false);
        });

        it('Supprime tous les écouteurs', function (): void {
            $executionCount = 0;

            $callback1 = static function () use (&$executionCount): void {
                $executionCount++;
            };
            $callback2 = static function () use (&$executionCount): void {
                $executionCount++;
            };

            $this->testObject->addEventListener('test1', $callback1);
            $this->testObject->addEventListener('test2', $callback2);

            $this->testObject->clearEventListeners('test1');
            $this->testObject->fireEvent('test1');
            $this->testObject->fireEvent('test2');

            expect($executionCount)->toBe(1);

            $this->testObject->clearEventListeners();
            $executionCount = 0;
            $this->testObject->fireEvent('test1');
            $this->testObject->fireEvent('test2');

            expect($executionCount)->toBe(0);
        });

        it('Vérifie si un événement a des écouteurs', function (): void {
            expect($this->testObject->hasEventListeners('test'))->toBe(false);

            $this->testObject->addEventListener('test', static function (): void {});
            expect($this->testObject->hasEventListeners('test'))->toBe(true);
        });

        it('Récupère les écouteurs', function (): void {
            $callback = static function (): void {};

            $this->testObject->addEventListener('test', $callback);
            $listeners = $this->testObject->getEventListeners('test');

            expect($listeners)->toBeAn('array');
            expect($listeners[0])->toContain($callback);

            $allListeners = $this->testObject->getEventListeners();
            expect($allListeners)->toContainKey('test');
        });
    });

    describe('Middlewares', function (): void {
        it('Déclenche un événement avec middlewares', function (): void {
            $preMiddlewareExecuted = false;
            $postMiddlewareExecuted = false;
            $mainListenerExecuted = false;

            $preMiddleware = static function ($params, $target) use (&$preMiddlewareExecuted): void {
                $preMiddlewareExecuted = true;
                expect($params['data'])->toBe('test');
                expect($target)->toBe('target');
            };

            $postMiddleware = static function ($result, $params, $target) use (&$postMiddlewareExecuted): void {
                $postMiddlewareExecuted = true;
                expect($result)->toBe('success');
                expect($params['data'])->toBe('test');
                expect($target)->toBe('target');
            };

            $this->testObject->addEventListener('test', static function () use (&$mainListenerExecuted): string {
                $mainListenerExecuted = true;
                return 'success';
            });

            $result = $this->testObject->fireEventWithMiddleware(
                'test',
                'target',
                ['data' => 'test'],
                [$preMiddleware],
                [$postMiddleware]
            );

            expect($preMiddlewareExecuted)->toBe(true);
            expect($mainListenerExecuted)->toBe(true);
            expect($postMiddlewareExecuted)->toBe(true);
            expect($result)->toBe('success');
        });

        it('Gère les middlewares non callables', function (): void {
            $executed = false;

            $this->testObject->addEventListener('test', static function () use (&$executed): void {
                $executed = true;
            });

            $result = $this->testObject->fireEventWithMiddleware(
                'test',
                null,
                [],
                ['not-a-callable'], // Devrait être ignoré
                [123] // Devrait être ignoré
            );

            expect($executed)->toBe(true);
        });
    });

    describe('Méthodes protégées', function (): void {
        it('Crée un événement avec le contexte courant', function (): void {
            // Utilisation de la réflexion pour tester la méthode protégée
            $reflection = new ReflectionClass($this->testObject);
            $method = $reflection->getMethod('createEvent');
            $method->setAccessible(true);

            $event = $method->invoke($this->testObject, 'test.event', ['param' => 'value']);

            expect($event->getName())->toBe('test.event');
            expect($event->getTarget())->toBe($this->testObject);
            expect($event->getParams())->toBe(['param' => 'value']);
        });

        it('Déclenche un événement avec le contexte courant', function (): void {
            // Utilisation de la réflexion pour tester la méthode protégée
            $reflection = new ReflectionClass($this->testObject);
            $method = $reflection->getMethod('fireEventWithSelf');
            $method->setAccessible(true);

            $receivedTarget = null;
            $this->testObject->addEventListener('self.event', static function ($event) use (&$receivedTarget): void {
                $receivedTarget = $event->getTarget();
            });

            $result = $method->invoke($this->testObject, 'self.event', ['data' => 'test']);

            expect($receivedTarget)->toBe($this->testObject);
            expect($result)->toBe(null);
        });
    });

    describe('Gestion des erreurs', function (): void {
        it('Lève une exception si aucun gestionnaire pour addEventListener', function (): void {
            $objectWithoutManager = clone $this->testObject;
			ReflectionClass::make($objectWithoutManager)->setValue('eventManager', null);

            expect(static function () use ($objectWithoutManager): void {
                $objectWithoutManager->addEventListener('test', static function (): void {});
            })->toThrow(new RuntimeException('Aucun gestionnaire d\'événements n\'a été défini.'));
        });

        it('Lève une exception si aucun gestionnaire pour fireEvent', function (): void {
            $objectWithoutManager = clone $this->testObject;
            ReflectionClass::make($objectWithoutManager)->setValue('eventManager', null);

            expect(static function () use ($objectWithoutManager): void {
                $objectWithoutManager->fireEvent('test');
            })->toThrow(new RuntimeException('Aucun gestionnaire d\'événements n\'a été défini.'));
        });
    });
});
