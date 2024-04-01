<?= $this->extend('layout') ?>

<?= $this->section('content') ?>

	<?= $this->includeFirst(['parser2', 'simple']) ?>
	<?= $this->includeFirst(['parser1', 'simple']) ?>

<?= $this->endSection() ?>
