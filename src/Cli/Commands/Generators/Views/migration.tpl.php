<@php

namespace {namespace};

use BlitzPHP\Database\Migration\Migration;
<?php if (! empty($table) && ! empty($action)): ?>
use BlitzPHP\Database\Migration\Structure;
<?php endif; ?>

class {class} extends Migration
{
<?php if ($group): ?>
    protected string $group = '<?= $group ?>';
<?php endif; ?>

    public function up()
    {
<?php if (empty($table) || empty($action)): ?>
        //
<?php else: ?>
        $this-><?= $action ?>('<?= $table ?>', function(Structure $table) {
<?php if ($session): ?>
            $table->string('id', 128);
            $table->ipAddress();
            $table->timestamp('timestamp');
            $table->binary('data');
<?php if ($matchIP): ?>
            $table->primary(['id', 'ip_address']);
<?php else: ?>
            $table->primary('id');
<?php endif; ?>
            $table->index('timestamp');
<?php else: ?>
            $table->id();
            $table->timestamps();
<?php endif; ?>

            return $table;
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
        $this->modify('<?= $table ?>', function(Structure $table) {
            //
        });
<?php endif; ?>
    }
}
