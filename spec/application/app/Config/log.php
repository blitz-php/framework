<?php

return [
    'name'        => 'Application',
    'date_format' => 'Y-m-d H:i:s',
    'processors'  => [],
    'handlers'    => [
        'file' => [
            'level'          => \Psr\Log\LogLevel::DEBUG,
            'extension'      => '',
            'permissions'    => 644,
            'path'           => '',
            'format'         => 'line',
            'dayly_rotation' => true,
            'max_files'      => 0,
        ],
    ]
];