<?php if ($message = flash('success')): ?>
    <div class="flash success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($message = flash('error')): ?>
    <div class="flash error"><?= e($message) ?></div>
<?php endif; ?>
