<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Http\MiddlewareQueue;
use BlitzPHP\Spec\ReflectionHelper;
use Spec\BlitzPHP\App\Middlewares\DumbMiddleware;
use Spec\BlitzPHP\App\Middlewares\SampleMiddleware;

use function Kahlan\expect;

describe('Http / MiddlewareQueue', function (): void {
    beforeAll(function (): void {
		$this->request    = service('request');
		$this->response   = service('response');
		$this->container  = service('container');
		$this->middleware = fn (array $middlewares = []) => new MiddlewareQueue($this->container, $middlewares, $this->request, $this->response);
    });

    describe('Constructeur', function (): void {
		it('Ajout de middleware via le constructeur', function (): void {
			$cb = function (): void {
			};

			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware([$cb]);

			expect($queue)->toHaveLength(1);
			expect($queue->current()->getCallable())->toBe($cb);
		});
    });

    describe('Recuperation du middleware courant', function (): void {
		it('Est-ce que la recuperation du middleware courant fonctionne', function (): void {
			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();

			$cb = function (): void {
			};
			$queue->add($cb);

			expect($queue->current()->getCallable())->toBe($cb);
		});

		it('current() leve une exception pour une position actuelle invalide', function (): void {
			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();

			expect(fn() => $queue->current())->toThrow(new OutOfBoundsException('Position actuelle non valide (0).'));
		});
    });

    describe('Ajout de middleware a la pile', function (): void {
		it('add() renvoie l\'instance', function (): void {
			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();

			$cb = function (): void {
			};

			expect($queue->add($cb))->toBe($queue);
		});

		it('Les middlewares sont ajoutés dans le bon ordre', function (): void {
			$one = function (): void {
			};
			$two = function (): void {
			};

			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();

			expect($queue)->toHaveLength(0);

			$queue->add($one);
			expect($queue)->toHaveLength(1);

			$queue->add($two);
			expect($queue)->toHaveLength(2);

			expect($queue->current()->getCallable())->toBe($one);
			$queue->next();
			expect($queue->current()->getCallable())->toBe($two);
		});

		it('prepend() renvoie l\'instance', function (): void {
			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();

			$cb = function (): void {
			};

			expect($queue->prepend($cb))->toBe($queue);
		});

		it('Les middlewares sont ajoutés en debut de chaine dans le bon ordre', function (): void {
			$one = function (): void {
			};
			$two = function (): void {
			};

			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();

			expect($queue)->toHaveLength(0);

			$queue->append($one);
			expect($queue)->toHaveLength(1);

			$queue->prepend($two);
			expect($queue)->toHaveLength(2);

			expect($queue->current()->getCallable())->toBe($two);
			$queue->next();
			expect($queue->current()->getCallable())->toBe($one);
		});

		it('Ajout de middlewares sous forme de chaine de caractere', function (): void {
			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();

			$queue->push('Sample');
			$queue->prepend(SampleMiddleware::class);

			expect($queue->current())->toBeAnInstanceOf(SampleMiddleware::class);
			expect($queue->current())->toBeAnInstanceOf(SampleMiddleware::class);
		});

		it('Ajout de middlewares via un tableau', function (): void {
			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();

			$one = function (): void {
			};

			$queue->add([$one]);
	        $queue->prepend([SampleMiddleware::class]);

			expect($queue->current())->toBeAnInstanceOf(SampleMiddleware::class);
			$queue->next();
			expect($queue->current()->getCallable())->toBe($one);
		});
    });

    describe('Insertion', function (): void {
		it('Insertion a une position quelconque', function (): void {
			$one = function (): void {
			};
			$two = function (): void {
			};
			$three = function (): void {
			};
			$four = new SampleMiddleware();

			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();

			$queue->add($one)->add($two)->insertAt(0, $three)->insertAt(2, $four);
			expect($queue->current()->getCallable())->toBe($three);
        	$queue->next();
			expect($queue->current()->getCallable())->toBe($one);
	        $queue->next();
			expect($queue->current())->toBeAnInstanceOf(SampleMiddleware::class);
        	$queue->next();
			expect($queue->current()->getCallable())->toBe($two);

			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();

			$queue->add($one)->add($two)->insertAt(1, $three);
			expect($queue->current()->getCallable())->toBe($one);
        	$queue->next();
			expect($queue->current()->getCallable())->toBe($three);
        	$queue->next();
			expect($queue->current()->getCallable())->toBe($two);
		});

		it('Insertion a une position hors limite', function (): void {
			$one = function (): void {
			};
			$two = function (): void {
			};

			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();

			$queue->add($one)->insertAt(98, $two);

			expect($queue)->toHaveLength(2);
			expect($queue->current()->getCallable())->toBe($one);
        	$queue->next();
			expect($queue->current()->getCallable())->toBe($two);
		});

		it('Insertion a une position negative', function (): void {
			$one = function (): void {
			};
			$two = function (): void {
			};
			$three = new SampleMiddleware();

			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();

			$queue->add($one)->insertAt(-1, $two)->insertAt(-1, $three);

			expect($queue)->toHaveLength(3);
			expect($queue->current()->getCallable())->toBe($two);
        	$queue->next();
			expect($queue->current())->toBeAnInstanceOf(SampleMiddleware::class);
        	$queue->next();
			expect($queue->current()->getCallable())->toBe($one);
		});

		it('Insertion avant une classe', function (): void {
			$one = function (): void {
			};
			$two = new SampleMiddleware();
			$three = function (): void {
			};
			$four = new DumbMiddleware();

			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();

			$queue->add($one)->add($two)->insertBefore(SampleMiddleware::class, $three)->insertBefore(SampleMiddleware::class, $four);

			expect($queue)->toHaveLength(4);
			expect($queue->current()->getCallable())->toBe($one);
        	$queue->next();
			expect($queue->current()->getCallable())->toBe($three);
        	$queue->next();
			expect($queue->current())->toBeAnInstanceOf(DumbMiddleware::class);
        	$queue->next();
			expect($queue->current())->toBeAnInstanceOf(SampleMiddleware::class);


			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();
			$two = SampleMiddleware::class;

			$queue->add($one)->add($two)->insertBefore(SampleMiddleware::class, $three);

			expect($queue)->toHaveLength(3);
			expect($queue->current()->getCallable())->toBe($one);
        	$queue->next();
			expect($queue->current()->getCallable())->toBe($three);
        	$queue->next();
			expect($queue->current())->toBeAnInstanceOf(SampleMiddleware::class);
		});

		it('Insertion avant une classe invalide leve une exception', function (): void {
			$one = function (): void {
			};
			$two = new SampleMiddleware();
			$three = function (): void {
			};

			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();

			expect(fn() => $queue->add($one)->add($two)->insertBefore('InvalidClassName', $three))
				->toThrow(new LogicException("No middleware matching 'InvalidClassName' could be found."));
		});

		it('Insertion avant une classe', function (): void {
			$one = new SampleMiddleware();
			$two = function (): void {
			};
			$three = function (): void {
			};
			$four = new DumbMiddleware();

			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();

			$queue->add($one)->add($two)->insertAfter(SampleMiddleware::class, $three)->insertAfter(SampleMiddleware::class, $four);

			expect($queue)->toHaveLength(4);
			expect($queue->current())->toBeAnInstanceOf(SampleMiddleware::class);
        	$queue->next();
			expect($queue->current())->toBeAnInstanceOf(DumbMiddleware::class);
        	$queue->next();
			expect($queue->current()->getCallable())->toBe($three);
        	$queue->next();
			expect($queue->current()->getCallable())->toBe($two);


			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();
			$one = SampleMiddleware::class;

			$queue->add($one)->add($two)->insertAfter(SampleMiddleware::class, $three);

			expect($queue)->toHaveLength(3);
			expect($queue->current())->toBeAnInstanceOf(SampleMiddleware::class);
        	$queue->next();
			expect($queue->current()->getCallable())->toEqual($three);
        	$queue->next();
			expect($queue->current()->getCallable())->toEqual($two);
		});

		it('Insertion apres une classe invalide ne leve pas une exception', function (): void {
			$one = new SampleMiddleware();
			$two = function (): void {
			};
			$three = function (): void {
			};

			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();

			$queue->add($one)->add($two)->insertAfter('InvalidClassName', $three);

			expect($queue)->toHaveLength(3);
			expect($queue->current())->toBeAnInstanceOf(SampleMiddleware::class);
			$queue->next();
			expect($queue->current()->getCallable())->toBe($two);
			$queue->next();
			expect($queue->current()->getCallable())->toBe($three);
		});
    });

	describe('Container', function (): void {
		it("S'assurer que le middleware fourni par le conteneur est le meme objet", function (): void {
			$middleware = new SampleMiddleware();
			$this->container->set(SampleMiddleware::class, $middleware);
			$queue = new MiddlewareQueue($this->container, [], $this->request, $this->response);
			$queue->add(SampleMiddleware::class);

			expect($queue->current())->toBe($middleware);
		});

		it("S'assurer qu'une exception est levee pour les middlewares inconnu", function (): void {
			$queue = new MiddlewareQueue($this->container, [], $this->request, $this->response);
			$queue->add('UnresolvableMiddleware');

			expect(fn() => $queue->current())
				->toThrow(new InvalidArgumentException("Middleware, `UnresolvableMiddleware` n'a pas été trouvé."));
		});
	});

	describe('Alias & register', function (): void {
		it("Definition des alias", function (): void {
			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();

			$queue->alias('sample', SampleMiddleware::class);
			expect(ReflectionHelper::getPrivateProperty($queue, 'aliases'))->toBe([
				'sample' => SampleMiddleware::class
			]);

			$queue->aliases([
				'dummy'  => DumbMiddleware::class,
				'sample' => SampleMiddleware::class,
			]);

			expect(ReflectionHelper::getPrivateProperty($queue, 'aliases'))->toBe([
				'sample' => SampleMiddleware::class,
				'dummy'  => DumbMiddleware::class
			]);
		});

		it("Utilisation des alias dans l'insertion", function (): void {
			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();

			$queue->aliases([
				'dummy'  => DumbMiddleware::class,
				'sample' => SampleMiddleware::class,
			]);

			$one = function (): void {
			};
			$two = new SampleMiddleware();
			$three = function (): void {
			};
			$four = new DumbMiddleware();

			$queue->add($two)->insertBefore('sample', $three)->push($four)->insertAfter('dummy', $one);

			expect($queue)->toHaveLength(4);
			expect($queue->current()->getCallable())->toBe($three);
        	$queue->next();
			expect($queue->current())->toBeAnInstanceOf(SampleMiddleware::class);
        	$queue->next();
			expect($queue->current())->toBeAnInstanceOf(DumbMiddleware::class);
        	$queue->next();
			expect($queue->current()->getCallable())->toBe($one);
		});

		it("Utilisation des alias dans la recuperation du middleware", function (): void {
			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();

			$queue->aliases(['dummy'  => DumbMiddleware::class]);

			$queue->add('dummy');

			expect($queue)->toHaveLength(1);
			expect($queue->current())->toBeAnInstanceOf(DumbMiddleware::class);
        });

		it('register', function (): void {
			/** @var MiddlewareQueue $queue */
			$queue = $this->middleware();

			$aliases = [
				'sample' => SampleMiddleware::class,
				'dummy'  => DumbMiddleware::class
			];

			$cb = function (): void {
			};

			$config = [
				'aliases' => $aliases,
				'globals' => array_keys($aliases),
				'build' => static function (MiddlewareQueue $queue) use ($cb): void {
					$queue->insertAt(0, $cb);
				},
			];

			$queue->register($config);

			expect($queue)->toHaveLength(3);
			expect(ReflectionHelper::getPrivateProperty($queue, 'aliases'))->toBe($config['aliases']);
			expect(ReflectionHelper::getPrivateProperty($queue, 'queue'))->toBe([$cb, ...$config['globals']]);
			$queue->seek(2);
			expect($queue->current())->toBeAnInstanceOf(DumbMiddleware::class);
			$queue->rewind();
			expect($queue->current()->getCallable())->toBe($cb);
			$queue->next();
			expect($queue->current())->toBeAnInstanceOf(SampleMiddleware::class);
		});
	});
});
