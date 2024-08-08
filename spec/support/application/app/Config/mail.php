<?php

use BlitzPHP\Mail\Mail;
/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
return [
    'from'       => ['address' => 'hello@example.com', 'name' => 'Example'],
    'handler'    => 'phpmailer',
    'view_dir'   => 'emails',
    'template'   => '',
    'dsn'        => '',
    'protocol'   => Mail::PROTOCOL_SENDMAIL,
    'host'       => 'localhost',
    'username'   => '',
    'password'   => '',
    'port'       => 25,
    'timeout'    => 5,
    'encryption' => Mail::ENCRYPTION_NONE,
    'mailType'   => 'html',
    'charset'    => Mail::CHARSET_UTF8,
    'priority'   => Mail::PRIORITY_NORMAL,
];
