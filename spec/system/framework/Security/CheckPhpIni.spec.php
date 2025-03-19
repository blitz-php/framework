<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Security\CheckPhpIni;

use function Kahlan\expect;

describe('Security / CheckPhpIni', function (): void {
    beforeAll(function(): void {
        $ini = ini_get_all();
        
        $this->display_errors = $ini['display_errors'] ?? ['global_value' => 'disabled', 'local_value' => 'disabled'];
        $this->opcache        = $ini['opcache'] ?? ['global_value' => 'disabled', 'local_value' => 'disabled'];
    });

    it('Check ini', function (): void {
        $output = CheckPhpIni::checkIni();

        expect($output['display_errors'])->toBe([
            'global'      => $this->display_errors['global_value'],
            'current'     => $this->display_errors['local_value'],
            'recommended' => '0',
            'remark'      => '',
        ]);
    }); 
    
    it('Check opcache', function (): void {
        $output = CheckPhpIni::checkIni('opcache');

        expect($output['opcache.save_comments'])->toBe([
            'global'      => $this->opcache['global_value'],
            'current'     => $this->opcache['local_value'],
            'recommended' => '0',
            'remark'      => 'Activé lorsque vous utilisez l\'annotation docblock `package require`',
        ]);
    });

    it('Run web', function (): void {
        $output = CheckPhpIni::run(false);

        $expected = [
            'global'      => '1',
            'current'     => '1',
            'recommended' => '0',
            'remark'      => 'Enable when you using package require docblock annotation',
        ];

        expect($output)->toBeA('string');
        // expect(str_contains($output, 'display_errors'))->toBeTruthy();
    });
});
