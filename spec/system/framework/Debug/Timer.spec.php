<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Debug\Timer;

use function Kahlan\expect;

describe('Debug / Timer', function (): void {
	it('stockes les timers', function (): void {
		$timer = new Timer();

        $timer->start('test1');
        sleep(1);
        $timer->stop('test1');

        $timers = $timer->getTimers();

		expect($timers)->toHaveLength(1);
        expect($timers)->toContainKey('test1');
		expect($timers['test1'])->toContainKey('start');
		expect($timers['test1'])->toContainKey('end');

        // Comme le minuteur a été arrêté, il aura une valeur.
		// Dans ce cas, celle-ci devrait être supérieure à 1 seconde.
		expect($timers['test1'])->toContainKey('duration');
		expect($timers['test1']['duration'] >= 1.0)->toBeTruthy();
	});

	it('appelle automatiquement stop', function (): void {
		$timer = new Timer();

        $timer->start('test1');
        sleep(1);

        $timers = $timer->getTimers();

		expect($timers['test1'])->toContainKey('duration');
		expect($timers['test1']['duration'] >= 1.0)->toBeTruthy();
	});

	it('elapsedTime donne le meme resultat que lorsqu\'on recupere timer en tant que tableau', function (): void {
		$timer = new Timer();

        $timer->start('test1');
        sleep(1);
        $timer->stop('test1');

        $timers = $timer->getTimers();

        $expected = $timers['test1']['duration'];

        expect($timer->getElapsedTime('test1'))->toBe($expected);
	});

	it('Leve une execption si on essaie de stopper un timer non demarrer', function (): void {
		$timer = new Timer();

		expect(fn() => $timer->stop('test1'))->toThrow(new RuntimeException());
	});

	it('Utilisation du helper', function (): void {
 		timer('test1');
        sleep(1);
        timer('test1');

		expect(1.0 >= timer()->getElapsedTime('test1'));
	});

	it('Renvoie null quand on veut recuperer le elapsedTime d\'un timer qui n\'existe pas', function (): void {
		$timer = new Timer();

		expect($timer->getElapsedTime('test1'))->toBeNull();
	});

	it('"Record" avec une fonction qui ne renvoie rien', function () {
		$timer       = new Timer();
        $returnValue = $timer->record('longjohn', static function (): void { usleep(100000); });

	   expect(1.0 >= $timer->getElapsedTime('longjohn'))->toBeTruthy();
	   expect($returnValue)->toBeNull();
	});

	it('"Record" avec une fonction qui renvoie une valeur', function () {
		$timer       = new Timer();
        $returnValue = $timer->record('longjohn', static function (): string {
            usleep(100000);

            return 'test';
        });

        expect(1.0 >= $timer->getElapsedTime('longjohn'))->toBeTruthy();
		expect($returnValue)->toBe('test');
	});

	it('"Record" avec une fonction flechee', function () {
		$timer       = new Timer();
         $returnValue = $timer->record('longjohn', static fn (): int => strlen('blitz-php'));

        expect(1.0 >= $timer->getElapsedTime('longjohn'))->toBeTruthy();
		expect($returnValue)->toBe(9);
	});

	it('"Record" avec une fonction qui leve une exception', function () {
		$timer       = new Timer();

		expect(fn() => $timer->record('ex', static function (): never {
			throw new RuntimeException();
		}))->toThrow(new RuntimeException());
	});

	it('"Record" leve une exception lorsqu\'on l\'appelle sans les parametres adequats', function () {
		$timer       = new Timer();

		expect(fn() => $timer->record('error', 'strlen'))
			->toThrow(new ArgumentCountError());
	});

	it('L\'appel de la fonction "timer" sans arguments renvoie une instance de Timer', function () {
		expect(timer())->toBeAnInstanceOf(Timer::class);
	});

	it('L\'appel de la fonction "timer" avec un argument renvoie une instance de Timer initialisée avec ce nom', function () {
		$returnValue = timer('test');

        expect($returnValue)->toBeAnInstanceOf(Timer::class);
		expect($returnValue->has('test'))->toBeTruthy();
	});

	it('L\'appel de la fonction "timer" sans le nom mais avec le calback renvoie une instance de Timer', function () {
		$returnValue = timer(null, static fn (): int => strlen('blitz-php'));

		expect($returnValue)->toBeAnInstanceOf(Timer::class);
	});

	it('Appel de la fonction "timer" avec un calback qui ne retourne rien', function () {
		$returnValue = timer('common', static function (): void { usleep(100000); });

		expect($returnValue)->not->toBeAnInstanceOf(Timer::class);
		expect($returnValue)->toBeNull();
		expect(timer()->getElapsedTime('common') >= 0.1)->toBeTruthy();
	});

	it('Appel de la fonction "timer" avec un calback qui retourne une valeur', function () {
		$returnValue = timer('common', static fn (): int => strlen('blitz-php'));

		expect($returnValue)->not->toBeAnInstanceOf(Timer::class);
		expect($returnValue)->toBe(9);
		expect(timer()->getElapsedTime('common') <= 0.1)->toBeTruthy();
	});

	xit('teste un temps d\'exécution long', function() {
		$timer = new Timer();
		$timer->start('longjohn', strtotime('-110 minutes'));

		expect($timer->getElapsedTime('longjohn'))->toBeCloseTo(110 * 60, 1);
	});

	xit('teste un temps d\'exécution long via fonction commune', function() {
		$timer = new Timer();
		$timer->start('longjohn', strtotime('-11 minutes'));

		expect($timer->getElapsedTime('longjohn'))->toBeCloseTo(11 * 60, 1);
	});
});
