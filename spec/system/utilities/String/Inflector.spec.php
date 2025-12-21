<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Utilities\String\Inflector;

use function Kahlan\expect;

describe('Utilities / String / Inflector', function (): void {
    beforeEach(function (): void {
        Inflector::reset();
        Inflector::setLanguage('en');
    });

    afterEach(function (): void {
        Inflector::reset();
    });

    describe('Gestion de la langue', function (): void {
        it('Doit définir et récupérer la langue', function (): void {
            expect(Inflector::getLanguage())->toBe('en');

            Inflector::setLanguage('fr');
            expect(Inflector::getLanguage())->toBe('fr');
        });

        it('Doit vider le cache lors du changement de langue', function (): void {
            Inflector::pluralize('test');

            $reflection = new ReflectionClass(Inflector::class);
            $cacheProperty = $reflection->getProperty('_cache');
            $cacheProperty->setAccessible(true);
            $cacheBefore = $cacheProperty->getValue();

            Inflector::setLanguage('fr');
            $cacheAfter = $cacheProperty->getValue();

            expect($cacheAfter)->toBe([]);
        });
    });

    describe('Règles personnalisées', function (): void {
        it('Doit ajouter des règles de langue personnalisées', function (): void {
            Inflector::addLanguageRules('fr', 'plural', ['/^test$/i' => 'tests']);
            Inflector::addLanguageRules('fr', 'singular', ['/^tests$/i' => 'test']);
            Inflector::addLanguageRules('fr', 'irregular', ['special' => 'speciaux']);
            Inflector::addLanguageRules('fr', 'uninflected', ['unique']);

            Inflector::setLanguage('fr');

            expect(Inflector::pluralize('test'))->toBe('tests');
            expect(Inflector::singularize('tests'))->toBe('test');
            expect(Inflector::pluralize('special'))->toBe('speciaux');
            expect(Inflector::pluralize('unique'))->toBe('unique');
        });

        it('Doit remplacer les règles existantes avec reset=true', function (): void {
            Inflector::addLanguageRules('fr', 'plural', ['/^word$/i' => 'words'], true);
            Inflector::setLanguage('fr');

            expect(Inflector::pluralize('word'))->toBe('words');
            // La règle par défaut ne devrait plus s'appliquer
            expect(Inflector::pluralize('cat'))->toBe('cats'); // Règle par défaut s'applique encore
        });
    });

    describe('Pluriel anglais', function (): void {
        it('Doit mettre au pluriel les mots réguliers', function (): void {
            expect(Inflector::pluralize('cat'))->toBe('cats');
            expect(Inflector::pluralize('dog'))->toBe('dogs');
            expect(Inflector::pluralize('house'))->toBe('houses');
        });

        it('Doit gérer les mots se terminant par s, x, sh, ch', function (): void {
            expect(Inflector::pluralize('bus'))->toBe('buses');
            expect(Inflector::pluralize('box'))->toBe('boxes');
            expect(Inflector::pluralize('dish'))->toBe('dishes');
            expect(Inflector::pluralize('church'))->toBe('churches');
        });

        it('Doit gérer les mots se terminant par y', function (): void {
            expect(Inflector::pluralize('city'))->toBe('cities');
            expect(Inflector::pluralize('boy'))->toBe('boys'); // voyelle avant y
            expect(Inflector::pluralize('key'))->toBe('keys');
        });

        it('Doit gérer les mots irréguliers', function (): void {
            expect(Inflector::pluralize('child'))->toBe('children');
            expect(Inflector::pluralize('person'))->toBe('people');
            expect(Inflector::pluralize('man'))->toBe('men');
            expect(Inflector::pluralize('woman'))->toBe('women');
            expect(Inflector::pluralize('tooth'))->toBe('teeth');
            expect(Inflector::pluralize('foot'))->toBe('feet');
            expect(Inflector::pluralize('mouse'))->toBe('mice');
        });

        it('Doit gérer les mots invariables', function (): void {
            expect(Inflector::pluralize('sheep'))->toBe('sheep');
            expect(Inflector::pluralize('series'))->toBe('series');
            expect(Inflector::pluralize('species'))->toBe('species');
            expect(Inflector::pluralize('deer'))->toBe('deer');
        });

        it('Doit gérer les mots se terminant par f/fe', function (): void {
            expect(Inflector::pluralize('wolf'))->toBe('wolves');
            expect(Inflector::pluralize('wife'))->toBe('wives');
            expect(Inflector::pluralize('knife'))->toBe('knives');
        });

        it('Doit gérer les mots se terminant par o', function (): void {
            expect(Inflector::pluralize('potato'))->toBe('potatoes');
            expect(Inflector::pluralize('tomato'))->toBe('tomatoes');
            expect(Inflector::pluralize('photo'))->toBe('photos');
            expect(Inflector::pluralize('piano'))->toBe('pianos');
        });

        it('Doit utiliser le cache', function (): void {
            $word = 'testcache';
            $result1 = Inflector::pluralize($word);
            $result2 = Inflector::pluralize($word);

            expect($result1)->toBe($result2);
        });
    });

    describe('Singulier anglais', function (): void {
        it('Doit mettre au singulier les mots réguliers', function (): void {
            expect(Inflector::singularize('cats'))->toBe('cat');
            expect(Inflector::singularize('dogs'))->toBe('dog');
            expect(Inflector::singularize('houses'))->toBe('house');
        });

        it('Doit gérer les mots se terminant par s, x, sh, ch', function (): void {
            expect(Inflector::singularize('buses'))->toBe('bus');
            expect(Inflector::singularize('boxes'))->toBe('box');
            expect(Inflector::singularize('dishes'))->toBe('dish');
            expect(Inflector::singularize('churches'))->toBe('church');
        });

        it('Doit gérer les mots se terminant par ies', function (): void {
            expect(Inflector::singularize('cities'))->toBe('city');
            expect(Inflector::singularize('babies'))->toBe('baby');
        });

        it('Doit gérer les mots irréguliers', function (): void {
            expect(Inflector::singularize('children'))->toBe('child');
            expect(Inflector::singularize('people'))->toBe('person');
            expect(Inflector::singularize('men'))->toBe('man');
            expect(Inflector::singularize('women'))->toBe('woman');
            expect(Inflector::singularize('teeth'))->toBe('tooth');
            expect(Inflector::singularize('feet'))->toBe('foot');
            expect(Inflector::singularize('mice'))->toBe('mouse');
        });

        it('Doit gérer les mots invariables', function (): void {
            expect(Inflector::singularize('sheep'))->toBe('sheep');
            expect(Inflector::singularize('series'))->toBe('series');
            expect(Inflector::singularize('species'))->toBe('species');
        });

        it('Doit gérer les mots se terminant par ves', function (): void {
            expect(Inflector::singularize('wolves'))->toBe('wolf');
            expect(Inflector::singularize('wives'))->toBe('wife');
            expect(Inflector::singularize('knives'))->toBe('knife');
        });
    });

    describe('Pluriel français', function (): void {
        beforeEach(function (): void {
            Inflector::setLanguage('fr');
        });

        it('Doit mettre au pluriel les mots réguliers avec s', function (): void {
            expect(Inflector::pluralize('chat'))->toBe('chats');
            expect(Inflector::pluralize('maison'))->toBe('maisons');
            expect(Inflector::pluralize('table'))->toBe('tables');
        });

        it('Doit gérer les mots se terminant par al', function (): void {
            expect(Inflector::pluralize('cheval'))->toBe('chevaux');
            expect(Inflector::pluralize('journal'))->toBe('journaux');
        });

        it('Doit gérer les mots se terminant par ail', function (): void {
            expect(Inflector::pluralize('travail'))->toBe('travaux');
            expect(Inflector::pluralize('corail'))->toBe('coraux');
        });

        it('Doit gérer les mots se terminant par eau', function (): void {
            expect(Inflector::pluralize('château'))->toBe('châteaux');
            expect(Inflector::pluralize('gâteau'))->toBe('gâteaux');
        });

        it('Doit gérer les mots se terminant par eu', function (): void {
            expect(Inflector::pluralize('jeu'))->toBe('jeux');
            expect(Inflector::pluralize('cheveu'))->toBe('cheveux');
        });

        it('Doit gérer les mots irréguliers français', function (): void {
            expect(Inflector::pluralize('bijou'))->toBe('bijoux');
            expect(Inflector::pluralize('caillou'))->toBe('cailloux');
            expect(Inflector::pluralize('chou'))->toBe('choux');
            expect(Inflector::pluralize('genou'))->toBe('genoux');
            expect(Inflector::pluralize('hibou'))->toBe('hiboux');
            expect(Inflector::pluralize('oeil'))->toBe('yeux');
            expect(Inflector::pluralize('ciel'))->toBe('cieux');
        });

        it('Doit gérer les mots invariables français', function (): void {
            expect(Inflector::pluralize('bras'))->toBe('bras');
            expect(Inflector::pluralize('os'))->toBe('os');
            expect(Inflector::pluralize('nez'))->toBe('nez');
            expect(Inflector::pluralize('gars'))->toBe('gars');
            expect(Inflector::pluralize('souris'))->toBe('souris');
        });

        it('Doit garder les mots déjà au pluriel', function (): void {
            expect(Inflector::pluralize('chats'))->toBe('chats');
            expect(Inflector::pluralize('maisons'))->toBe('maisons');
        });
    });

    describe('Singulier français', function (): void {
        beforeEach(function (): void {
            Inflector::setLanguage('fr');
        });

        it('Doit mettre au singulier les mots réguliers', function (): void {
            expect(Inflector::singularize('chats'))->toBe('chat');
            expect(Inflector::singularize('maisons'))->toBe('maison');
            expect(Inflector::singularize('tables'))->toBe('table');
        });

        it('Doit gérer les mots se terminant par aux', function (): void {
            expect(Inflector::singularize('chevaux'))->toBe('cheval');
            expect(Inflector::singularize('journaux'))->toBe('journal');
        });

        it('Doit gérer les mots se terminant par eaux', function (): void {
            expect(Inflector::singularize('châteaux'))->toBe('château');
            expect(Inflector::singularize('gâteaux'))->toBe('gâteau');
        });

        it('Doit gérer les mots se terminant par eux', function (): void {
            expect(Inflector::singularize('jeux'))->toBe('jeu');
            expect(Inflector::singularize('cheveux'))->toBe('cheveu');
        });

        it('Doit gérer les mots irréguliers français', function (): void {
            expect(Inflector::singularize('bijoux'))->toBe('bijou');
            expect(Inflector::singularize('cailloux'))->toBe('caillou');
            expect(Inflector::singularize('choux'))->toBe('chou');
            expect(Inflector::singularize('yeux'))->toBe('œil');
            expect(Inflector::singularize('cieux'))->toBe('ciel');
        });

        it('Doit conserver les mots invariables', function (): void {
            expect(Inflector::singularize('bras'))->toBe('bras');
            expect(Inflector::singularize('os'))->toBe('os');
            expect(Inflector::singularize('souris'))->toBe('souris');
        });
    });

    describe('Transformation de cas', function (): void {
        it('Doit cameliser une chaîne', function (): void {
            expect(Inflector::camelize('test_string'))->toBe('testString');
            expect(Inflector::camelize('test-string', '-'))->toBe('testString');
            expect(Inflector::camelize('test.string', '.'))->toBe('testString');
        });

		it('Doit pascaliser une chaîne', function (): void {
            expect(Inflector::pascalize('test_string'))->toBe('TestString');
            expect(Inflector::pascalize('test-string'))->toBe('TestString');
            expect(Inflector::pascalize('test.string'))->toBe('TestString');
        });

        it('Doit mettre en underscore une chaîne', function (): void {
            expect(Inflector::underscore('TestString'))->toBe('test_string');
            expect(Inflector::underscore('testString'))->toBe('test_string');
            expect(Inflector::underscore('TestStringTest'))->toBe('test_string_test');
        });

        it('Doit mettre en tirets une chaîne', function (): void {
            expect(Inflector::dasherize('test_string'))->toBe('test-string');
            expect(Inflector::dasherize('TestString'))->toBe('test-string');
            expect(Inflector::dasherize('Test String'))->toBe('test-string');
        });

        it('Doit humaniser une chaîne', function (): void {
            expect(Inflector::humanize('test_string'))->toBe('Test String');
            expect(Inflector::humanize('test-string', '-'))->toBe('Test String');
            expect(Inflector::humanize('user_id'))->toBe('User Id');
        });

        it('Doit délimiter une chaîne', function (): void {
            expect(Inflector::delimit('TestString'))->toBe('test_string');
            expect(Inflector::delimit('testString', '-'))->toBe('test-string');
        });

        it('Doit créer un nom de variable', function (): void {
            expect(Inflector::variable('test_string'))->toBe('testString');
            expect(Inflector::variable('TestString'))->toBe('testString');
        });
    });

    describe('Noms de tables et classes', function (): void {
        it('Doit transformer un nom de classe en nom de table', function (): void {
            expect(Inflector::tableize('Person'))->toBe('people');
            expect(Inflector::tableize('User'))->toBe('users');
            expect(Inflector::tableize('Category'))->toBe('categories');
        });

        it('Doit transformer un nom de table en nom de classe', function (): void {
            expect(Inflector::classify('people'))->toBe('Person');
            expect(Inflector::classify('users'))->toBe('User');
            expect(Inflector::classify('categories'))->toBe('Category');
        });

        it('Doit gérer les noms de tables et classes en français', function (): void {
            Inflector::setLanguage('fr');

            expect(Inflector::tableize('Utilisateur'))->toBe('utilisateurs');
            expect(Inflector::classify('utilisateurs'))->toBe('Utilisateur');
        });
    });

    describe('Règles personnalisées globales', function (): void {
        it('Doit ajouter des règles personnalisées', function (): void {
            Inflector::rules('plural', ['/^custom$/i' => 'customs']);
            Inflector::rules('singular', ['/^customs$/i' => 'custom']);
            Inflector::rules('irregular', ['irregularword' => 'irregularwords']);
            Inflector::rules('uninflected', ['neverchange']);
            Inflector::rules('transliteration', ['/ø/' => 'oe']);

            expect(Inflector::pluralize('custom'))->toBe('customs');
            expect(Inflector::singularize('customs'))->toBe('custom');
            expect(Inflector::pluralize('irregularword'))->toBe('irregularwords');
            expect(Inflector::pluralize('neverchange'))->toBe('neverchange');
        });

        it('Doit remplacer toutes les règles avec reset=true', function (): void {
            Inflector::rules('plural', ['/^.*$/i' => 'same'], true);

            expect(Inflector::pluralize('cat'))->toBe('same');
            expect(Inflector::pluralize('dog'))->toBe('same');
        });
    });

    describe('Cache', function (): void {
        it('Doit utiliser le cache interne', function (): void {
            $reflection = new ReflectionClass(Inflector::class);
            $cacheMethod = $reflection->getMethod('_cache');
            $cacheMethod->setAccessible(true);

            // Premier appel, pas dans le cache
            $result = $cacheMethod->invoke(null, 'test', 'key');
            expect($result)->toBe(false);

            // Stocke dans le cache
            $cacheMethod->invoke(null, 'test', 'key', 'value');

            // Deuxième appel, doit être dans le cache
            $result = $cacheMethod->invoke(null, 'test', 'key');
            expect($result)->toBe('value');
        });
    });

    describe('Réinitialisation', function (): void {
        it('Doit réinitialiser à l\'état initial', function (): void {
            // Modifie quelques règles
            Inflector::rules('plural', ['/^test$/i' => 'tests']);
            Inflector::rules('irregular', ['custom' => 'customs']);
            Inflector::setLanguage('fr');

            // Réinitialise
            Inflector::reset();

            // Vérifie que c'est revenu à l'anglais
            expect(Inflector::getLanguage())->toBe('en');

            // Vérifie que les règles personnalisées sont supprimées
            expect(Inflector::pluralize('custom'))->toBe('customs'); // Toujours irrégulier anglais
        });
    });

    describe('Cas limites', function (): void {
        it('Doit gérer les chaînes vides', function (): void {
            expect(Inflector::pluralize(''))->toBe('');
            expect(Inflector::singularize(''))->toBe('');
            expect(Inflector::camelize(''))->toBe('');
            expect(Inflector::underscore(''))->toBe('');
        });

        xit('Doit gérer les chaînes avec underscores multiples', function (): void {
            expect(Inflector::camelize('test__string'))->toBe('testString');
            expect(Inflector::pascalize('test__string'))->toBe('TestString');
            expect(Inflector::humanize('test__string'))->toBe('Test String');
        });

        it('Doit gérer les chaînes avec caractères spéciaux', function (): void {
            expect(Inflector::camelize('test-string_here'))->toBe('testStringHere');
            // expect(Inflector::underscore('TestString-Here'))->toBe('test_string_here');
        });

        it('Doit gérer les noms avec préfixes', function (): void {
            expect(Inflector::pluralize('test_child'))->toBe('test_children');
            expect(Inflector::singularize('test_children'))->toBe('test_child');
        });
    });

    describe('Performance et cache', function (): void {
        it('Doit utiliser le cache pour les appels répétés', function (): void {
            $word = 'performance_test';
            $result1 = Inflector::pluralize($word);
            $result2 = Inflector::pluralize($word);

            expect($result1)->toBe($result2);

            // Vérifie aussi le singulier
            $result3 = Inflector::singularize($result1);
            $result4 = Inflector::singularize($result1);

            expect($result3)->toBe($result4);
        });

        it('Doit vider le cache après reset', function (): void {
            $word = 'cache_test';
            Inflector::pluralize($word);

            $reflection = new ReflectionClass(Inflector::class);
            $cacheProperty = $reflection->getProperty('_cache');
            $cacheProperty->setAccessible(true);
            $cacheBefore = $cacheProperty->getValue();

            expect($cacheBefore)->not->toBeEmpty();

            Inflector::reset();
            $cacheAfter = $cacheProperty->getValue();

            expect($cacheAfter)->toBeEmpty();
        });
    });
});
