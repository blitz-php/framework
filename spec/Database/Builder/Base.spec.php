<?php

use BlitzPHP\Database\BaseBuilder;
use BlitzPHP\Spec\Mock\MockConnection;

describe("Database / Query Builder", function() {

    beforeEach(function() {
        $this->builder = new BaseBuilder(new MockConnection([]));
    });

    it(": La méthode 'db' renvoie la Connection", function() {
        $builder = $this->builder->from('jobs j');
        
        expect($builder->db())->toBeAnInstanceOf(MockConnection::class);
    });
    
    it(": Séléction distincte", function() {
        $builder = $this->builder->select('country')->distinct()->from('users u');
        
        expect($builder->sql())->toBe('SELECT DISTINCT country FROM users As u');
    });
});
