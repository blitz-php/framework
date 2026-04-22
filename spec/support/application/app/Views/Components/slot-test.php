<div class="alert alert-<?= $variant ?>">
    <?php if (isset($slots['header'])): ?>
        <div class="alert-header"><?= $slots['header'] ?></div>
    <?php endif; ?>
    <div class="alert-body"><?= $slot ?></div>
    <?php if (isset($slots['footer'])): ?>
        <div class="alert-footer"><?= $slots['footer'] ?></div>
    <?php endif; ?>
</div>
