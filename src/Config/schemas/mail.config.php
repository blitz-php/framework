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
        'address' => Expect::string(env('mail.from.address', 'hello@example.com')),
        'name'    => Expect::string(env('mail.from.name', 'Example')),
    ]),
    'handler'    => Expect::anyOf('phpmailer', 'symfony')->default('phpmailer'),
    'view_dir'   => Expect::string('emails'),
    'template'   => Expect::string(''),
    'protocol'   => Expect::string(env('mail.protocol', Mail::PROTOCOL_SENDMAIL)),
    'dsn'        => Expect::string(env('mail.dsn', '')),
    'host'       => Expect::string(env('mail.host', 'localhost')),
    'username'   => Expect::string(env('mail.username', '')),
    'password'   => Expect::string(env('mail.password', '')),
    'port'       => Expect::int((int) env('mail.port', 25)),
    'timeout'    => Expect::int(5),
    'encryption' => Expect::string(env('mail.encryption', Mail::ENCRYPTION_NONE)),
    'mailType'   => Expect::string('html'),
    'charset'    => Expect::string(env('mail.charset', Mail::CHARSET_UTF8)),
    'priority'   => Expect::int(Mail::PRIORITY_NORMAL),
])->otherItems();
