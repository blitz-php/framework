<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
use BlitzPHP\Controllers\ApplicationController;
use BlitzPHP\Controllers\ResourcePresenter;

use function Kahlan\expect;

describe('Controllers / ResourcePresenter', function (): void {
    beforeEach(function (): void {
       $this->controller = new class extends ResourcePresenter {
            public function testIndex() {
                return $this->index();
            }

            public function testShow($id = null) {
                return $this->show($id);
            }

            public function testNew() {
                return $this->new();
            }

            public function testCreate() {
                return $this->create();
            }

            public function testEdit($id = null) {
                return $this->edit($id);
            }

            public function testUpdate($id = null) {
                return $this->update($id);
            }

            public function testRemove($id = null) {
                return $this->remove($id);
            }

            public function testDelete($id = null) {
                return $this->delete($id);
            }
        };
    });

    describe('Méthodes de présentation', function (): void {
        it('Doit retourner un message pour index()', function (): void {
            $result = $this->controller->testIndex();

            expect($result)->toBeA('string');
            expect($result)->toMatch('/not implemented/i');
        });

        it('Doit retourner un message pour show() avec ID', function (): void {
            $result = $this->controller->testShow(123);

            expect($result)->toBeA('string');
            expect($result)->toMatch('/not implemented/i');
        });

        it('Doit retourner un message pour show() sans ID', function (): void {
            $result = $this->controller->testShow();

            expect($result)->toBeA('string');
            expect($result)->toMatch('/not implemented/i');
        });

        it('Doit retourner un message pour new()', function (): void {
            $result = $this->controller->testNew();

            expect($result)->toBeA('string');
        });

        it('Doit retourner un message pour create()', function (): void {
            $result = $this->controller->testCreate();

            expect($result)->toBeA('string');
        });

        it('Doit retourner un message pour edit()', function (): void {
            $result = $this->controller->testEdit(456);

            expect($result)->toBeA('string');
        });

        it('Doit retourner un message pour update()', function (): void {
            $result = $this->controller->testUpdate(789);

            expect($result)->toBeA('string');
        });

        it('Doit retourner un message pour remove()', function (): void {
            $result = $this->controller->testRemove(999);

            expect($result)->toBeA('string');
        });

        it('Doit retourner un message pour delete()', function (): void {
            $result = $this->controller->testDelete(111);

            expect($result)->toBeA('string');
        });
    });

    describe('Héritage', function (): void {
        it('Doit étendre ApplicationController', function (): void {
            expect($this->controller)->toBeAnInstanceOf(ResourcePresenter::class);
            expect($this->controller)->toBeAnInstanceOf(ApplicationController::class);
        });

        it('Doit avoir les méthodes de ApplicationController', function (): void {
            expect(method_exists($this->controller, 'view'))->toBeTruthy();
            expect(method_exists($this->controller, 'render'))->toBeTruthy();
            expect(method_exists($this->controller, 'addData'))->toBeTruthy();
        });
    });
});
