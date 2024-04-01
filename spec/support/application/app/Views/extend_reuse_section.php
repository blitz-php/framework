<?= $this->extend('layout_welcome') ?>

<?= $this->section('page_title', $pageTitle) ?>

<?= $this->begin('content') ?>
<?= $testString ?>
<?= $this->end() ?>
