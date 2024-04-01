<?= $this->extend('layout') ?>

<?= $this->section('content') ?>

	<?= $this->includeFirst(['parser2', 'simple2']) ?>

<?= $this->endSection() ?>
