<?= $this->extend('layout') ?>

<?= $this->section('content') ?>

	<?= $this->includeWhen(fn() => 2 == 1, 'simple') ?>
	<?= $this->includeWhen(1 == 1, 'parser1') ?>

<?= $this->endSection() ?>
