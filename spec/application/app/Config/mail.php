<?php 

return [
   'from'       => ['address' => 'hello@example.com', 'name' => 'Example'],
   'handler'    => 'phpmailer',
   'view_dir'   => 'emails',
   'template'   => '',
   'dsn'        => '',
   'protocol'   => \BlitzPHP\Mail\Mail::PROTOCOL_SENDMAIL,
   'host'       => 'localhost',
   'username'   => '',
   'password'   => '',
   'port'       => 25,
   'timeout'    => 5,
   'encryption' => \BlitzPHP\Mail\Mail::ENCRYPTION_NONE,
   'mailType'   => 'html',
   'charset'    => \BlitzPHP\Mail\Mail::CHARSET_UTF8,
   'priority'   => \BlitzPHP\Mail\Mail::PRIORITY_NORMAL,
];
