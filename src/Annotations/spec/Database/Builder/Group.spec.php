<?php

use BlitzPHP\Database\BaseBuilder;
use BlitzPHP\Spec\Mock\MockConnection;

describe("Database / Query Builder : Regroupement", function() {

    beforeEach(function() {
        $this->builder = new BaseBuilder(new MockConnection([]));
    });

    it(": Groupement simple", function() {
        $builder = $this->builder->from('users u')->select('name')->groupBy('name');

        expect($builder->sql())->toBe('SELECT name FROM users As u GROUP BY name'); 
    });
    
    it(": Groupement avec HAVING", function() {
        $builder = $this->builder->from('users u')->select('name')->groupBy('name')->having('SUM(id) > 2');

        expect($builder->sql())->toBe('SELECT name FROM users As u GROUP BY name HAVING SUM(id) > 2'); 
    });
        
    it(": Groupement avec HAVING (orHaving)", function() {
        $builder = $this->builder->from('users u')
            ->select('name')
            ->groupBy('name')
            ->having('id >', 3)
            ->orHaving('SUM(id) > 2');

        expect($builder->sql())->toBe('SELECT name FROM users As u GROUP BY name HAVING id > 3 OR SUM(id) > 2'); 
    });
    
    it(": Groupement avec HAVING (havingIn)", function() {
        $builder = $this->builder->from('users u')
            ->select('name')
            ->groupBy('name')
            ->havingIn('id', [1, 2]);

        expect($builder->sql())->toBe('SELECT name FROM users As u GROUP BY name HAVING id IN (1,2)'); 
    });

    it(": Groupement avec HAVING (havingIn avec callback)", function() {
        $builder = $this->builder->from('users u')
            ->select('name')
            ->groupBy('name')
            ->havingIn('id', static fn (BaseBuilder $builder) => $builder->select('user_id')->from('users_jobs uj')->where('group_id', 3));

        expect($builder->sql())->toBe('SELECT name FROM users As u GROUP BY name HAVING id IN (SELECT user_id FROM users_jobs As uj WHERE group_id = 3)'); 
    });
    
    it(": Groupement avec HAVING (orHavingIn)", function() {
        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingIn('id', [1, 2])
            ->orHavingIn('group_id', [5, 6]);
            
        expect($builder->sql())->toBe('SELECT name FROM users As u GROUP BY name HAVING id IN (1,2) OR group_id IN (5,6)'); 
    });
    
    it(": Groupement avec HAVING (orHavingIn avec callback)", function() {
        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingIn('id', static fn (BaseBuilder $builder) => $builder->select('user_id')->from('users_jobs uj')->where('group_id', 3))
            ->orHavingIn('group_id', static fn (BaseBuilder $builder) => $builder->select('group_id')->from('groups g')->where('group_id', 6));

        expect($builder->sql())->toBe('SELECT name FROM users As u GROUP BY name HAVING id IN (SELECT user_id FROM users_jobs As uj WHERE group_id = 3) OR group_id IN (SELECT group_id FROM groups As g WHERE group_id = 6)'); 
    });
        
    it(": Groupement avec HAVING (havingNotIn)", function() {
        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingNotIn('id', [1, 2]);

        expect($builder->sql())->toBe('SELECT name FROM users As u GROUP BY name HAVING id NOT IN (1,2)'); 
    });

    it(": Groupement avec HAVING (havingNotIn avec callback)", function() {
        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingNotIn('id', static fn (BaseBuilder $builder) => $builder->select('user_id')->from('users_jobs uj')->where('group_id', 3));

        expect($builder->sql())->toBe('SELECT name FROM users As u GROUP BY name HAVING id NOT IN (SELECT user_id FROM users_jobs As uj WHERE group_id = 3)'); 
    });
    
    it(": Groupement avec HAVING (orHavingNotIn)", function() {
        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingNotIn('id', [1, 2])
            ->orHavingNotIn('group_id', [5, 6]);
            
        expect($builder->sql())->toBe('SELECT name FROM users As u GROUP BY name HAVING id NOT IN (1,2) OR group_id NOT IN (5,6)');         
    });
    
    it(": Groupement avec HAVING (orHavingNotIn avec callback)", function() {
        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingNotIn('id', static fn (BaseBuilder $builder) => $builder->select('user_id')->from('users_jobs uj')->where('group_id', 3))
            ->orHavingNotIn('group_id', static fn (BaseBuilder $builder) => $builder->select('group_id')->from('groups g')->where('group_id', 6));

        expect($builder->sql())->toBe('SELECT name FROM users As u GROUP BY name HAVING id NOT IN (SELECT user_id FROM users_jobs As uj WHERE group_id = 3) OR group_id NOT IN (SELECT group_id FROM groups As g WHERE group_id = 6)'); 
    });
    
    it(": Groupement avec HAVING (havingLike)", function() {
        $expected = 'SELECT name FROM users As u GROUP BY name HAVING pet_name LIKE \'%a%\'';

        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingLike('pet_name', 'a');

        expect($builder->sql())->toBe($expected); 
        
        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingLike('pet_name', '%a%');

        expect($builder->sql())->toBe($expected); 
    });

    it(": Groupement avec HAVING (havingLike before)", function() {
        $expected = 'SELECT name FROM users As u GROUP BY name HAVING pet_name LIKE \'%a\'';

        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingLike('pet_name', 'a', 'before');

        expect($builder->sql())->toBe($expected); 
        
        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingLike('pet_name', '%a');

        expect($builder->sql())->toBe($expected); 
    });
    
    it(": Groupement avec HAVING (havingLike after)", function() {
        $expected = 'SELECT name FROM users As u GROUP BY name HAVING pet_name LIKE \'a%\'';

        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingLike('pet_name', 'a', 'after');

        expect($builder->sql())->toBe($expected); 
        
        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingLike('pet_name', 'a%');

        expect($builder->sql())->toBe($expected); 
    });

    it(": Groupement avec HAVING (havingNotLike)", function() {
        $expected = 'SELECT name FROM users As u GROUP BY name HAVING pet_name NOT LIKE \'%a%\'';

        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingNotLike('pet_name', 'a');

        expect($builder->sql())->toBe($expected); 
        
        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingNotLike('pet_name', '%a%');

        expect($builder->sql())->toBe($expected); 
    });

    it(": Groupement avec HAVING (havingNotLike before)", function() {
        $expected = 'SELECT name FROM users As u GROUP BY name HAVING pet_name NOT LIKE \'%a\'';

        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingNotLike('pet_name', 'a', 'before');

        expect($builder->sql())->toBe($expected); 
        
        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingNotLike('pet_name', '%a');

        expect($builder->sql())->toBe($expected); 
    });
    
    it(": Groupement avec HAVING (havingNotLike after)", function() {
        $expected = 'SELECT name FROM users As u GROUP BY name HAVING pet_name NOT LIKE \'a%\'';

        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingNotLike('pet_name', 'a', 'after');

        expect($builder->sql())->toBe($expected); 
        
        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingNotLike('pet_name', 'a%');

        expect($builder->sql())->toBe($expected); 
    });

    it(": Groupement avec HAVING (orHavingLike)", function() {
        $expected = 'SELECT name FROM users As u GROUP BY name HAVING pet_name LIKE \'%a%\' OR pet_color LIKE \'%b%\'';

        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingLike('pet_name', 'a')
            ->orHavingLike('pet_color', 'b');

        expect($builder->sql())->toBe($expected); 
        
        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingLike('pet_name', '%a%')
            ->orHavingLike('pet_color', '%b%');

        expect($builder->sql())->toBe($expected);
    });

    it(": Groupement avec HAVING (orHavingLike before)", function() {
        $expected = 'SELECT name FROM users As u GROUP BY name HAVING pet_name LIKE \'%a\' OR pet_color LIKE \'%b\'';

        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingLike('pet_name', 'a', 'before')
            ->orHavingLike('pet_color', 'b', 'before');

        expect($builder->sql())->toBe($expected); 
        
        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingLike('pet_name', '%a')
            ->orHavingLike('pet_color', '%b');

        expect($builder->sql())->toBe($expected);
    });
    
    it(": Groupement avec HAVING (orHavingLike after)", function() {
        $expected = 'SELECT name FROM users As u GROUP BY name HAVING pet_name LIKE \'a%\' OR pet_color LIKE \'b%\'';

        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingLike('pet_name', 'a', 'after')
            ->orHavingLike('pet_color', 'b', 'after');

        expect($builder->sql())->toBe($expected); 
        
        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingLike('pet_name', 'a%')
            ->orHavingLike('pet_color', 'b%');

        expect($builder->sql())->toBe($expected);
    });
    
    it(": Groupement avec HAVING (orHavingNotLike)", function() {
        $expected = 'SELECT name FROM users As u GROUP BY name HAVING pet_name LIKE \'%a%\' OR pet_color NOT LIKE \'%b%\'';

        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingLike('pet_name', 'a')
            ->orHavingNotLike('pet_color', 'b');

        expect($builder->sql())->toBe($expected); 
        
        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingLike('pet_name', '%a%')
            ->orHavingNotLike('pet_color', '%b%');

        expect($builder->sql())->toBe($expected);
    });
    
    it(": Groupement avec HAVING (orHavingNotLike before)", function() {
        $expected = 'SELECT name FROM users As u GROUP BY name HAVING pet_name LIKE \'%a\' OR pet_color NOT LIKE \'%b\'';

        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingLike('pet_name', 'a', 'before')
            ->orHavingNotLike('pet_color', 'b', 'before');

        expect($builder->sql())->toBe($expected); 
        
        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingLike('pet_name', '%a')
            ->orHavingNotLike('pet_color', '%b');

        expect($builder->sql())->toBe($expected);
    });

    it(": Groupement avec HAVING (orHavingNotLike after)", function() {
        $expected = 'SELECT name FROM users As u GROUP BY name HAVING pet_name LIKE \'a%\' OR pet_color NOT LIKE \'b%\'';

        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingLike('pet_name', 'a', 'after')
            ->orHavingNotLike('pet_color', 'b', 'after');

        expect($builder->sql())->toBe($expected); 
        
        $builder = $this->builder->from('users u')
            ->select('name')->groupBy('name')
            ->havingLike('pet_name', 'a%')
            ->orHavingNotLike('pet_color', 'b%');

        expect($builder->sql())->toBe($expected);
    });
});
