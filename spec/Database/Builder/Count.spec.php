<?php

use BlitzPHP\Database\BaseBuilder;
use BlitzPHP\Spec\Mock\MockConnection;

describe("Databaase / Query Builder : Comptage", function() {

    beforeEach(function() {
        $this->builder = new BaseBuilder(new MockConnection([]));
    });

    it(": Nombre de ligne", function() {
        $builder = $this->builder->testMode()->from('jobs j');
        
        expect($builder->count())->toBe('SELECT COUNT(*) As num_rows FROM jobs As j');
    });
    
    it(": Nombre de ligne avec condition", function() {
        $builder = $this->builder->testMode()->from('jobs j')->where('id >', 3);
        
        expect($builder->count())->toBe('SELECT COUNT(*) As num_rows FROM jobs As j WHERE id > 3');
    });
    
    it(": Nombre de ligne avec regroupement", function() {
        $builder = $this->builder->testMode()->from('jobs j')->where('id >', 3)->groupBy('id');
        
        expect($builder->count())->toBe('SELECT COUNT(*) As num_rows FROM ( SELECT * FROM jobs As j WHERE id > 3 GROUP BY id ) BLITZ_count_all_results');
    });
    
    it(": Compter tous les résultats avec GroupBy et le prefixe de la base de données", function() {
        $this->builder->db()->setPrefix('db_');
        $builder = $this->builder->testMode()->select('j.*')->from('jobs j')->where('id >', 3)->groupBy('id');
        
        expect($builder->count())->toMatch('/^SELECT COUNT\(\*\) As num_rows FROM \( SELECT j_(?:[a-z0-9]+)\.\* FROM db_jobs As j_(?:[a-z0-9]+) WHERE id > 3 GROUP BY id \) BLITZ_count_all_results$/'); 
    });
    
    it(": Compter tous les résultats avec GroupBy et Having", function() {
        $builder = $this->builder->testMode()->from('jobs j')->where('id >', 3)->groupBy('id')->having('1=1');
        
        expect($builder->count())->toBe('SELECT COUNT(*) As num_rows FROM ( SELECT * FROM jobs As j WHERE id > 3 GROUP BY id HAVING 1=1 ) BLITZ_count_all_results');
    });
    
    it(": Compter tous les résultats avec Having uniquement", function() {
        $builder = $this->builder->testMode()->from('jobs j')->where('id >', 3)->having('1=1');
        
        expect($builder->count())->toBe('SELECT COUNT(*) As num_rows FROM jobs As j WHERE id > 3 HAVING 1=1');
    });
});
