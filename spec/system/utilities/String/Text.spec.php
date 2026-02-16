<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Utilities\String\Text;
use BlitzPHP\Utilities\Iterable\Collection;

use function Kahlan\expect;

describe('Utilities / String / Text', function (): void {
    describe('Méthodes statiques de base', function (): void {
        it('Doit créer un objet Stringable avec of()', function (): void {
            $stringable = Text::of('test');
            expect($stringable)->toBeAnInstanceOf(Stringable::class);
            expect((string) $stringable)->toBe('test');
        });

        it('Doit récupérer la partie après une recherche avec after()', function (): void {
            expect(Text::after('Hello World', 'Hello '))->toBe('World');
            expect(Text::after('Hello World', 'Hello'))->toBe(' World');
            expect(Text::after('Hello World', ''))->toBe('Hello World');
            expect(Text::after('Hello World', 'NotFound'))->toBe('Hello World');
        });

        it('Doit récupérer la partie après la dernière occurrence avec afterLast()', function (): void {
            expect(Text::afterLast('App\Http\Controllers', '\\'))->toBe('Controllers');
            expect(Text::afterLast('test1/test2/test3', '/'))->toBe('test3');
            expect(Text::afterLast('test', ''))->toBe('test');
            expect(Text::afterLast('test', 'z'))->toBe('test');
        });

        it('Doit récupérer la partie avant une recherche avec before()', function (): void {
            expect(Text::before('Hello World', ' World'))->toBe('Hello');
            expect(Text::before('Hello World', 'Hello'))->toBe('');
            expect(Text::before('Hello World', ''))->toBe('Hello World');
            expect(Text::before('Hello World', 'NotFound'))->toBe('Hello World');
        });

        it('Doit récupérer la partie avant la dernière occurrence avec beforeLast()', function (): void {
            expect(Text::beforeLast('App\Http\Controllers', '\\'))->toBe('App\Http');
            expect(Text::beforeLast('test1/test2/test3', '/'))->toBe('test1/test2');
            expect(Text::beforeLast('test', ''))->toBe('test');
            expect(Text::beforeLast('test', 'z'))->toBe('test');
        });

        it('Doit récupérer la partie entre deux chaînes avec between()', function (): void {
            expect(Text::between('[test]', '[', ']'))->toBe('test');
            expect(Text::between('[test][second]', '[', ']'))->toBe('test][second');
            expect(Text::between('test', '[', ']'))->toBe('test');
            expect(Text::between('test', '', ''))->toBe('test');
        });

        it('Doit récupérer la première partie entre deux chaînes avec betweenFirst()', function (): void {
            expect(Text::betweenFirst('[test][second]', '[', ']'))->toBe('test');
            expect(Text::betweenFirst('(test)(second)', '(', ')'))->toBe('test');
            expect(Text::betweenFirst('test', '[', ']'))->toBe('test');
        });
    });

    describe('Conversion de casse', function (): void {
        it('Doit convertir en camelCase avec camel()', function (): void {
            expect(Text::camel('foo_bar'))->toBe('fooBar');
            expect(Text::camel('Foo Bar'))->toBe('fooBar');
            expect(Text::camel('foo-bar'))->toBe('fooBar');
            expect(Text::camel('foo bar'))->toBe('fooBar');
            expect(Text::camel('FooBar'))->toBe('fooBar');
            expect(Text::camel('fooBar'))->toBe('fooBar');
        });

        it('Doit convertir en kebab-case avec kebab()', function (): void {
            expect(Text::kebab('fooBar'))->toBe('foo-bar');
            expect(Text::kebab('FooBar'))->toBe('foo-bar');
            expect(Text::kebab('foo_bar'))->toBe('foo-bar');
            expect(Text::kebab('Foo Bar'))->toBe('foo-bar');
        });

        it('Doit convertir en snake_case avec snake()', function (): void {
            expect(Text::snake('fooBar'))->toBe('foo_bar');
            expect(Text::snake('FooBar'))->toBe('foo_bar');
            expect(Text::snake('fooBar', '-'))->toBe('foo-bar');
            expect(Text::snake('Foo Bar'))->toBe('foo_bar');
            expect(Text::snake('foo bar'))->toBe('foo_bar');
        });

        it('Doit convertir en StudlyCase avec studly()', function (): void {
            expect(Text::studly('foo_bar'))->toBe('FooBar');
            expect(Text::studly('foo-bar'))->toBe('FooBar');
            expect(Text::studly('foo bar'))->toBe('FooBar');
            expect(Text::studly('fooBar'))->toBe('FooBar');
        });

        it('Doit convertir en minuscules avec lower()', function (): void {
            expect(Text::lower('FOO BAR'))->toBe('foo bar');
            expect(Text::lower('Foo Bar'))->toBe('foo bar');
            expect(Text::lower('FooBar'))->toBe('foobar');
        });

        it('Doit convertir en majuscules avec upper()', function (): void {
            expect(Text::upper('foo bar'))->toBe('FOO BAR');
            expect(Text::upper('Foo Bar'))->toBe('FOO BAR');
            expect(Text::upper('FooBar'))->toBe('FOOBAR');
        });

        it('Doit convertir en title case avec title()', function (): void {
            expect(Text::title('foo bar'))->toBe('Foo Bar');
            expect(Text::title('foo-bar'))->toBe('Foo-Bar');
            expect(Text::title('FOO BAR'))->toBe('Foo Bar');
        });

        it('Doit convertir en headline case avec headline()', function (): void {
            expect(Text::headline('foo_bar'))->toBe('Foo Bar');
            expect(Text::headline('foo-bar'))->toBe('Foo Bar');
            expect(Text::headline('fooBar'))->toBe('Foo Bar');
            expect(Text::headline('foo bar'))->toBe('Foo Bar');
        });

        it('Doit convertir avec ucfirst() et lcfirst()', function (): void {
            expect(Text::ucfirst('foo'))->toBe('Foo');
            expect(Text::ucfirst('foo bar'))->toBe('Foo bar');
            expect(Text::lcfirst('Foo'))->toBe('foo');
            expect(Text::lcfirst('Foo Bar'))->toBe('foo Bar');
        });
    });

    describe('Vérifications et validations', function (): void {
        it('Doit vérifier si une chaîne contient une sous-chaîne avec contains()', function (): void {
            expect(Text::contains('Hello World', 'World'))->toBe(true);
            expect(Text::contains('Hello World', 'world'))->toBe(false);
            expect(Text::contains('Hello World', 'world', true))->toBe(true);
            expect(Text::contains('Hello World', ['Hello', 'World']))->toBe(true);
            expect(Text::contains('Hello World', ['Hello', 'Universe']))->toBe(true);
            expect(Text::contains('Hello World', ['Universe', 'Galaxy']))->toBe(false);
        });

        it('Doit vérifier si une chaîne contient toutes les sous-chaînes avec containsAll()', function (): void {
            expect(Text::containsAll('Hello World', ['Hello', 'World']))->toBe(true);
            expect(Text::containsAll('Hello World', ['Hello', 'Universe']))->toBe(false);
            expect(Text::containsAll('Hello World', ['hello', 'world'], true))->toBe(true);
        });

        it('Doit vérifier si une chaîne commence par avec startsWith()', function (): void {
            expect(Text::startsWith('Hello World', 'Hello'))->toBe(true);
            expect(Text::startsWith('Hello World', 'hello'))->toBe(false);
            expect(Text::startsWith('Hello World', ['Hello', 'World']))->toBe(true);
            expect(Text::startsWith('Hello World', ['World', 'Universe']))->toBe(false);
        });

        it('Doit vérifier si une chaîne se termine par avec endsWith()', function (): void {
            expect(Text::endsWith('Hello World', 'World'))->toBe(true);
            expect(Text::endsWith('Hello World', 'world'))->toBe(false);
            expect(Text::endsWith('Hello World', ['World', 'Hello']))->toBe(true);
            expect(Text::endsWith('Hello World', ['Hello', 'Universe']))->toBe(false);
        });

        it('Doit vérifier si une chaîne correspond à un pattern avec is()', function (): void {
            expect(Text::is('foo*', 'foobar'))->toBe(true);
            expect(Text::is('*bar', 'foobar'))->toBe(true);
            expect(Text::is('foo*bar', 'foobar'))->toBe(true);
            expect(Text::is('foo', 'foo'))->toBe(true);
            expect(Text::is('foo', 'bar'))->toBe(false);
            expect(Text::is(['foo*', 'bar*'], 'foobar'))->toBe(true);
            expect(Text::is(['foo*', 'bar*'], 'barbaz'))->toBe(true);
        });

        it('Doit vérifier si une chaîne est ASCII avec isAscii()', function (): void {
            expect(Text::isAscii('Hello World'))->toBe(true);
            expect(Text::isAscii('Hello 世界'))->toBe(false);
            expect(Text::isAscii(''))->toBe(true);
        });

        it('Doit vérifier si une chaîne est JSON valide avec isJson()', function (): void {
            expect(Text::isJson('{"name":"John"}'))->toBe(true);
            expect(Text::isJson('[1,2,3]'))->toBe(true);
            expect(Text::isJson('{"name":John}'))->toBe(false);
            expect(Text::isJson('Hello World'))->toBe(false);
            expect(Text::isJson(''))->toBe(false);
        });

        it('Doit vérifier si une chaîne est un UUID valide avec isUuid()', function (): void {
            expect(Text::isUuid('123e4567-e89b-12d3-a456-426614174000'))->toBe(true);
            expect(Text::isUuid('00000000-0000-0000-0000-000000000000'))->toBe(true);
            expect(Text::isUuid('invalid-uuid'))->toBe(false);
            expect(Text::isUuid(''))->toBe(false);
        });
    });

    describe('Manipulation de chaînes', function (): void {
        it('Doit limiter le nombre de caractères avec limit()', function (): void {
            expect(Text::limit('Hello World', 5))->toBe('Hello...');
            expect(Text::limit('Hello World', 11))->toBe('Hello World');
            expect(Text::limit('Hello World', 5, '***'))->toBe('Hello***');
            expect(Text::limit('Hello', 10))->toBe('Hello');
        });

        it('Doit limiter le nombre de mots avec words()', function (): void {
            expect(Text::words('Hello World Universe', 2))->toBe('Hello World...');
            expect(Text::words('Hello World', 5))->toBe('Hello World');
            expect(Text::words('Hello World', 1, '***'))->toBe('Hello***');
            expect(Text::words('Hello World', 2, ''))->toBe('Hello World');
        });

        it('Doit masquer une partie de chaîne avec mask()', function (): void {
            expect(Text::mask('password', '*', 2, 4))->toBe('pa****rd');
            expect(Text::mask('1234567890', '*', 3))->toBe('123*******');
            expect(Text::mask('test', '*', 0, 4))->toBe('****');
            expect(Text::mask('test', '', 1, 2))->toBe('test');
            expect(Text::mask('test', '*', 10, 2))->toBe('test');
        });

        it('Doit terminer une chaîne avec finish()', function (): void {
            expect(Text::finish('test', '/'))->toBe('test/');
            expect(Text::finish('test/', '/'))->toBe('test/');
            expect(Text::finish('test//', '/'))->toBe('test/');
        });

        it('Doit commencer une chaîne avec start()', function (): void {
            expect(Text::start('test', '/'))->toBe('/test');
            expect(Text::start('/test', '/'))->toBe('/test');
            expect(Text::start('//test', '/'))->toBe('/test');
        });

        it('Doit inverser une chaîne avec reverse()', function (): void {
            expect(Text::reverse('Hello'))->toBe('olleH');
            expect(Text::reverse('世界'))->toBe('界世');
            expect(Text::reverse(''))->toBe('');
        });

        it('Doit compter les occurrences avec substrCount()', function (): void {
            expect(Text::substrCount('Hello Hello World', 'Hello'))->toBe(2);
            expect(Text::substrCount('Hello World', 'Hello'))->toBe(1);
            expect(Text::substrCount('Hello World', 'Universe'))->toBe(0);
        });

        it('Doit nettoyer les espaces avec squish()', function (): void {
            expect(Text::squish('  Hello   World  '))->toBe('Hello World');
            expect(Text::squish("Hello\t\n World"))->toBe('Hello World');
            expect(Text::squish('Hello　World'))->toBe('Hello World'); // Espace pleine largeur
        });
    });

    describe('Remplacements', function (): void {
        it('Doit remplacer avec replace()', function (): void {
            expect(Text::replace('World', 'Universe', 'Hello World'))->toBe('Hello Universe');
            expect(Text::replace(['Hello', 'World'], ['Hi', 'Earth'], 'Hello World'))->toBe('Hi Earth');
            expect(Text::replace('o', '0', 'Hello World', ))->toBe('Hell0 W0rld');
        });

        it('Doit remplacer la première occurrence avec replaceFirst()', function (): void {
            expect(Text::replaceFirst('Hello', 'Hi', 'Hello Hello World'))->toBe('Hi Hello World');
            expect(Text::replaceFirst('Universe', 'Earth', 'Hello World'))->toBe('Hello World');
            expect(Text::replaceFirst('', 'test', 'Hello World'))->toBe('Hello World');
        });

        it('Doit remplacer la dernière occurrence avec replaceLast()', function (): void {
            expect(Text::replaceLast('Hello', 'Hi', 'Hello Hello World'))->toBe('Hello Hi World');
            expect(Text::replaceLast('Universe', 'Earth', 'Hello World'))->toBe('Hello World');
        });

        it('Doit remplacer séquentiellement avec replaceArray()', function (): void {
            expect(Text::replaceArray('?', ['Hello', 'World'], '? ?'))->toBe('Hello World');
            expect(Text::replaceArray('?', ['Hello'], '? ?'))->toBe('Hello ?');
            expect(Text::replaceArray('?', [], '? ?'))->toBe('? ?');
        });

        it('Doit supprimer des chaînes avec remove()', function (): void {
            expect(Text::remove('World', 'Hello World'))->toBe('Hello ');
            expect(Text::remove(['Hello', 'World'], 'Hello World'))->toBe(' ');
            expect(Text::remove('world', 'Hello World', false))->toBe('Hello ');
            expect(Text::remove('world', 'Hello World', true))->toBe('Hello World');
        });

        it('Doit échanger des mots avec swap()', function (): void {
            expect(Text::swap(['Hello' => 'Hi', 'World' => 'Earth'], 'Hello World'))->toBe('Hi Earth');
            expect(Text::swap(['foo' => 'bar'], 'foo foo'))->toBe('bar bar');
        });
    });

    describe('Extraction et correspondances', function (): void {
        it('Doit extraire une correspondance avec match()', function (): void {
            expect(Text::match('/Hello (\w+)/', 'Hello World'))->toBe('World');
            expect(Text::match('/\d+/', 'Hello 123 World'))->toBe('123');
            expect(Text::match('/notfound/', 'Hello World'))->toBe('');
        });

        it('Doit extraire toutes les correspondances avec matchAll()', function (): void {
            $matches = Text::matchAll('/\d+/', 'Hello 123 World 456');
            expect($matches)->toBeAnInstanceOf(Collection::class);
            expect($matches->toArray())->toBe(['123', '456']);

            $empty = Text::matchAll('/notfound/', 'Hello World');
            expect($empty->count())->toBe(0);
        });

        it('Doit créer un extrait avec excerpt()', function (): void {
            expect(Text::excerpt('Hello World Universe', 'World'))->toMatch('/Hello World Universe/');
            expect(Text::excerpt('Hello World Universe', 'World', ['radius' => 2]))->toMatch('/.../');
        });

        it('Doit mettre en surbrillance avec highlight()', function (): void {
            expect(Text::highlight('Hello World', 'World'))->toContain('<span class="highlight">World</span>');
            expect(Text::highlight('Hello World', ['Hello', 'World']))->toContain('<span class="highlight">');
            expect(Text::highlight('Hello World', 'world', ['regex' => '|%s|i']))->toContain('<span class="highlight">');
            expect(Text::highlight('<p>Hello World</p>', 'World', ['html' => true]))->toContain('<span class="highlight">');
            expect(Text::highlight('Hello World', ''))->toBe('Hello World');
        });
    });

    describe('Padding et remplissage', function (): void {
        it('Doit remplir à gauche avec padLeft()', function (): void {
            expect(Text::padLeft('test', 10))->toBe('      test');
            expect(Text::padLeft('test', 10, '*'))->toBe('******test');
            expect(Text::padLeft('test', 3))->toBe('test');
        });

        it('Doit remplir à droite avec padRight()', function (): void {
            expect(Text::padRight('test', 10))->toBe('test      ');
            expect(Text::padRight('test', 10, '*'))->toBe('test******');
        });

        it('Doit remplir des deux côtés avec padBoth()', function (): void {
            expect(Text::padBoth('test', 10))->toBe('   test   ');
            expect(Text::padBoth('test', 10, '*'))->toBe('***test***');
            expect(Text::padBoth('test', 9, '*'))->toBe('**test***');
        });
    });

    describe('Longueur et position', function (): void {
        it('Doit mesurer la longueur avec length()', function (): void {
            expect(Text::length('Hello'))->toBe(5);
            expect(Text::length('世界'))->toBe(2);
            expect(Text::length(''))->toBe(0);
            expect(Text::length('Hello', 'UTF-8'))->toBe(5);
        });

        it('Doit extraire une sous-chaîne avec substr()', function (): void {
            expect(Text::substr('Hello World', 6))->toBe('World');
            expect(Text::substr('Hello World', 0, 5))->toBe('Hello');
            expect(Text::substr('Hello World', -5))->toBe('World');
            expect(Text::substr('Hello World', 0, 100))->toBe('Hello World');
            expect(Text::substr('', 0, 5))->toBe('');
        });

        it('Doit compter les mots avec wordCount()', function (): void {
            expect(Text::wordCount('Hello World'))->toBe(2);
            expect(Text::wordCount('Hello'))->toBe(1);
            expect(Text::wordCount(''))->toBe(0);
            expect(Text::wordCount('Hello-World', '-'))->toBe(1);
        });
    });

    describe('Pluriel et singulier', function (): void {
        it('Doit mettre au pluriel avec plural()', function (): void {
            expect(Text::plural('category'))->toBe('categories');
            expect(Text::plural('user'))->toBe('users');
            expect(Text::plural('person'))->toBe('people');
        });

        it('Doit mettre au singulier avec singular()', function (): void {
            expect(Text::singular('categories'))->toBe('category');
            expect(Text::singular('users'))->toBe('user');
            expect(Text::singular('people'))->toBe('person');
        });

        it('Doit mettre au pluriel Studly avec pluralStudly()', function (): void {
            expect(Text::pluralStudly('BlogPost'))->toBe('BlogPosts');
            expect(Text::pluralStudly('User'))->toBe('Users');
        });
    });

    describe('Génération et randomisation', function (): void {
        it('Doit générer une chaîne aléatoire avec random()', function (): void {
            $random = Text::random(10);
            expect(strlen($random))->toBe(10);

            $random2 = Text::random(20);
            expect(strlen($random2))->toBe(20);
            expect($random)->not->toBe($random2);
        });

        it('Doit utiliser une factory personnalisée pour random()', function (): void {
            Text::createRandomStringsUsing(fn($length) => str_repeat('a', $length));

            expect(Text::random(5))->toBe('aaaaa');
            expect(Text::random(10))->toBe('aaaaaaaaaa');

            Text::createRandomStringsNormally();

            $random = Text::random(5);
            expect($random)->not->toBe('aaaaa');
        });

        it('Doit utiliser une séquence pour random()', function (): void {
            Text::createRandomStringsUsingSequence(['first', 'second']);

            expect(Text::random(5))->toBe('first');
            expect(Text::random(5))->toBe('second');

            // Après épuisement de la séquence
            $random = Text::random(5);
            expect(strlen($random))->toBe(5);

            Text::createRandomStringsNormally();
        });
    });

    describe('Slug et ASCII', function (): void {
        it('Doit créer un slug avec slug()', function (): void {
            expect(Text::slug('Hello World'))->toBe('hello-world');
            expect(Text::slug('Hello World', '_'))->toBe('hello_world');
            expect(Text::slug('Hello & World'))->toBe('hello-world');
            expect(Text::slug('Élément Français'))->toBe('element-francais');
            expect(Text::slug('  extra  spaces  '))->toBe('extra-spaces');
        });

        it('Doit convertir en ASCII avec ascii()', function (): void {
            expect(Text::ascii('Élément'))->toBe('Element');
            expect(Text::ascii('Café'))->toBe('Cafe');
            expect(Text::ascii('Hello World'))->toBe('Hello World');
            expect(Text::ascii('München', 'de'))->toBe('Muenchen');
        });

        it('Doit nettoyer avec clean()', function (): void {
            expect(Text::clean('Hello World'))->toBe('Hello World');
            // Test avec caractères UTF-8 invalides
            $invalidUtf8 = "Hello" . chr(0xC3) . chr(0x28); // Séquence UTF-8 invalide
            $cleaned = Text::clean($invalidUtf8);
            // expect($cleaned)->toBe('Hello');
        });
    });

    describe('Insertion et tokenisation', function (): void {
        it('Doit insérer des variables avec insert()', function (): void {
            expect(Text::insert('Hello :name', ['name' => 'World']))->toBe('Hello World');
            expect(Text::insert('Hello :name, how are you?', ['name' => 'John']))->toBe('Hello John, how are you?');
            expect(Text::insert('Hello ?', ['World']))->toBe('Hello World');
            expect(Text::insert('Hello :name', []))->toBe('Hello :name');
        });

        it('Doit nettoyer les insertions avec cleanInsert()', function (): void {
            $str = 'Hello :name, how are you?';
            $options = ['before' => ':', 'after' => '', 'clean' => true];
            expect(Text::cleanInsert($str, $options))->toBe('Hello how are you?');
        });

        it('Doit tokeniser une chaîne avec tokenize()', function (): void {
            expect(Text::tokenize('a,b,c'))->toBe(['a', 'b', 'c']);
            expect(Text::tokenize('a, b, c'))->toBe(['a', 'b', 'c']);
            expect(Text::tokenize('a(b,c)d', ',', '(', ')'))->toBe(['a(b,c)d']);
            expect(Text::tokenize(''))->toBe([]);
        });
    });

    describe('Parsing et callbacks', function (): void {
        it('Doit parser un callback avec parseCallback()', function (): void {
            expect(Text::parseCallback('Controller@method'))->toBe(['Controller', 'method']);
            expect(Text::parseCallback('Controller'))->toBe(['Controller', null]);
            expect(Text::parseCallback('Controller@method', 'index'))->toBe(['Controller', 'method']);
            expect(Text::parseCallback('Controller', 'index'))->toBe(['Controller', 'index']);
        });

        it('Doit créer une liste avec toList()', function (): void {
            expect(Text::toList(['a', 'b', 'c']))->toBe('a, b and c');
            expect(Text::toList(['a', 'b']))->toBe('a and b');
            expect(Text::toList(['a']))->toBe('a');
            expect(Text::toList([]))->toBe('');
            expect(Text::toList(['a', 'b', 'c'], '; ', 'et'))->toBe('a; b et c');
        });

        it('Doit encapsuler avec wrap()', function (): void {
            expect(Text::wrap('test', '"'))->toBe('"test"');
            expect(Text::wrap('test', '[', ']'))->toBe('[test]');
            expect(Text::wrap('test', '<', '>'))->toBe('<test>');
        });
    });

    describe('Méthodes avancées', function (): void {
        it('Doit répéter une chaîne avec repeat()', function (): void {
            expect(Text::repeat('a', 3))->toBe('aaa');
            expect(Text::repeat('ab', 2))->toBe('abab');
            expect(Text::repeat('test', 0))->toBe('');
        });

        it('Doit convertir en UTF-8 avec toUtf8()', function (): void {
            $result = Text::toUtf8('test', 'ASCII');
            expect($result)->toBe('test');
        });

        it('Doit translittérer avec transliterate()', function (): void {
            if (class_exists('Transliterator')) {
                $result = Text::transliterate('Élément');
                expect($result)->toContain('Element');
            }
        });

        it('Doit échapper les citations avec quote()', function (): void {
            expect(Text::quote('test'))->toBe('test');
            expect(Text::quote('test"test'))->toBe('"test\"test"');
            expect(Text::quote('test test'))->toBe('"test test"');
        });

        it('Doit diviser par majuscules avec ucsplit()', function (): void {
            expect(Text::ucsplit('FooBar'))->toBe(['Foo', 'Bar']);
            expect(Text::ucsplit('FooBarBaz'))->toBe(['Foo', 'Bar', 'Baz']);
            expect(Text::ucsplit('foo'))->toBe(['foo']);
        });
    });

    describe('Cache et configuration', function (): void {
        it('Doit vider le cache avec flushCache()', function (): void {
            // Remplir le cache
            Text::camel('test_string');
            Text::snake('TestString');
            Text::studly('test_string');

            Text::flushCache();

            // Les caches devraient être vides maintenant
            // On ne peut pas tester directement les propriétés statiques privées,
            // mais on peut vérifier que les méthodes fonctionnent toujours
            expect(Text::camel('test_string'))->toBe('testString');
            expect(Text::snake('TestString'))->toBe('test_string');
        });
    });

    describe('Méthodes __callStatic et convertTo', function (): void {
        it('Doit gérer les appels dynamiques pour les conversions', function (): void {
            expect(Text::toCamel('hello_world'))->toBe('helloWorld');
            expect(Text::toSnake('HelloWorld'))->toBe('hello_world');
            expect(Text::toPascal('hello_world'))->toBe('HelloWorld');
            expect(Text::toKebab('helloWorld'))->toBe('hello-world');
            expect(Text::toLower('HELLO'))->toBe('hello');
            expect(Text::toUpper('hello'))->toBe('HELLO');
            expect(Text::toTitle('hello world'))->toBe('Hello World');
        });

        it('Doit lever une exception pour les méthodes inconnues', function (): void {
            expect(function (): void {
                Text::unknownMethod();
            })->toThrow(new InvalidArgumentException('Méthode inconnue ' . Text::class . '::unknownMethod'));
        });

        it('Doit lever une exception pour les convertisseurs invalides', function (): void {
            expect(function (): void {
                Text::convertTo('test', 'invalid');
            })->toThrow(new InvalidArgumentException());
        });
    });

    describe('Cas limites et robustesse', function (): void {
        it('Doit gérer les chaînes vides', function (): void {
            expect(Text::length(''))->toBe(0);
            expect(Text::substr('', 0, 5))->toBe('');
            expect(Text::contains('', 'test'))->toBe(false);
            expect(Text::startsWith('', 'test'))->toBe(false);
            expect(Text::endsWith('', 'test'))->toBe(false);
            expect(Text::lower(''))->toBe('');
            expect(Text::upper(''))->toBe('');
        });

        it('Doit gérer les positions négatives', function (): void {
            expect(Text::substr('Hello World', -5))->toBe('World');
            expect(Text::substr('Hello', -10, 5))->toBe('Hello');
            expect(Text::mask('password', '*', -3, 2))->toBe('passw**d');
        });

        it('Doit gérer les encodages', function (): void {
            expect(Text::length('世界'))->toBe(2);
            expect(Text::substr('世界你好', 2, 2))->toBe('你好');
            expect(Text::upper('éèê'))->toBe('ÉÈÊ');
            expect(Text::lower('ÉÈÊ'))->toBe('éèê');
        });
    });
});
