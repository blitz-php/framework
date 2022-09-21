<?php

use BlitzPHP\Database\BaseBuilder;
use BlitzPHP\Spec\Mock\MockConnection;

describe("Database / Query Builder : Alias", function() {

    beforeEach(function() {
        $this->builder = new BaseBuilder(new MockConnection([]));
    });

    it(": Alias simple", function() {
        $builder = $this->builder->from('jobs j');
        expect($builder->sql())->toBe('SELECT * FROM jobs As j');
        
        $builder = $this->builder->from('jobs As j');
        expect($builder->sql())->toBe('SELECT * FROM jobs As j');
    });
    
    it(": Prise en charge des tableau d'alias", function() {
        $builder = $this->builder->from(['jobs j', 'users u']);
        expect($builder->sql())->toBe('SELECT * FROM jobs As j, users As u');
        
        $builder = $this->builder->from(['jobs j', 'users as u']);
        expect($builder->sql())->toBe('SELECT * FROM jobs As j, users As u');
    });
    
    it(": Prise en charge de chaine d'alias", function() {
        $builder = $this->builder->from('jobs j, users u');
        expect($builder->sql())->toBe('SELECT * FROM jobs As j, users As u');
        
        $builder = $this->builder->from('jobs j, users AS u');
        expect($builder->sql())->toBe('SELECT * FROM jobs As j, users As u');
    });
    
    it(": Prise en charge de chaine d'alias", function() {
        $builder = $this->builder->from('jobs j, users u');
        expect($builder->sql())->toBe('SELECT * FROM jobs As j, users As u');
        
        $builder = $this->builder->from('jobs j, users AS u');
        expect($builder->sql())->toBe('SELECT * FROM jobs As j, users As u');
    });

    it(": Alias 'Join' avec un nom de table court", function() {
        $this->builder->db()->setPrefix('db_');

        $builder = $this->builder->from('jobs')->join('users as u', ['u.id' => 'jobs.id']);
        expect($builder->sql())->toMatch('/^SELECT \* FROM db_jobs As jobs_(?:[a-z0-9]+) INNER JOIN db_users As u ON u\.id = jobs_(?:[a-z0-9]+)\.id$/');           
    });

    it(": Alias 'Join' avec un nom de table long", function() {
        $this->builder->db()->setPrefix('db_');

        $builder = $this->builder->from('jobs')->join('users as u', ['users.id' => 'jobs.id']);
        expect($builder->sql())->toMatch('/^SELECT \* FROM db_jobs As jobs_(?:[a-z0-9]+) INNER JOIN db_users As u ON u\.id = jobs_(?:[a-z0-9]+)\.id$/');           
    });
    
    it(": Alias simple 'Like' avec le préfixe DB", function() {
        $this->builder->db()->setPrefix('db_');

        $builder = $this->builder->from('jobs j')->like('j.name', 'veloper');
        expect($builder->sql())->toBe('SELECT * FROM db_jobs As j WHERE j.name LIKE \'%veloper%\'');           
    });
});
