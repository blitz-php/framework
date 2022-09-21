<?php

use BlitzPHP\Database\BaseBuilder;
use BlitzPHP\Exceptions\DatabaseException;
use BlitzPHP\Spec\Mock\MockConnection;

describe("Database / Query Builder : Suppression", function() {

    beforeEach(function() {
        $this->builder = new BaseBuilder(new MockConnection([]));
    });
     
    it(": Vérification de la présence d'une table", function() {
        $builder = $this->builder->testMode();
        expect(function() use ($builder) {
            $builder->delete();
        })->toThrow(new DatabaseException("Table is not defined."));
    });
    
    it(": Suppression de base", function() {
        $builder = $this->builder->testMode()->from('jobs');
        expect($builder->delete())->toBe('DELETE FROM jobs'); 
    });
        
    it(": Suppression conditionnée", function() {
        $builder = $this->builder->testMode()->from('jobs');
        expect($builder->delete(['id' => 1]))->toBe('DELETE FROM jobs WHERE id = 1'); 
        
        $builder = $this->builder->testMode()->from('jobs')->where(['id' => 1]);
        expect($builder->delete())->toBe('DELETE FROM jobs WHERE id = 1'); 
    });
    
    it(": Suppression avec alias de table", function() {
        // Retrait des alias (explicite) pour les requetes de suppression
        $builder = $this->builder->testMode()->from('jobs As j');
        expect($builder->delete(['id' => 1]))->toBe('DELETE FROM jobs WHERE id = 1'); 
        
        // Retrait des alias (implicite) pour les requetes de suppression
        $builder = $this->builder->testMode()->from('jobs j');
        expect($builder->delete(['id' => 1]))->toBe('DELETE FROM jobs WHERE id = 1'); 
    });
        
    it(": Suppression avec limite", function() {
        $builder = $this->builder->testMode()->from('jobs')->where('id', 1)->limit(10);
        expect($builder->delete())->toBe('DELETE FROM jobs WHERE id = 1 LIMIT 10'); 
    });
});
