<?php 

return [
    'active_adapter'  => 'native' ,
    'compress_output' => 'auto' ,
    'view_base'       => VIEW_PATH ,
    'debug'           => 'auto' ,
    'shared'          => fn(): array => [],
    'decorators'      => [] ,
    'adapters'        => ['native' => ['extension' => 'php', 'save_data' => true]]
];
