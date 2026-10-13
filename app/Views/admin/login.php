<?php

declare(strict_types=1);

$emailValue = isset($email) ? (string) $email : '';
$errorMessage = isset($error) ? (string) $error : '';
?>
<section class="section-shell admin-auth-shell">
    <div class="container admin-auth-card">
        <p class="eyebrow">Staff access</p>
        <h1>Admin sign in</h1>
        <p class="lede">Review upcoming appointments and manage the booking queue.</p>

        <?php if ($errorMessage !== ''): ?>
            <div class="form-alert" role="alert">
                <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/admin/login" class="admin-login-form">
            <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <label>
                <span>Email</span>
                <input
                    type="email"
                    name="email"
                    value="<?= htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="username"
                    required
                >
            </label>

            <label>
                <span>Password</span>
                <input type="password" name="password" autocomplete="current-password" required>
            </label>

            <button class="button button-primary" type="submit">Sign in</button>
        </form>
    </div>
</section>
