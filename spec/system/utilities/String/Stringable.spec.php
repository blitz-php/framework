<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Utilities\String\Stringable;
use BlitzPHP\Utilities\Iterable\Collection;
use BlitzPHP\Utilities\DateTime\Date;

use function Kahlan\expect;

describe('Utilities / String / Stringable', function (): void {
    describe('Construction et méthodes de base', function (): void {
        it('Doit créer une instance vide', function (): void {
            $string = new Stringable();
            expect((string) $string)->toBe('');
            expect($string->value())->toBe('');
            expect($string->toString())->toBe('');
        });

        it('Doit créer une instance avec une valeur', function (): void {
            $string = new Stringable('Hello');
            expect((string) $string)->toBe('Hello');
            expect($string->value())->toBe('Hello');
        });

        it('Doit vérifier si vide avec isEmpty() et isNotEmpty()', function (): void {
            $empty = new Stringable('');
            $notEmpty = new Stringable('Hello');

            expect($empty->isEmpty())->toBe(true);
            expect($empty->isNotEmpty())->toBe(false);
            expect($notEmpty->isEmpty())->toBe(false);
            expect($notEmpty->isNotEmpty())->toBe(true);
        });

        it('Doit convertir en types primitifs', function (): void {
            $string = new Stringable('123');
            expect($string->toInteger())->toBe(123);
            expect($string->toFloat())->toBe(123.0);

            $floatString = new Stringable('123.45');
            expect($floatString->toFloat())->toBe(123.45);

            $boolString = new Stringable('true');
            expect($boolString->toBoolean())->toBe(true);

            $boolString2 = new Stringable('false');
            expect($boolString2->toBoolean())->toBe(false);

            $dateString = new Stringable('2023-01-01');
            expect($dateString->toDate())->toBeAnInstanceOf(Date::class);
        });

        it('Doit vérifier l\'égalité avec exactly()', function (): void {
            $string1 = new Stringable('Hello');
            $string2 = new Stringable('Hello');
            $string3 = new Stringable('hello');

            expect($string1->exactly('Hello'))->toBe(true);
            expect($string1->exactly($string2))->toBe(true);
            expect($string1->exactly('hello'))->toBe(false);
            expect($string1->exactly($string3))->toBe(false);
        });
    });

    describe('Manipulation de chaînes', function (): void {
        it('Doit ajouter du texte avec append()', function (): void {
            $string = new Stringable('Hello');
            $result = $string->append(' World', '!');
            expect((string) $result)->toBe('Hello World!');
            // L'instance originale ne doit pas être modifiée
            expect((string) $string)->toBe('Hello');
        });

        it('Doit préfixer du texte avec prepend()', function (): void {
            $string = new Stringable('World');
            $result = $string->prepend('Hello ');
            expect((string) $result)->toBe('Hello World');
        });

        it('Doit ajouter des nouvelles lignes avec newLine()', function (): void {
            $string = new Stringable('Hello');
            $result = $string->newLine(2)->append('World');
            expect((string) $result)->toBe('Hello' . PHP_EOL . PHP_EOL . 'World');
        });

        it('Doit répéter avec repeat()', function (): void {
            $string = new Stringable('AB');
            $result = $string->repeat(3);
            expect((string) $result)->toBe('ABABAB');
        });

        it('Doit inverser avec reverse()', function (): void {
            $string = new Stringable('Hello');
            $result = $string->reverse();
            expect((string) $result)->toBe('olleH');
        });

        it('Doit wrapper avec wrap()', function (): void {
            $string = new Stringable('content');
            $result = $string->wrap('<', '>');
            expect((string) $result)->toBe('<content>');

            $result2 = $string->wrap('"');
            expect((string) $result2)->toBe('"content"');
        });
    });

    describe('Extraction et recherche', function (): void {
        it('Doit extraire after()', function (): void {
            $string = new Stringable('Hello World');
            $result = $string->after('Hello ');
            expect((string) $result)->toBe('World');
        });

        it('Doit extraire afterLast()', function (): void {
            $string = new Stringable('App\Http\Controllers');
            $result = $string->afterLast('\\');
            expect((string) $result)->toBe('Controllers');
        });

        it('Doit extraire before()', function (): void {
            $string = new Stringable('Hello World');
            $result = $string->before(' World');
            expect((string) $result)->toBe('Hello');
        });

        it('Doit extraire beforeLast()', function (): void {
            $string = new Stringable('App\Http\Controllers');
            $result = $string->beforeLast('\\');
            expect((string) $result)->toBe('App\Http');
        });

        it('Doit extraire between()', function (): void {
            $string = new Stringable('[test]');
            $result = $string->between('[', ']');
            expect((string) $result)->toBe('test');
        });

        it('Doit extraire betweenFirst()', function (): void {
            $string = new Stringable('[test][second]');
            $result = $string->betweenFirst('[', ']');
            expect((string) $result)->toBe('test');
        });

        it('Doit extraire avec substr()', function (): void {
            $string = new Stringable('Hello World');
            $result = $string->substr(6);
            expect((string) $result)->toBe('World');

            $result2 = $string->substr(0, 5);
            expect((string) $result2)->toBe('Hello');

            $result3 = $string->substr(-5);
            expect((string) $result3)->toBe('World');
        });

        it('Doit extraire un extrait avec excerpt()', function (): void {
            $string = new Stringable('Hello World Universe');
            $result = $string->excerpt('World');
            expect($result)->toContain('World');

            $result2 = $string->excerpt('', ['radius' => 5]);
            expect($result2)->toContain('...');
        });
    });

    describe('Vérifications et tests', function (): void {
        it('Doit vérifier contains()', function (): void {
            $string = new Stringable('Hello World');
            expect($string->contains('World'))->toBe(true);
            expect($string->contains('world'))->toBe(false);
            expect($string->contains('world', true))->toBe(true);
            expect($string->contains(['Hello', 'World']))->toBe(true);
            expect($string->contains(['Hello', 'Universe']))->toBe(true);
            expect($string->contains(['Universe', 'Galaxy']))->toBe(false);
        });

        it('Doit vérifier containsAll()', function (): void {
            $string = new Stringable('Hello World');
            expect($string->containsAll(['Hello', 'World']))->toBe(true);
            expect($string->containsAll(['Hello', 'Universe']))->toBe(false);
            expect($string->containsAll(['hello', 'world'], true))->toBe(true);
        });

        it('Doit vérifier startsWith()', function (): void {
            $string = new Stringable('Hello World');
            expect($string->startsWith('Hello'))->toBe(true);
            expect($string->startsWith('hello'))->toBe(false);
            expect($string->startsWith(['Hello', 'World']))->toBe(true);
            expect($string->startsWith(['World', 'Universe']))->toBe(false);
        });

        it('Doit vérifier endsWith()', function (): void {
            $string = new Stringable('Hello World');
            expect($string->endsWith('World'))->toBe(true);
            expect($string->endsWith('world'))->toBe(false);
            expect($string->endsWith(['World', 'Hello']))->toBe(true);
            expect($string->endsWith(['Hello', 'Universe']))->toBe(false);
        });

        it('Doit vérifier avec is()', function (): void {
            $string = new Stringable('foobar');
            expect($string->is('foo*'))->toBe(true);
            expect($string->is('*bar'))->toBe(true);
            expect($string->is('foo'))->toBe(false);
            expect($string->is(['foo*', 'bar*']))->toBe(true);
        });

        it('Doit vérifier les types avec is*()', function (): void {
            $ascii = new Stringable('Hello');
            $unicode = new Stringable('Hello 世界');
            $json = new Stringable('{"name":"John"}');
            $uuid = new Stringable('123e4567-e89b-12d3-a456-426614174000');
            $empty = new Stringable('');

            expect($ascii->isAscii())->toBe(true);
            expect($unicode->isAscii())->toBe(false);
            expect($json->isJson())->toBe(true);
            expect($ascii->isJson())->toBe(false);
            expect($uuid->isUuid())->toBe(true);
            expect($ascii->isUuid())->toBe(false);
            // isUlid() retourne false par défaut car non implémenté
            expect($ascii->isUlid())->toBe(false);
        });

        it('Doit tester les patterns avec test()', function (): void {
            $string = new Stringable('Hello 123 World');
            expect($string->test('/\d+/'))->toBe(true);
            expect($string->test('/^Hello/'))->toBe(true);
            expect($string->test('/^World/'))->toBe(false);
        });

        it('Doit trouver les positions avec position() et lastPosition()', function (): void {
            $string = new Stringable('Hello Hello World');
            expect($string->position('Hello'))->toBe(0);
            expect($string->position('Hello', 1))->toBe(6);
            expect($string->position('NotFound'))->toBe(false);

            expect($string->lastPosition('Hello'))->toBe(6);
            expect($string->lastPosition('World'))->toBe(12);
            expect($string->lastPosition('hello', 0, false))->toBe(6);
        });

        it('Doit vérifier les types de caractères', function (): void {
            $alpha = new Stringable('Hello');
            $numeric = new Stringable('123');
            $alnum = new Stringable('Hello123');
            $printable = new Stringable('Hello World!');

            expect($alpha->isAlpha())->toBe(true);
            expect($numeric->isNumeric())->toBe(true);
            expect($alnum->isAlnum())->toBe(true);
            expect($printable->isPrintable())->toBe(true);

            $nonAlpha = new Stringable('123!');
            expect($nonAlpha->isAlpha())->toBe(false);
        });
    });

    describe('Conversion de casse', function (): void {
        it('Doit convertir en camelCase', function (): void {
            $string = new Stringable('hello_world');
            $result = $string->camel();
            expect((string) $result)->toBe('helloWorld');
        });

        it('Doit convertir en kebab-case', function (): void {
            $string = new Stringable('HelloWorld');
            $result = $string->kebab();
            expect((string) $result)->toBe('hello-world');
        });

        it('Doit convertir en snake_case', function (): void {
            $string = new Stringable('HelloWorld');
            $result = $string->snake();
            expect((string) $result)->toBe('hello_world');

            $result2 = $string->snake('-');
            expect((string) $result2)->toBe('hello-world');
        });

        it('Doit convertir en StudlyCase', function (): void {
            $string = new Stringable('hello_world');
            $result = $string->studly();
            expect((string) $result)->toBe('HelloWorld');
        });

        it('Doit convertir en minuscules', function (): void {
            $string = new Stringable('HELLO');
            $result = $string->lower();
            expect((string) $result)->toBe('hello');
        });

        it('Doit convertir en majuscules', function (): void {
            $string = new Stringable('hello');
            $result = $string->upper();
            expect((string) $result)->toBe('HELLO');
        });

        it('Doit convertir en title case', function (): void {
            $string = new Stringable('hello world');
            $result = $string->title();
            expect((string) $result)->toBe('Hello World');
        });

        it('Doit convertir en headline case', function (): void {
            $string = new Stringable('hello_world');
            $result = $string->headline();
            expect((string) $result)->toBe('Hello World');
        });

        it('Doit convertir avec ucfirst() et lcfirst()', function (): void {
            $string = new Stringable('hello');
            $result = $string->ucfirst();
            expect((string) $result)->toBe('Hello');

            $string2 = new Stringable('HELLO');
            $result2 = $string2->lcfirst();
            expect((string) $result2)->toBe('hELLO');
        });

        it('Doit créer un slug', function (): void {
            $string = new Stringable('Hello World');
            $result = $string->slug();
            expect((string) $result)->toBe('hello-world');

            $result2 = $string->slug('_');
            expect((string) $result2)->toBe('hello_world');
        });

        it('Doit convertir en ASCII', function (): void {
            $string = new Stringable('Élément');
            $result = $string->ascii();
            expect((string) $result)->toBe('Element');

            $result2 = $string->ascii('fr');
            expect((string) $result2)->toBe('Element');
        });
    });

    describe('Remplacements', function (): void {
        it('Doit remplacer avec replace()', function (): void {
            $string = new Stringable('Hello World');
            $result = $string->replace('World', 'Universe');
            expect((string) $result)->toBe('Hello Universe');

            $result2 = $string->replace(['Hello', 'World'], ['Hi', 'Earth']);
            expect((string) $result2)->toBe('Hi Earth');
        });

        it('Doit remplacer la première occurrence', function (): void {
            $string = new Stringable('Hello Hello World');
            $result = $string->replaceFirst('Hello', 'Hi');
            expect((string) $result)->toBe('Hi Hello World');
        });

        it('Doit remplacer la dernière occurrence', function (): void {
            $string = new Stringable('Hello Hello World');
            $result = $string->replaceLast('Hello', 'Hi');
            expect((string) $result)->toBe('Hello Hi World');
        });

        it('Doit remplacer séquentiellement', function (): void {
            $string = new Stringable('? ?');
            $result = $string->replaceArray('?', ['Hello', 'World']);
            expect((string) $result)->toBe('Hello World');
        });

        it('Doit remplacer avec regex', function (): void {
            $string = new Stringable('Hello 123 World');
            $result = $string->replaceMatches('/\d+/', '456');
            expect((string) $result)->toBe('Hello 456 World');

            $result2 = $string->replaceMatches('/\d+/', fn($matches) => (int)$matches[0] * 2);
            expect((string) $result2)->toBe('Hello 246 World');
        });

        it('Doit supprimer avec remove()', function (): void {
            $string = new Stringable('Hello World');
            $result = $string->remove('World');
            expect((string) $result)->toBe('Hello ');

            $result2 = $string->remove(['Hello', 'World']);
            expect((string) $result2)->toBe(' ');

            $result3 = $string->remove('world', false);
            expect((string) $result3)->toBe('Hello ');
        });

        it('Doit échanger avec swap()', function (): void {
            $string = new Stringable('Hello World');
            $result = $string->swap(['Hello' => 'Hi', 'World' => 'Earth']);
            expect((string) $result)->toBe('Hi Earth');
        });

        it('Doit substituer avec substrReplace()', function (): void {
            $string = new Stringable('Hello World');
            $result = $string->substrReplace('Universe', 6);
            expect((string) $result)->toBe('Hello Universe');

            $result2 = $string->substrReplace('Hi', 0, 5);
            expect((string) $result2)->toBe('Hi World');
        });
    });

    describe('Troncature et limitation', function (): void {
        it('Doit limiter les caractères avec limit()', function (): void {
            $string = new Stringable('Hello World');
            $result = $string->limit(5);
            expect((string) $result)->toBe('Hello...');

            $result2 = $string->limit(5, '***');
            expect((string) $result2)->toBe('Hello***');

            $result2 = $string->limit(11, '***');
            expect((string) $result2)->toBe('Hello World');
        });

        it('Doit limiter les mots avec words()', function (): void {
            $string = new Stringable('Hello World Universe');
            $result = $string->words(2);
            expect((string) $result)->toBe('Hello World...');

            $result2 = $string->words(2, '***');
            expect((string) $result2)->toBe('Hello World***');

            $result2 = $string->words(5, '***');
            expect((string) $result2)->toBe('Hello World Universe');
        });

        it('Doit masquer avec mask()', function (): void {
            $string = new Stringable('password123');
            $result = $string->mask('*', 2, 6);
            expect((string) $result)->toBe('pa******123');

            $result2 = $string->mask('*', 8);
            expect((string) $result2)->toBe('password***');
        });

        it('Doit trimmer avec trim(), ltrim(), rtrim()', function (): void {
            $string = new Stringable('  Hello World  ');
            $result = $string->trim();
            expect((string) $result)->toBe('Hello World');

            $result2 = $string->ltrim();
            expect((string) $result2)->toBe('Hello World  ');

            $result3 = $string->rtrim();
            expect((string) $result3)->toBe('  Hello World');
        });

        it('Doit nettoyer les espaces avec squish()', function (): void {
            $string = new Stringable('  Hello   World  ');
            $result = $string->squish();
            expect((string) $result)->toBe('Hello World');
        });
    });

    describe('Padding et formatage', function (): void {
        it('Doit padd à gauche avec padLeft()', function (): void {
            $string = new Stringable('test');
            $result = $string->padLeft(10);
            expect((string) $result)->toBe('      test');

            $result2 = $string->padLeft(10, '*');
            expect((string) $result2)->toBe('******test');
        });

        it('Doit padd à droite avec padRight()', function (): void {
            $string = new Stringable('test');
            $result = $string->padRight(10);
            expect((string) $result)->toBe('test      ');

            $result2 = $string->padRight(10, '*');
            expect((string) $result2)->toBe('test******');
        });

        it('Doit padd des deux côtés avec padBoth()', function (): void {
            $string = new Stringable('test');
            $result = $string->padBoth(10);
            expect((string) $result)->toBe('   test   ');

            $result2 = $string->padBoth(10, '*');
            expect((string) $result2)->toBe('***test***');
        });

        it('Doit terminer avec finish()', function (): void {
            $string = new Stringable('test');
            $result = $string->finish('/');
            expect((string) $result)->toBe('test/');

            $string2 = new Stringable('test/');
            $result2 = $string2->finish('/');
            expect((string) $result2)->toBe('test/');
        });

        it('Doit commencer avec start()', function (): void {
            $string = new Stringable('test');
            $result = $string->start('/');
            expect((string) $result)->toBe('/test');

            $string2 = new Stringable('/test');
            $result2 = $string2->start('/');
            expect((string) $result2)->toBe('/test');
        });
    });

    describe('Parsing et extraction de données', function (): void {
        it('Doit parser un callback avec parseCallback()', function (): void {
            $string = new Stringable('Controller@method');
            $result = $string->parseCallback();
            expect($result)->toBe(['Controller', 'method']);

            $string2 = new Stringable('Controller');
            $result2 = $string2->parseCallback('index');
            expect($result2)->toBe(['Controller', 'index']);
        });

        it('Doit extraire avec match()', function (): void {
            $string = new Stringable('Hello 123 World');
            $result = $string->match('/\d+/');
            expect((string) $result)->toBe('123');

            $result2 = $string->match('/notfound/');
            expect((string) $result2)->toBe('');
        });

        it('Doit extraire toutes les correspondances avec matchAll()', function (): void {
            $string = new Stringable('Hello 123 World 456');
            $result = $string->matchAll('/\d+/');
            expect($result)->toBeAnInstanceOf(Collection::class);
            expect($result->toArray())->toBe(['123', '456']);
        });

        it('Doit scanner avec scan()', function (): void {
            $string = new Stringable('John 25');
            $result = $string->scan('%s %d');
            expect($result)->toBeAnInstanceOf(Collection::class);
            expect($result->toArray())->toBe(['John', 25]);
        });

        it('Doit splitter avec split()', function (): void {
            $string = new Stringable('a,b,c');
            $result = $string->split(',');
            expect($result)->toBeAnInstanceOf(Collection::class);
            expect($result->toArray())->toBe(['a', 'b', 'c']);

            $result2 = $string->split(2);
            expect($result2->toArray())->toBe(['a,', 'b,', 'c']);
        });

		it('Doit splitter correctement avec différents séparateurs', function (): void {
			// Virgule simple
			$string = new Stringable('a,b,c');
			expect($string->split(',')->toArray())->toBe(['a', 'b', 'c']);

			// Point-virgule
			$string = new Stringable('a;b;c');
			expect($string->split(';')->toArray())->toBe(['a', 'b', 'c']);

			// Pattern complexe (espace)
			$string = new Stringable('a b c');
			expect($string->split(' ')->toArray())->toBe(['a', 'b', 'c']);

			// Regex valide
			$string = new Stringable('a,b;c.d');
			expect($string->split('/[,;.]/')->toArray())->toBe(['a', 'b', 'c', 'd']);

			// Pattern avec caractères spéciaux regex
			$string = new Stringable('a.b.c');
			expect($string->split('.')->toArray())->toBe(['a', 'b', 'c']);
			// Note: '.' en regex signifie "n'importe quel caractère"
			// Mais après preg_quote, ça devient '\.' qui est le point littéral
		});

        it('Doit exploser avec explode()', function (): void {
            $string = new Stringable('a,b,c');
            $result = $string->explode(',');
            expect($result)->toBeAnInstanceOf(Collection::class);
            expect($result->toArray())->toBe(['a', 'b', 'c']);

            $result2 = $string->explode(',', 2);
            expect($result2->toArray())->toBe(['a', 'b,c']);
        });

        it('Doit splitter par majuscules avec ucsplit()', function (): void {
            $string = new Stringable('HelloWorld');
            $result = $string->ucsplit();
            expect($result)->toBeAnInstanceOf(Collection::class);
            expect($result->toArray())->toBe(['Hello', 'World']);
        });

        it('Doit compter les mots avec wordCount()', function (): void {
            $string = new Stringable('Hello World Universe');
            expect($string->wordCount())->toBe(3);
        });

        it('Doit compter les occurrences avec substrCount()', function (): void {
            $string = new Stringable('Hello Hello World');
            expect($string->substrCount('Hello'))->toBe(2);
            expect($string->substrCount('World'))->toBe(1);
            expect($string->substrCount('Universe'))->toBe(0);
        });
    });

    describe('Méthodes conditionnelles et fluides', function (): void {
        it('Doit utiliser when()', function (): void {
            $string = new Stringable('Hello');
            $result = $string->when(true, fn($str) => $str->append(' World'));
            expect((string) $result)->toBe('Hello World');

            $result2 = $string->when(false, fn($str) => $str->append(' World'));
            expect((string) $result2)->toBe('Hello');
        });

        it('Doit utiliser unless()', function (): void {
            $string = new Stringable('Hello');
            $result = $string->unless(false, fn($str) => $str->append(' World'));
            expect((string) $result)->toBe('Hello World');

            $result2 = $string->unless(true, fn($str) => $str->append(' World'));
            expect((string) $result2)->toBe('Hello');
        });

        it('Doit utiliser whenNotEmpty()', function (): void {
            $string = new Stringable('Hello');
            $result = $string->whenNotEmpty(fn($str) => $str->append(' World'));
            expect((string) $result)->toBe('Hello World');

            $empty = new Stringable('');
            $result2 = $empty->whenNotEmpty(fn($str) => $str->append(' World'));
            expect((string) $result2)->toBe('');
        });

        it('Doit utiliser whenEmpty()', function (): void {
            $string = new Stringable('Hello');
            $result = $string->whenEmpty(fn($str) => $str->append(' Default'));
            expect((string) $result)->toBe('Hello');

            $empty = new Stringable('');
            $result2 = $empty->whenEmpty(fn($str) => $str->append('Default'));
            expect((string) $result2)->toBe('Default');
        });

        it('Doit transformer avec transform()', function (): void {
            $string = new Stringable('Hello');
            $result = $string->transform(fn($str) => strtoupper($str->value()));
            expect($result)->toBe('HELLO');
        });

        it('Doit utiliser tap()', function (): void {
            $string = new Stringable('Hello');
            $captured = null;
            $result = $string->tap(function ($str) use (&$captured): void {
                $captured = $str->value();
            });
            expect($captured)->toBe('Hello');
            expect((string) $result)->toBe('Hello');
        });

        it('Doit utiliser pipe()', function (): void {
            $string = new Stringable('Hello');
            $result = $string->pipe(fn($str) => $str->append(' World'));
            expect((string) $result)->toBe('Hello World');
        });
    });

    describe('Comparaisons', function (): void {
        it('Doit comparer avec compare()', function (): void {
            $string1 = new Stringable('apple');
            $string2 = new Stringable('banana');

            expect($string1->compare('apple'))->toBe(0);
            expect($string1->compare('Apple', false))->toBe(0);
            expect($string1->compare('Apple', true))->toBeGreaterThan(0);
            expect($string1->compare($string2))->toBeLessThan(0);
            expect($string2->compare($string1))->toBeGreaterThan(0);
        });

        it('Doit vérifier l\'égalité avec equals()', function (): void {
            $string = new Stringable('Hello');
            expect($string->equals('Hello'))->toBe(true);
            expect($string->equals('hello', false))->toBe(true);
            expect($string->equals('hello', true))->toBe(false);
            expect($string->equals(new Stringable('Hello')))->toBe(true);
        });
    });

    describe('Méthodes magiques et propriétés', function (): void {
        it('Doit accéder aux propriétés via __get()', function (): void {
            $string = new Stringable('Hello World');
            expect($string->length)->toBe(11);
            expect($string->upper)->toBe('HELLO WORLD');
            expect($string->lower)->toBe('hello world');
            expect($string->camel)->toBe('helloWorld');
            expect($string->snake)->toBe('hello_world');
            expect($string->kebab)->toBe('hello-world');
            expect($string->studly)->toBe('HelloWorld');
            expect($string->title)->toBe('Hello World');
            expect($string->slug)->toBe('hello-world');
        });

        it('Doit convertir en JSON avec jsonSerialize()', function (): void {
            $string = new Stringable('Hello');
            expect(json_encode($string))->toBe('"Hello"');
        });

        it('Doit accéder à basename() et dirname()', function (): void {
            $string = new Stringable('/path/to/file.txt');
            $result = $string->basename();
            expect((string) $result)->toBe('file.txt');

            $result2 = $string->basename('.txt');
            expect((string) $result2)->toBe('file');

            $result3 = $string->dirname();
            expect((string) $result3)->toBe('/path/to');

            $result4 = $string->dirname(2);
            expect((string) $result4)->toBe('/path');
        });

        it('Doit convertir en pluriel et singulier', function (): void {
            $string = new Stringable('category');
            $result = $string->plural();
            expect((string) $result)->toBe('categories');

            $string2 = new Stringable('categories');
            $result2 = $string2->singular();
            expect((string) $result2)->toBe('category');

            $string3 = new Stringable('BlogPost');
            $result3 = $string3->pluralStudly();
            expect((string) $result3)->toBe('BlogPosts');
        });
    });

    describe('Méthodes avancées', function (): void {
        it('Doit supprimer les balises HTML avec stripTags()', function (): void {
            $string = new Stringable('<p>Hello <strong>World</strong></p>');
            $result = $string->stripTags();
            expect((string) $result)->toBe('Hello World');

            $result2 = $string->stripTags('<strong>');
            expect((string) $result2)->toBe('Hello <strong>World</strong>');
        });

        it('Doit convertir en markdown', function (): void {
			skipIf(! class_exists('\League\CommonMark\GithubFlavoredMarkdownConverter'));

            $string = new Stringable('# Hello World');
            $result = $string->markdown();
            expect((string) $result)->toContain('<h1>Hello World</h1>');

            $result2 = $string->inlineMarkdown();
            expect((string) $result2)->toContain('Hello World');
        });

        it('Doit convertir l\'encodage', function (): void {
            $string = new Stringable('Hello');
            $result = $string->convertEncoding('UTF-8', 'UTF-8');
            expect((string) $result)->toBe('Hello');
        });

        it('Doit normaliser Unicode', function (): void {
            $string = new Stringable('Hello');
            $result = $string->normalize();
            expect((string) $result)->toBe('Hello');
        });

        it('Doit mesurer la longueur avec length()', function (): void {
            $string = new Stringable('Hello');
            expect($string->length())->toBe(5);

            $unicode = new Stringable('世界');
            expect($unicode->length())->toBe(2);
        });
    });

    describe('Chaînage fluide', function (): void {
        it('Doit permettre le chaînage de méthodes', function (): void {
            $result = (new Stringable('  HELLO_WORLD  '))
                ->trim()
                ->lower()
                ->replace('_', ' ')
                ->ucfirst();

            expect((string) $result)->toBe('Hello world');
        });

        it('Doit permettre des transformations complexes', function (): void {
            $result = (new Stringable('user_profile'))
                ->studly()
                ->plural()
                ->append('Controller');

            expect((string) $result)->toBe('UserProfilesController');
        });

        it('Doit combiner les méthodes conditionnelles', function (): void {
            $isAdmin = true;
            $result = (new Stringable('user'))
                ->when($isAdmin, fn($str): Stringable => $str->prepend('admin_'))
                ->append('_controller')
                ->camel();

            expect((string) $result)->toBe('adminUserController');
        });
    });

    describe('Cas limites', function (): void {
        it('Doit gérer les chaînes vides', function (): void {
            $string = new Stringable('');
            expect((string) $string->append('test'))->toBe('test');
            expect((string) $string->prepend('test'))->toBe('test');
            expect($string->length())->toBe(0);
            expect($string->isEmpty())->toBe(true);
            expect($string->contains('test'))->toBe(false);
            expect($string->startsWith('test'))->toBe(false);
            expect($string->endsWith('test'))->toBe(false);
        });

        it('Doit gérer les caractères Unicode', function (): void {
            $string = new Stringable('世界你好');
            expect($string->length())->toBe(4);
            expect((string) $string->substr(2, 2))->toBe('你好');
            expect((string) $string->reverse())->toBe('好你界世');
        });

        it('Doit gérer les positions hors limites', function (): void {
            $string = new Stringable('Hello');
            expect((string) $string->substr(10))->toBe('');
            expect((string) $string->substr(-10, 5))->toBe('Hello');
            expect($string->position('test'))->toBe(false);
        });
    });

    describe('Méthodes de découpage et suppression', function (): void {
		it('Doit supprimer le début avec chopStart()', function (): void {
			$string = new Stringable('/test/path');
			$result = $string->chopStart('/');
			expect((string) $result)->toBe('test/path');

			$string2 = new Stringable('test/path');
			$result2 = $string2->chopStart('/');
			expect((string) $result2)->toBe('test/path');
		});

		it('Doit supprimer la fin avec chopEnd()', function (): void {
			$string = new Stringable('test/path/');
			$result = $string->chopEnd('/');
			expect((string) $result)->toBe('test/path');

			$string2 = new Stringable('test/path');
			$result2 = $string2->chopEnd('/');
			expect((string) $result2)->toBe('test/path');
		});

		it('Doit prendre les premiers caractères avec take()', function (): void {
			$string = new Stringable('Hello World');
			$result = $string->take(5);
			expect((string) $result)->toBe('Hello');

			$result2 = $string->take(-5);
			expect((string) $result2)->toBe('World');
		});

		it('Doit convertir en Base64 et décoder', function (): void {
			$string = new Stringable('Hello World');
			$encoded = $string->toBase64();
			expect((string) $encoded)->toBe(base64_encode('Hello World'));

			$decoded = $encoded->fromBase64();
			expect((string) $decoded)->toBe('Hello World');
		});

		it('Doit convertir en Pascal case', function (): void {
			$string = new Stringable('hello_world');
			$result = $string->pascal();
			expect((string) $result)->toBe('HelloWorld');
		});

		it('Doit convertir en APA case', function (): void {
			$string = new Stringable('the lord of the rings');
			$result = $string->apa();
			expect((string) $result)->toBe('The Lord of the Rings');
		});
	});

	describe('Méthodes de vérification avancées', function (): void {
		it('Doit vérifier avec doesntContain()', function (): void {
			$string = new Stringable('Hello World');
			expect($string->doesntContain('Universe'))->toBe(true);
			expect($string->doesntContain('World'))->toBe(false);
			expect($string->doesntContain('world', true))->toBe(false);
		});

		it('Doit vérifier avec doesntEndWith()', function (): void {
			$string = new Stringable('Hello World');
			expect($string->doesntEndWith('Universe'))->toBe(true);
			expect($string->doesntEndWith('World'))->toBe(false);
		});

		it('Doit vérifier avec doesntStartWith()', function (): void {
			$string = new Stringable('Hello World');
			expect($string->doesntStartWith('Universe'))->toBe(true);
			expect($string->doesntStartWith('Hello'))->toBe(false);
		});
	});

	describe('Méthodes de transformation avancées', function (): void {
		it('Doit dédupliquer les caractères avec deduplicate()', function (): void {
			$string = new Stringable('Hello    World');
			$result = $string->deduplicate();
			expect((string) $result)->toBe('Hello World');

			$string2 = new Stringable('aaabbbccc');
			$result2 = $string2->deduplicate('a');
			expect((string) $result2)->toBe('abbbccc');
		});

		it('Doit convertir la casse avec convertCase()', function (): void {
			$string = new Stringable('hello world');
			$result = $string->convertCase(MB_CASE_TITLE);
			expect((string) $result)->toBe('Hello World');

			$string2 = new Stringable('HELLO WORLD');
			$result2 = $string2->convertCase(MB_CASE_LOWER);
			expect((string) $result2)->toBe('hello world');
		});

		it('Doit translittérer avec transliterate()', function (): void {
			$string = new Stringable('Café');
			$result = $string->transliterate();
			expect((string) $result)->toBe('Cafe');
		});

		it('Doit extraire les nombres avec numbers()', function (): void {
			$string = new Stringable('Hello 123 World 456');
			$result = $string->numbers();
			expect((string) $result)->toBe('123456');
		});

		it('Doit wrapper avec wordWrap()', function (): void {
			$string = new Stringable('Lorem ipsum dolor sit amet');
			$result = $string->wordWrap(10);
			expect((string) $result)->toBe("Lorem\nipsum\ndolor sit\namet");
		});

		it('Doit désencapsuler avec unwrap()', function (): void {
			$string = new Stringable('"Hello"');
			$result = $string->unwrap('"');
			expect((string) $result)->toBe('Hello');

			$string2 = new Stringable('<div>Hello</div>');
			$result2 = $string2->unwrap('<div>', '</div>');
			expect((string) $result2)->toBe('Hello');
		});

		it('Doit hacher avec hash()', function (): void {
			$string = new Stringable('password');
			$result = $string->hash('md5');
			expect((string) $result)->toBe(md5('password'));
		});
	});

	describe('Méthodes conditionnelles spécifiques', function (): void {
		it('Doit utiliser whenContains()', function (): void {
			$string = new Stringable('Hello World');
			$result = $string->whenContains('World', fn($str) => $str->append('!'));
			expect((string) $result)->toBe('Hello World!');

			$result2 = $string->whenContains('Universe', fn($str) => $str->append('!'), fn($str) => $str->append('?'));
			expect((string) $result2)->toBe('Hello World?');
		});

		it('Doit utiliser whenStartsWith()', function (): void {
			$string = new Stringable('Hello World');
			$result = $string->whenStartsWith('Hello', fn($str) => $str->append('!'));
			expect((string) $result)->toBe('Hello World!');

			$result2 = $string->whenStartsWith('Hi', fn($str) => $str->append('!'), fn($str) => $str->append('?'));
			expect((string) $result2)->toBe('Hello World?');
		});

		it('Doit utiliser whenEndsWith()', function (): void {
			$string = new Stringable('Hello World');
			$result = $string->whenEndsWith('World', fn($str) => $str->append('!'));
			expect((string) $result)->toBe('Hello World!');
		});

		it('Doit utiliser whenIs()', function (): void {
			$string = new Stringable('foobar');
			$result = $string->whenIs('foo*', fn($str) => $str->append('!'));
			expect((string) $result)->toBe('foobar!');
		});

		it('Doit utiliser whenTest()', function (): void {
			$string = new Stringable('Hello 123');
			$result = $string->whenTest('/\d+/', fn($str) => $str->append('!'));
			expect((string) $result)->toBe('Hello 123!');
		});
	});

	describe('Méthodes de gestion de caractères', function (): void {
		it('Doit récupérer un caractère avec charAt()', function (): void {
			$string = new Stringable('Hello');
			expect($string->charAt(1))->toBe('e');
			expect($string->charAt(10))->toBeFalsy();
		});

		it('Doit remplacer le début avec replaceStart()', function (): void {
			$string = new Stringable('Hello World');
			$result = $string->replaceStart('Hello', 'Hi');
			expect((string) $result)->toBe('Hi World');

			$string2 = new Stringable('World Hello');
			$result2 = $string2->replaceStart('Hello', 'Hi');
			expect((string) $result2)->toBe('World Hello');
		});

		it('Doit remplacer la fin avec replaceEnd()', function (): void {
			$string = new Stringable('Hello World');
			$result = $string->replaceEnd('World', 'Universe');
			expect((string) $result)->toBe('Hello Universe');

			$string2 = new Stringable('World Hello');
			$result2 = $string2->replaceEnd('World', 'Universe');
			expect((string) $result2)->toBe('World Hello');
		});

		it('Doit mettre en majuscules les mots avec ucwords()', function (): void {
			$string = new Stringable('hello world');
			$result = $string->ucwords();
			expect((string) $result)->toBe('Hello World');
		});

		it('Doit vérifier les correspondances avec isMatch()', function (): void {
			$string = new Stringable('Hello 123');
			expect($string->isMatch('/\d+/'))->toBe(true);
			expect($string->isMatch('/^\d+$/'))->toBe(false);
		});
	});

	describe('Méthodes ArrayAccess', function (): void {
		it('Doit implémenter ArrayAccess correctement', function (): void {
			$string = new Stringable('Hello');

			// Test offsetExists
			expect(isset($string[0]))->toBe(true);
			expect(isset($string[10]))->toBe(false);

			// Test offsetGet
			expect($string[0])->toBe('H');
			expect($string[1])->toBe('e');

			// Test offsetSet
			$string[0] = 'J';
			expect((string) $string)->toBe('Jello');
		});
	});

	describe('Méthodes de classe', function (): void {
		it('Doit récupérer le basename de classe avec classBasename()', function (): void {
			$string = new Stringable('App\\Http\\Controllers\\HomeController');
			$result = $string->classBasename();
			expect((string) $result)->toBe('HomeController');
		});
	});
});
