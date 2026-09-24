<?php

declare(strict_types=1);

$emailValue = htmlspecialchars((string) ($email ?? ''), ENT_QUOTES, 'UTF-8');
?>
<section class="page-header">
    <div class="container narrow">
        <p class="eyebrow">Spa administration</p>
        <h1>Sign in</h1>
        <p class="lede">Use the fictional demo administrator account to review the management interface.</p>
    </div>
</section>

<section class="section">
    <div class="container narrow">
        <?php if (is_string($error ?? null) && $error !== ''): ?>
            <div class="error-summary" role="alert">
                <strong><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        <?php endif; ?>

        <form class="admin-login-form" method="post" action="/admin/login">
            <input type="hidden" name="csrf_token"
                value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" autocomplete="username"
                    value="<?= $emailValue ?>" required maxlength="254">
            </div>

            <div class="form-field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password"
                    autocomplete="current-password" required>
            </div>

            <button class="button" type="submit">Sign in</button>
        </form>
    </div>
</section>
