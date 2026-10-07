<?php

declare(strict_types=1);

$name = is_string($adminName ?? null) ? $adminName : 'Administrator';
?>
<section class="page-header">
    <div class="container">
        <p class="eyebrow">Spa administration</p>
        <h1>Welcome, <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="lede">The authenticated admin area is ready. Appointment management is the next Phase 4 slice.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="admin-panel">
            <h2>Appointment dashboard</h2>
            <p>Upcoming appointments, filters, appointment details, and controlled status transitions will live here.</p>
        </div>

        <form method="post" action="/admin/logout">
            <input type="hidden" name="csrf_token"
                value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <button class="button button-secondary" type="submit">Sign out</button>
        </form>
    </div>
</section>
