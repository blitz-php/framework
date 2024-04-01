<?= $this->extend('layout') ?>

<?= $this->section('content') ?>

	<?= $this->includeUnless(2 == 1, 'simple') ?>

<?= $this->endSection() ?>
