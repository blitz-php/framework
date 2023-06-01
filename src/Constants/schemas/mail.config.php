<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Mail\Mail;
use Nette\Schema\Expect;

return Expect::structure([
    'from' => Expect::structure([
        'address' => Expect::string()->default('hello@example.com'),
        'name'    => Expect::string()->default('Example'),
    ]),
    'handler'    => Expect::string()->default('phpmailer'),
    'view_base'  => Expect::string()->default('emails'),
    'protocol'   => Expect::string()->default('mail'),
    'host'       => Expect::string()->default('localhost'),
    'username'   => Expect::string()->default(''),
    'password'   => Expect::string()->default(''),
    'port'       => Expect::int()->default(25),
    'timeout'    => Expect::int()->default(5),
    'encryption' => Expect::string()->default(Mail::ENCRYPTION_TLS),
    'mailType'   => Expect::string()->default('html'),
    'charset'    => Expect::string()->default('UTF-8'),
    'priority'   => Expect::int()->default(Mail::PRIORITY_NORMAL),
]);
