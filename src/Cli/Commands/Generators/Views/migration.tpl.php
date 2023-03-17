<@php

namespace {namespace};

use BlitzPHP\Database\Migration\Migration;
<?php if (! empty($table) && ! empty($action)): ?>
use BlitzPHP\Database\Migration\Structure;
<?php endif; ?>

class {class} extends Migration
{
<?php if ($group): ?>
    protected ?string $group = '<?= $group ?>';
<?php endif; ?>

    public function up()
    {
<?php if (empty($table) || empty($action)): ?>
        //
<?php else: ?>
        $this-><?= $action ?>('<?= $table ?>', function(Structure $structure) {
<?php if ($session): ?>
            $structure->string('id', 128);
            $structure->ipAddress('ip_address');
            $structure->timestamp('timestamp');
            $structure->binary('data');
<?php if ($matchIP): ?>
            $structure->primary(['id', 'ip_address']);
<?php else: ?>
            $structure->primary('id');
<?php endif; ?>
            $structure->index('timestamp');
<?php else: ?>
            $structure->increments('id');
            $structure->timestamps();
<?php endif; ?>

            return $structure;
        });
<?php endif; ?>
    }

    public function down()
    {
<?php if (empty($table) || empty($action)): ?>
        //
<?php elseif ($action === 'create') : ?>
        $this->dropIfExists('<?= $table ?>');
<?php else: ?>
        $this->modify('<?= $table ?>', function(Structure $structure) {
            //
        });
<?php endif; ?>
    }
}
