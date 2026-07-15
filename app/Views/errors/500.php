<?php

declare(strict_types=1);

?>
<section class="page-header">
    <div class="container narrow">
        <p class="eyebrow">Unexpected error</p>
        <h1>Something went wrong</h1>
        <p class="lede">We could not complete that request. Please try again.</p>
        <?php if (isset($detail) && is_string($detail)) : ?>
            <p class="debug-detail"><?= htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <a class="button" href="/">Return home</a>
    </div>
</section>

