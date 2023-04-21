<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Contracts\Support;

use CachingIterator;
use Countable;
use Exception;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @credit <a href="https://github.com/tighten/collect">Tightenco\Collect\Support\Enumerable</a>
 */
interface Enumerable extends Arrayable, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
    /**
     * Créez une nouvelle instance de collection si la valeur n'en est pas déjà une.
     *
     * @param Arrayable|iterable|null $items
     *
     * @return static
     */
    public static function make($items = []);

    /**
     * Créez une nouvelle instance en appelant le callback un certain nombre de fois.
     *
     * @return static
     */
    public static function times(int $number, ?callable $callback = null);

    /**
     * Créez une collection avec la plage donnée.
     *
     * @param int|string $from
     * @param int|string $to
     *
     * @return static
     */
    public static function range($from, $to);

    /**
     * Enveloppez la valeur donnée dans une collection, le cas échéant.
     *
     * @param iterable|mixed $value
     *
     * @return static
     */
    public static function wrap($value);

    /**
     * Obtenez les éléments sous-jacents de la collection donnée, le cas échéant.
     *
     * @param array|static $value
     */
    public static function unwrap($value): array;

    /**
     * Créez une nouvelle instance sans éléments.
     *
     * @return static
     */
    public static function empty();

    /**
     * Récupère tous les éléments de l'énumérable.
     */
    public function all(): array;

    /**
     * Alias pour la méthode "avg".
     *
     * @param  (callable(mixed): float|int)|string|null  $callback
     *
     * @return float|int|null
     */
    public function average($callback = null);

    /**
     * Obtenir la médiane d'une clé donnée.
     *
     * @param array|string|null $key
     *
     * @return float|int|null
     */
    public function median($key = null);

    /**
     * Obtenir le mode d'une clé donnée.
     *
     * @param array|string|null $key
     *
     * @return array<int, float|int>|null
     */
    public function mode($key = null): ?array;

    /**
     * Réduisez les éléments en un seul énumérable.
     *
     * @return static<int, mixed>
     */
    public function collapse();

    /**
     * Alias pour la méthode "contains".
     *
     * @param  (callable(mixed, mixed): bool)|mixed|string  $key
     */
    public function some($key, mixed $operator = null, mixed $value = null): bool;

    /**
     * Déterminez si un élément existe, en utilisant une comparaison stricte.
     *
     * @param  (callable(mixed): bool)|mixed|array-key  $key
     */
    public function containsStrict($key, mixed $value = null): bool;

    /**
     * Obtenir la valeur moyenne d'une clé donnée.
     *
     * @param  (callable(mixed): float|int)|string|null  $callback
     *
     * @return float|int|null
     */
    public function avg($callback = null);

    /**
     * Détermine si un élément existe dans l'énumérable.
     *
     * @param  (callable(mixed, mixed): bool)|mixed|string  $key
     */
    public function contains($key, mixed $operator = null, mixed $value = null): bool;

    /**
     * Détermine si un élément n'est pas contenu dans la collection.
     */
    public function doesntContain(mixed $key, ?string $operator = null, mixed $value = null): bool;

    /**
     * Jointure croisée avec les listes données, renvoyant toutes les permutations possibles.
     *
     * @param Arrayable|iterable ...$lists
     *
     * @return static<int, array<int, mixed>>
     */
    public function crossJoin(...$lists);

    /**
     * Videz la collection et terminez le script.
     *
     * @param mixed ...$args
     *
     * @return never
     */
    public function dd(...$args);

    /**
     * Videz la collection.
     *
     * @return $this
     */
    public function dump();

    /**
     * Récupère les éléments qui ne sont pas présents dans les éléments donnés.
     *
     * @param Arrayable|iterable $items
     *
     * @return static
     */
    public function diff($items);

    /**
     * Obtenez les éléments qui ne sont pas présents dans les éléments donnés, à l'aide du callback.
     *
     * @param Arrayable|iterable          $items
     * @param callable(mixed, mixed): int $callback
     *
     * @return static
     */
    public function diffUsing($items, callable $callback);

    /**
     * Récupère les éléments dont les clés et les valeurs ne sont pas présentes dans les éléments donnés.
     *
     * @param Arrayable|iterable $items
     *
     * @return static
     */
    public function diffAssoc($items);

    /**
     * Obtenez les éléments dont les clés et les valeurs ne sont pas présentes dans les éléments donnés, à l'aide du callback.
     *
     * @param Arrayable|iterable          $items
     * @param callable(mixed, mixed): int $callback
     *
     * @return static
     */
    public function diffAssocUsing($items, callable $callback);

    /**
     * Récupère les éléments dont les clés ne sont pas présentes dans les éléments donnés.
     *
     * @param Arrayable|iterable $items
     *
     * @return static
     */
    public function diffKeys($items);

    /**
     * Get the items whose keys are not present in the given items, using the callback.
     *
     * @param Arrayable|iterable                    $items
     * @param callable(int|string, int|string): int $callback
     *
     * @return static
     */
    public function diffKeysUsing($items, callable $callback);

    /**
     * Récupérer les éléments en double.
     *
     * @param  (callable(mixed): bool)|string|null  $callback
     *
     * @return static
     */
    public function duplicates($callback = null, bool $strict = false);

    /**
     * Récupérez les éléments en double à l'aide d'une comparaison stricte.
     *
     * @param  (callable(mixed): bool)|string|null  $callback
     *
     * @return static
     */
    public function duplicatesStrict($callback = null);

    /**
     * Exécutez un callback sur chaque élément.
     *
     * @param callable(mixed, mixed): mixed $callback
     *
     * @return $this
     */
    public function each(callable $callback);

    /**
     * Exécutez un rappel sur chaque bloc d'éléments imbriqué.
     *
     * @return static
     */
    public function eachSpread(callable $callback);

    /**
     * Déterminez si tous les éléments réussissent le test de vérité donné.
     *
     * @param  (callable(mixed, mixed): bool)|mixed|string  $key
     */
    public function every($key, ?string $operator = null, mixed $value = null): bool;

    /**
     * Obtient tous les éléments sauf ceux avec les clés spécifiées.
     *
     * @param array|Enumerable|mixed $keys
     *
     * @return static
     */
    public function except($keys);

    /**
     * Exécutez un filtre sur chacun des éléments.
     *
     * @param  (callable(mixed): bool)|null  $callback
     *
     * @return static
     */
    public function filter(?callable $callback = null);

    /**
     * Appliquez le callback si la "valeur" donnée est (ou se résout) véridique.
     *
     * @param  (callable($this): mixed)|null  $callback
     * @param  (callable($this): mixed)|null  $default
     *
     * @return $this|mixed
     */
    public function when(bool $value, ?callable $callback = null, ?callable $default = null);

    /**
     * Appliquez le callback si la collection est vide.
     *
     * @param  (callable($this): mixed)  $callback
     * @param  (callable($this): mixed)|null  $default
     *
     * @return $this|mixed
     */
    public function whenEmpty(callable $callback, ?callable $default = null);

    /**
     * Appliquez le callback si la collection n'est pas vide.
     *
     * @param  (callable($this): mixed)  $callback
     * @param  (callable($this): mixed)|null  $default
     *
     * @return $this|mixed
     */
    public function whenNotEmpty(callable $callback, ?callable $default = null);

    /**
     * Apply the callback if the given "value" is (or resolves to) truthy.
     *
     * @template TUnlessReturnType
     *
     * @param bool $value
     * @param  (callable($this): TUnlessReturnType)  $callback
     * @param  (callable($this): TUnlessReturnType)|null  $default
     *
     * @return $this|TUnlessReturnType
     */
    public function unless($value, callable $callback, ?callable $default = null);

    /**
     * Appliquez le callback seulement si la collection est vide.
     *
     * @param callable($this): mixed $callback
     * @param  (callable($this): mixed)|null  $default
     *
     * @return $this|mixed
     */
    public function unlessEmpty(callable $callback, ?callable $default = null);

    /**
     * Appliquez le callback seulement si la collection n'est pas vide.
     *
     * @param callable($this): mixed $callback
     * @param  (callable($this): mixed)|null  $default
     *
     * @return $this|mixed
     */
    public function unlessNotEmpty(callable $callback, ?callable $default = null);

    /**
     * Filtrez les éléments par la paire clé-valeur donnée.
     *
     * @return static
     */
    public function where(string $key, ?string $operator = null, mixed $value = null);

    /**
     * Filtrer les éléments où la valeur de la clé donnée est nulle.
     *
     * @return static
     */
    public function whereNull(?string $key = null);

    /**
     * Filtre les éléments où la valeur de la clé donnée n'est pas nulle.
     *
     * @return static
     */
    public function whereNotNull(?string $key = null);

    /**
     * Filtrez les éléments en fonction de la paire clé-valeur donnée à l'aide d'une comparaison stricte.
     *
     * @return static
     */
    public function whereStrict(string $key, mixed $value);

    /**
     * Filtrez les éléments par la paire clé-valeur donnée.
     *
     * @param Arrayable|iterable $values
     *
     * @return static
     */
    public function whereIn(string $key, $values, bool $strict = false);

    /**
     * Filtrez les éléments en fonction de la paire clé-valeur donnée à l'aide d'une comparaison stricte.
     *
     * @param Arrayable|iterable $values
     *
     * @return static
     */
    public function whereInStrict(string $key, $values);

    /**
     * Filtrez les éléments de sorte que la valeur de la clé donnée se situe entre les valeurs données.
     *
     * @param Arrayable|iterable $values
     *
     * @return static
     */
    public function whereBetween(string $key, $values);

    /**
     * Filtrez les éléments de sorte que la valeur de la clé donnée ne soit pas comprise entre les valeurs données.
     *
     * @param Arrayable|iterable $values
     *
     * @return static
     */
    public function whereNotBetween(string $key, $values);

    /**
     * Filtrez les éléments par la paire clé-valeur donnée.
     *
     * @param Arrayable|iterable $values
     *
     * @return static
     */
    public function whereNotIn(string $key, $values, bool $strict = false);

    /**
     * Filtrez les éléments en fonction de la paire clé-valeur donnée à l'aide d'une comparaison stricte.
     *
     * @param Arrayable|iterable $values
     *
     * @return static
     */
    public function whereNotInStrict(string $key, $values);

    /**
     * Filtrez les éléments, en supprimant tous les éléments qui ne correspondent pas au(x) type(s) donné(s).
     *
     * @param array<class-string>|class-string $type
     *
     * @return static
     */
    public function whereInstanceOf($type);

    /**
     * Obtenir le premier élément de l'énumérable réussissant le test de vérité donné.
     *
     * @param  (callable(mixed,mixed): bool)|null  $callback
     * @param  mixed|(\Closure(): mixed)  $default
     *
     * @return mixed
     */
    public function first(?callable $callback = null, $default = null);

    /**
     * Obtenez le premier élément par la paire clé-valeur donnée.
     *
     * @return mixed|null
     */
    public function firstWhere(string $key, mixed $operator = null, mixed $value = null);

    /**
     * Obtenez un tableau aplati des éléments de la collection.
     *
     * @return static
     */
    public function flatten(int $depth = INF);

    /**
     * Flip the values with their keys.
     *
     * @return static<mixed, TKey>
     */
    public function flip();

    /**
     * Obtenez un article de la collection par clé.
     */
    public function get(int|string|null $key, mixed $default = null): mixed;

    /**
     * Regroupez un tableau associatif par un champ ou à l'aide d'un callback.
     *
     * @param  (callable(mixed, int|string $key): int|string)|array|string  $groupBy
     * @param mixed $groupBy
     *
     * @return static
     */
    public function groupBy($groupBy, bool $preserveKeys = false);

    /**
     * Saisissez un tableau associatif par un champ ou à l'aide d'un callback.
     *
     * @param  (callable(mixed, int|string $key): int|string)|array|string  $keyBy
     * @param mixed $keyBy
     *
     * @return static
     */
    public function keyBy($keyBy);

    /**
     * Détermine si un élément existe dans la collection par clé.
     */
    public function has(mixed $key): bool;

    /**
     * Déterminez si l'une des clés existe dans la collection.
     */
    public function hasAny(mixed $key): bool;

    /**
     * Concaténer les valeurs d'une clé donnée sous forme de chaîne.
     */
    public function implode(callable|string $value, ?string $glue = null): string;

    /**
     * Intersection de la collection avec les éléments donnés.
     *
     * @param Arrayable|iterable $items
     *
     * @return static
     */
    public function intersect($items);

    /**
     * Intersecter la collection avec les éléments donnés par clé.
     *
     * @param Arrayable|iterable $items
     *
     * @return static
     */
    public function intersectByKeys($items);

    /**
     * Déterminez si la collection est vide.
     */
    public function isEmpty(): bool;

    /**
     * Déterminez si la collection n'est pas vide.
     */
    public function isNotEmpty(): bool;

    /**
     * Déterminez si la collection contient un seul élément.
     */
    public function containsOneItem(): bool;

    /**
     * Joignez tous les éléments de la collection à l'aide d'une chaîne. Les articles finaux peuvent utiliser une chaîne de colle séparée.
     */
    public function join(string $glue, string $finalGlue = ''): string;

    /**
     * Obtenez les clés des objets de la collection.
     *
     * @return static<int, mixed>
     */
    public function keys();

    /**
     * Obtenez le dernier élément de la collection.
     *
     * @param  (callable(mixed, mixed $key): bool)|null  $callback
     * @param  mixed|(Closure(): mixed)  $default
     */
    public function last(?callable $callback = null, $default = null): mixed;

    /**
     * Exécutez une map sur chacun des éléments.
     *
     * @param  callable(mixed $value, mixed $key): mixed  $callback
     *
     * @return static
     */
    public function map(callable $callback);

    /**
     * Exécutez une map sur chaque bloc d'éléments imbriqué.
     *
     * @param callable(mixed): mixed $callback
     *
     * @return static
     */
    public function mapSpread(callable $callback);

    /**
     * Exécutez une map de dictionnaire sur les éléments.
     *
     * Le rappel doit renvoyer un tableau associatif avec une seule paire clé/valeur.
     *
     * @param  callable(mixed $value, mixed $key): array  $callback
     *
     * @return static
     */
    public function mapToDictionary(callable $callback);

    /**
     * Exécutez une map de regroupement sur les éléments.
     *
     * Le callback doit renvoyer un tableau associatif avec une seule paire clé/valeur.
     *
     * @param callable(mixed, mixed): array $callback
     *
     * @return static
     */
    public function mapToGroups(callable $callback);

    /**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @template TMapWithKeysKey of array-key
     * @template TMapWithKeysValue
     *
     * @param callable(mixed, TKey): array<TMapWithKeysKey, TMapWithKeysValue> $callback
     *
     * @return static<TMapWithKeysKey, TMapWithKeysValue>
     */
    public function mapWithKeys(callable $callback);

    /**
     * Mappez une collection et aplatissez le résultat d'un seul niveau.
     *
     * @param  callable(mixed $key, mixed $value): Collection|array)  $callback
     *
     * @return static
     */
    public function flatMap(callable $callback);

    /**
     * Mappez les valeurs dans une nouvelle classe.
     *
     * @return static
     */
    public function mapInto(string $class);

    /**
     * Fusionner la collection avec les éléments donnés.
     *
     * @param Arrayable|iterable $items
     *
     * @return static
     */
    public function merge($items);

    /**
     * Fusionne récursivement la collection avec les éléments donnés.
     *
     * @param Arrayable|iterable $items
     *
     * @return static
     */
    public function mergeRecursive($items);

    /**
     * Créez une collection en utilisant cette collection pour les clés et une autre pour ses valeurs.
     *
     * @param Arrayable|iterable $values
     *
     * @return static
     */
    public function combine($values);

    /**
     * Union de la collection avec les éléments donnés.
     *
     * @param Arrayable|iterable $items
     *
     * @return static
     */
    public function union($items);

    /**
     * Obtenir la valeur minimale d'une clé donnée.
     *
     * @param  (callable(mixed $value):mixed)|string|null  $callback
     * @param mixed|null $callback
     *
     * @return mixed
     */
    public function min($callback = null);

    /**
     * Obtenir la valeur maximale d'une clé donnée.
     *
     * @param  (callable(mixed $value):mixed)|string|null  $callback
     * @param mixed|null $callback
     *
     * @return mixed
     */
    public function max($callback = null);

    /**
     * Créez une nouvelle collection composée de chaque nième élément.
     *
     * @return static
     */
    public function nth(int $step, int $offset = 0);

    /**
     * Obtenez les éléments avec les clés spécifiées.
     *
     * @param array|Enumerable|string $keys
     *
     * @return static
     */
    public function only($keys);

    /**
     * "Pagine" la collection en la découpant en une plus petite collection.
     *
     * @return static
     */
    public function forPage(int $page, int $perPage);

    /**
     * Partitionnez la collection en deux tableaux à l'aide du callback ou de la clé donnés.
     *
     * @param  (callable(mixed $value, mixed $key): bool)|mixed|string  $key
     * @param mixed $key
     *
     * @return static<int<0, 1>, static>
     */
    public function partition($key, ?string $operator = null, mixed $value = null);

    /**
     * Poussez tous les éléments donnés sur la collection.
     *
     * @return static
     */
    public function concat(iterable $source);

    /**
     * Obtenez un ou un nombre spécifié d'éléments au hasard dans la collection.
     *
     * @param (callable(self): int)|int|null $number
     *
     * @return mixed|static
     *
     * @throws InvalidArgumentException
     */
    public function random($number = null);

    /**
     * Reduce the collection to a single value.
     *
     * @template TReduceInitial
     * @template TReduceReturnType
     *
     * @param callable(TReduceInitial|TReduceReturnType, mixed, TKey): TReduceReturnType $callback
     * @param TReduceInitial                                                             $initial
     *
     * @return TReduceReturnType
     */
    public function reduce(callable $callback, $initial = null);

    /**
     * Réduisez la collection à plusieurs valeurs agrégées.
     *
     * @throws UnexpectedValueException
     */
    public function reduceSpread(callable $callback, ...$initial): array;

    /**
     * Remplacez les éléments de la collection par les éléments donnés.
     *
     * @param Arrayable|iterable $items
     *
     * @return static
     */
    public function replace($items);

    /**
     * Remplacez récursivement les éléments de la collection par les éléments donnés.
     *
     * @param Arrayable|iterable $items
     *
     * @return static
     */
    public function replaceRecursive($items);

    /**
     * Inverser l'ordre des elements de la collection.
     *
     * @return static
     */
    public function reverse();

    /**
     * Recherche dans la collection une valeur donnée et renvoie la clé correspondante en cas de succès.
     *
     * @param  mixed|callable(mixed $item, string|int $key): bool  $value
     * @param mixed $value
     *
     * @return bool|int|string
     */
    public function search($value, bool $strict = false);

    /**
     * Mélangez les objets de la collection.
     *
     * @return static
     */
    public function shuffle(?int $seed = null);

    /**
     * Créez des blocs représentant une vue "fenêtre coulissante" des éléments de la collection.
     *
     * @return static<int, static>
     */
    public function sliding(int $size = 2, int $step = 1);

    /**
     * Ignorez les {$count} premiers éléments.
     *
     * @return static
     */
    public function skip(int $count);

    /**
     * Ignorer les éléments de la collection jusqu'à ce que la condition donnée soit remplie.
     *
     * @param  mixed|callable(mixed,int|string $key): bool  $value
     * @param mixed $value
     *
     * @return static
     */
    public function skipUntil($value);

    /**
     * Ignorer les éléments de la collection tant que la condition donnée est remplie.
     *
     * @param  mixed|callable(mixed, int|string $key): bool  $value
     * @param mixed $value
     *
     * @return static
     */
    public function skipWhile($value);

    /**
     * Obtenez une tranche d'éléments de l'énumérable.
     *
     * @return static
     */
    public function slice(int $offset, ?int $length = null);

    /**
     * Diviser une collection en un certain nombre de groupes.
     *
     * @return static<int, static>
     */
    public function split(int $numberOfGroups);

    /**
     * Récupère le premier élément de la collection, mais uniquement s'il existe exactement un élément. Sinon, lancez une exception.
     *
     * @param  (callable(mixed, int|string $key): bool)|string  $key
     *
     * @throws ItemNotFoundException
     * @throws MultipleItemsFoundException
     */
    public function sole($key = null, ?string $operator = null, mixed $value = null): mixed;

    /**
     * Récupère le premier élément de la collection mais lève une exception si aucun élément correspondant n'existe.
     *
     * @param  (callable(mixed, int|string $key): bool)|string  $key
     *
     * @throws ItemNotFoundException
     */
    public function firstOrFail($key = null, ?string $operator = null, mixed $value = null): mixed;

    /**
     * Coupez la collection en morceaux de la taille donnée.
     */
    public function chunk(int $size);

    /**
     * Coupez la collection en morceaux avec un callback.
     *
     * @param  callable(mixed, int|string $key, static<int, mixed>): bool  $callback
     *
     * @return static<int, static<int, mixed>>
     */
    public function chunkWhile(callable $callback);

    /**
     * Divisez une collection en un certain nombre de groupes et remplissez complètement les premiers groupes.
     *
     * @return static<int, static>
     */
    public function splitIn(int $numberOfGroups);

    /**
     * Triez chaque élément avec un callback.
     *
     * @param  (callable(mixed, mixed): int)|null|int  $callback
     *
     * @return static
     */
    public function sort(?callable $callback = null);

    /**
     * Trier les éléments par ordre décroissant.
     *
     * @return static
     */
    public function sortDesc(int $options = SORT_REGULAR);

    /**
     * Trier la collection à l'aide du rappel donné.
     *
     * @param  array<int|string, (callable(mixed, mixed): mixed)|(callable(mixed, int|string): mixed)|string|array{string, string}>|(callable(mixed, int|string): mixed)|string  $callback
     *
     * @return static
     */
    public function sortBy($callback, int $options = SORT_REGULAR, bool $descending = false);

    /**
     * Triez la collection dans l'ordre décroissant à l'aide du rappel donné.
     *
     * @param  array<int|string, (callable(mixed, mixed): mixed)|(callable(mixed, int|string): mixed)|string|array{string, string}>|(callable(mixed, int|string): mixed)|string  $callback
     *
     * @return static
     */
    public function sortByDesc($callback, int $options = SORT_REGULAR);

    /**
     * Trier les clés de collection.
     *
     * @return static
     */
    public function sortKeys(int $options = SORT_REGULAR, bool $descending = false);

    /**
     * Triez les clés de collection par ordre décroissant.
     *
     * @return static
     */
    public function sortKeysDesc(int $options = SORT_REGULAR);

    /**
     * Triez les clés de collection à l'aide d'un callback.
     *
     * @param callable(mixed, mixed): int $callback
     *
     * @return static
     */
    public function sortKeysUsing(callable $callback);

    /**
     * Obtenir la somme des valeurs données.
     *
     * @param  (callable(mixed $value): mixed)|string|null  $callback
     * @param mixed|null $callback
     *
     * @return mixed
     */
    public function sum($callback = null);

    /**
     * Prenez le premier ou le dernier {$limit} éléments.
     */
    public function take(int $limit);

    /**
     * Prenez des objets dans la collection jusqu'à ce que la condition donnée soit remplie.
     *
     * @param callable(mixed, int|string): bool|mixed $value
     *
     * @return static
     */
    public function takeUntil($value);

    /**
     * Prendre des objets dans la collection tant que la condition donnée est remplie.
     *
     * @param callable(mixed, int|string): bool|mixed $value
     *
     * @return static
     */
    public function takeWhile($value);

    /**
     * Passez la collection au rappel donné, puis renvoyez-la.
     *
     * @param callable($this): mixed $callback
     *
     * @return $this
     */
    public function tap(callable $callback);

    /**
     * Passez la collection au callback donné et renvoyez le résultat.
     *
     * @param callable($this): mixed $callback
     *
     * @return mixed
     */
    public function pipe(callable $callback);

    /**
     * Passez la collection dans une nouvelle classe.
     *
     * @param class-string $class
     *
     * @return mixed
     */
    public function pipeInto(string $class);

    /**
     * Passez la collection à travers une série de canaux appelables et renvoyez le résultat.
     *
     * @param callable[] $callbacks
     */
    public function pipeThrough(array $callbacks): mixed;

    /**
     * Obtenir les valeurs d'une clé donnée.
     *
     * @param string|string[] $value
     *
     * @return static<int, mixed>
     */
    public function pluck($value, ?string $key = null);

    /**
     * Créez une collection de tous les éléments qui ne réussissent pas un test de vérité donné.
     *
     * @param  (callable(mixed $value, mixed $key): bool)|bool|mixed  $callback
     * @param mixed $callback
     *
     * @return static
     */
    public function reject($callback = true);

    /**
     * Convertit un tableau de notation "point" aplati en un tableau étendu.
     *
     * @return static
     */
    public function undot();

    /**
     * Renvoie uniquement les éléments uniques du tableau de collection.
     *
     * @param  (callable(mixed $item, int|string $key): mixed)|string|null  $key
     * @param mixed|null $key
     *
     * @return static
     */
    public function unique($key = null, bool $strict = false);

    /**
     * Renvoie uniquement les éléments uniques du tableau de collection en utilisant une comparaison stricte.
     *
     * @param  (callable(mixed $value, mixed $key): mixed)|string|null  $key
     * @param mixed|null $key
     *
     * @return static
     */
    public function uniqueStrict($key = null);

    /**
     * Réinitialisez les clés sur le tableau sous-jacent.
     *
     * @return static<int, mixed>
     */
    public function values();

    /**
     * Remplir la collection à la longueur spécifiée avec une valeur.
     *
     * @return static<int, mixed>
     */
    public function pad(int $size, mixed $value);

    /**
     * Obtenez l'itérateur de valeurs.
     */
    public function getIterator(): Traversable;

    /**
     * Comptez le nombre d'éléments de la collection.
     */
    public function count(): int;

    /**
     * Comptez le nombre d'éléments de la collection par un champ ou à l'aide d'un callback.
     *
     * @param  (callable(mixed, int|string): int|string)|string|null  $countBy
     *
     * @return static<int|string, int>
     */
    public function countBy($countBy = null);

    /**
     * Compressez la collection avec un ou plusieurs tableaux.
     *
     * ex. new Collection([1, 2, 3])->zip([4, 5, 6]);
     *      => [[1, 4], [2, 5], [3, 6]]
     *
     * @param Arrayable|iterable ...$items
     *
     * @return static<int, static<int, mixed>>
     */
    public function zip($items);

    /**
     * Rassemblez les valeurs dans une collection.
     */
    public function collect();

    /**
     * Convertissez l'objet en quelque chose de JSON sérialisable.
     */
    public function jsonSerialize(): mixed;

    /**
     * Obtenez une instance de CachingIterator.
     */
    public function getCachingIterator(int $flags = CachingIterator::CALL_TOSTRING): CachingIterator;

    /**
     * Convertissez la collection en sa représentation sous forme de chaîne.
     */
    public function __toString(): string;

    /**
     * Indique que la représentation sous forme de chaîne du modèle doit être échappée lorsque __toString est invoqué.
     *
     * @return $this
     */
    public function escapeWhenCastingToString(bool $escape = true);

    /**
     * Ajoutez une méthode à la liste des méthodes proxy.
     */
    public static function proxy(string $method): void;

    /**
     * Accédez dynamiquement aux proxys de collecte.
     *
     * @throws Exception
     */
    public function __get(string $key): mixed;
}
