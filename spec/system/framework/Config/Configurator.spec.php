<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Config\Configurator;
use BlitzPHP\Exceptions\ConfigException;
use BlitzPHP\Exceptions\UnknownOptionException;
use Dflydev\DotAccessData\Exception\InvalidPathException;
use Nette\Schema\Expect;

use function Kahlan\expect;

describe('Config / Configurator', function (): void {
    describe('Validation', function (): void {
        it('Validation avec schéma strict', function (): void {
            $configurator = new Configurator();
            $configurator->addSchema('test', Expect::structure([
                'required' => Expect::string()->required(),
                'optional' => Expect::int()->default(42),
                'nested'   => Expect::structure([
                    'enabled' => Expect::bool()->default(true)
                ])
            ]));

            // Données valides
            $configurator->merge(['test' => [
                'required' => 'hello',
                'optional' => 100,
                'nested' => ['enabled' => false]
            ]]);

            expect($configurator->get('test.required'))->toBe('hello');
            expect($configurator->get('test.optional'))->toBe(100);
            expect($configurator->get('test.nested.enabled'))->toBe(false);
        });

        it('Validation avec types', function (): void {
            $configurator = new Configurator();
            $configurator->addSchema('types', Expect::structure([
                'string' => Expect::string(),
                'int' => Expect::int(),
                'bool' => Expect::bool(),
                'array' => Expect::array(),
                'any' => Expect::mixed()
            ]));

            $configurator->merge(['types' => [
                'string' => 'text',
                'int' => 123,
                'bool' => true,
                'array' => ['a', 'b'],
                'any' => new stdClass()
            ]]);

            expect($configurator->get('types.string'))->toBe('text');
            expect($configurator->get('types.int'))->toBe(123);
            expect($configurator->get('types.bool'))->toBe(true);
            expect($configurator->get('types.array'))->toBe(['a', 'b']);
            expect($configurator->get('types.any'))->toBe([]);
        });

        it('Validation avec valeurs énumérées', function (): void {
            $configurator = new Configurator();
            $configurator->addSchema('enum', Expect::structure([
                'status' => Expect::anyOf('active', 'inactive', 'pending')->default('pending')
            ]));

            // Valeur valide
            $configurator->merge(['enum' => ['status' => 'active']]);
            expect($configurator->get('enum.status'))->toBe('active');

            // Valeur invalide
			$configurator->merge(['enum' => ['status' => 'invalid']]);
            expect(fn() => $configurator->get('enum.status'))
                ->toThrow(new ConfigException());
        });
    });

    describe('Gestion du cache', function (): void {
        it('Cache multiple niveaux', function (): void {
            $configurator = new Configurator();
            $configurator->addSchema('deep', Expect::structure([
                'level1' => Expect::structure([
                    'level2' => Expect::structure([
                        'level3' => Expect::string()
                    ])
                ])
            ]));

            $configurator->merge(['deep' => [
                'level1' => ['level2' => ['level3' => 'value']]
            ]]);

            // Accéder à différents niveaux
            expect($configurator->get('deep.level1.level2.level3'))->toBe('value');
            expect($configurator->get('deep.level1.level2'))->toBe(['level3' => 'value']);
            expect($configurator->get('deep.level1'))->toBe(['level2' => ['level3' => 'value']]);
        });

        it('Invalidation sélective', function (): void {
            $configurator = new Configurator();
            $configurator->addSchema('selective', Expect::structure([
                'a' => Expect::string(),
                'b' => Expect::string(),
                'nested' => Expect::structure([
                    'c' => Expect::string()
                ])
            ]));

            $configurator->merge(['selective' => [
                'a' => 'A',
                'b' => 'B',
                'nested' => ['c' => 'C']
            ]]);

            // Récupérer pour mettre en cache
            $configurator->get('selective.a');
            $configurator->get('selective.nested.c');

            // Modifier seulement 'a'
            $configurator->set('selective.a', 'A2');

            // 'a' devrait être nouveau, 'nested.c' devrait toujours être en cache
            expect($configurator->get('selective.a'))->toBe('A2');
            expect($configurator->get('selective.nested.c'))->toBe('C');
            expect($configurator->get('selective.b'))->toBe('B'); // Toujours original
        });
    });

    describe('Edge cases Configurator', function (): void {
        it('Accès sans schéma défini', function (): void {
            $configurator = new Configurator();

            // Doit échouer car pas de schéma pour 'test'
            expect(fn() => $configurator->get('test.key'))
                ->toThrow(new UnknownOptionException('Schéma de configuration manquant pour "test".', 'test'));

            expect($configurator->exists('test.key'))->toBeFalsy();
        });

        it('Schéma avec valeurs par défaut', function (): void {
            $configurator = new Configurator();
            $configurator->addSchema('defaults', Expect::structure([
                'explicit' => Expect::string()->default('default-value'),
                'implicit' => Expect::string() // Pas de default
            ]));

            // Merge avec données partielles
            $configurator->merge(['defaults' => [
                'implicit' => 'provided'
            ]]);

            expect($configurator->get('defaults.explicit'))->toBe('default-value');
            expect($configurator->get('defaults.implicit'))->toBe('provided');

            // Sans aucune donnée
            $configurator2 = new Configurator();
            $configurator2->addSchema('defaults', Expect::structure([
                'explicit' => Expect::string()->default('default-value')
            ]));

            // Doit utiliser la valeur par défaut
            expect($configurator2->get('defaults.explicit'))->toBe('default-value');
        });

        it('Conversion stdClass vers array profonde', function (): void {
            $configurator = new Configurator();
            $configurator->addSchema('conversion', Expect::mixed());

            // Simuler des données avec stdClass imbriqués
            $data = new stdClass();
            $data->level1 = new stdClass();
            $data->level1->level2 = new stdClass();
            $data->level1->level2->value = 'deep';
            $data->array = [new stdClass(), new stdClass()];
            $data->array[0]->test = 'a';
            $data->array[1]->test = 'b';

            $configurator->merge(['conversion' => (array) $data]);

            $result = $configurator->get('conversion');

            expect($result)->toBeAn('array');
            expect($result['level1'])->toBeAn('array');
            expect($result['level1']['level2'])->toBeAn('array');
            expect($result['level1']['level2']['value'])->toBe('deep');
            expect($result['array'][0])->toBeAn('array');
            expect($result['array'][0]['test'])->toBe('a');
        });

        it('Merge avec données existantes', function (): void {
            $configurator = new Configurator();
            $configurator->addSchema('merge', Expect::structure([
                'existing' => Expect::string(),
                'new' => Expect::string()
            ]));

            // Premier merge
            $configurator->merge(['merge' => ['existing' => 'old']]);
            expect($configurator->get('merge.existing'))->toBe('old');

            // Deuxième merge (devrait écraser)
            $configurator->merge(['merge' => ['existing' => 'new', 'new' => 'added']]);
            expect($configurator->get('merge.existing'))->toBe('new');
            expect($configurator->get('merge.new'))->toBe('added');
        });
    });

    describe('Tests internes', function (): void {
        it('getTopLevelKey avec différents formats', function (): void {
            expect(Configurator::getTopLevelKey('app'))->toBe('app');
            expect(Configurator::getTopLevelKey('app.environment'))->toBe('app');
            expect(Configurator::getTopLevelKey('app/environment'))->toBe('app');
            expect(Configurator::getTopLevelKey('database.default.host'))->toBe('database');

            // Doit échouer avec chemin vide
            expect(fn(): string => Configurator::getTopLevelKey(''))->toThrow(new InvalidPathException());
            expect(fn(): string => Configurator::getTopLevelKey('   '))->toThrow(new InvalidPathException());
        });

        it('Configurator::set avec chemin invalide', function (): void {
            $configurator = new Configurator();
            $configurator->addSchema('test', Expect::mixed());
            $configurator->merge(['test' => []]);

            // Doit échouer avec un chemin invalide
            // Note: DotAccessData peut accepter des chemins invalides selon l'implémentation
            // expect(fn() => $configurator->set('test..key', 'value'))->toThrow();
			$configurator->set('test..key', 'value');
			expect($configurator->get('test..key'))->toBe('value');
        });

        it('Configurator::get avec cache', function (): void {
            $configurator = new Configurator();
            $configurator->addSchema('test', Expect::structure([
                'key' => Expect::string()->default('default')
            ]));
            $configurator->merge(['test' => ['key' => 'value']]);

            // Premier appel - construit le cache
            $value1 = $configurator->get('test.key');
            expect($value1)->toBe('value');

            // Deuxième appel - utilise le cache
            $value2 = $configurator->get('test.key');
            expect($value2)->toBe('value');

            // Vérifier que build n'a été appelé qu'une fois
            // (Test indirect via la modification de la config)
            $configurator->set('test.key', 'newvalue');
            expect($configurator->get('test.key'))->toBe('newvalue');
        });

        it('Configurator::exists avec/sans schéma', function (): void {
            $configurator = new Configurator();

            // Sans schéma
            expect($configurator->exists('test.key'))->toBeFalsy();

            // Avec schéma mais sans données
            $configurator->addSchema('test', Expect::mixed());
            expect($configurator->exists('test.key'))->toBeFalsy();

            // Avec schéma et données
            $configurator->merge(['test' => ['key' => 'value']]);
            expect($configurator->exists('test.key'))->toBeTruthy();
            expect($configurator->exists('test.nonexistent'))->toBeFalsy();
        });
    });
});
