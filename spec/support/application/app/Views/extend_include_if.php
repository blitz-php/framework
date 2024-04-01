<?= $this->extend('layout') ?>

<?= $this->section('content') ?>

	<?= $this->includeIf('simple') ?>
	<?= $this->includeIf('parser2') ?>

<?= $this->endSection() ?>
