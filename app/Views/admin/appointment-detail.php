<?php

declare(strict_types=1);

/** @var array{id: int, name: string, email: string} $admin */
/** @var array<string, mixed> $appointment */

$price = number_format(((int) $appointment['price_cents']) / 100, 2);
?>
<section class="section-shell admin-dashboard-shell">
    <div class="container">
        <div class="admin-dashboard-header">
            <div>
                <p class="eyebrow">Appointment detail</p>
                <h1><?= htmlspecialchars((string) $appointment['service_name'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="lede">
                    Reference <code><?= htmlspecialchars((string) $appointment['reference'], ENT_QUOTES, 'UTF-8') ?></code>
                </p>
            </div>
            <div class="detail-actions">
                <a class="button button-secondary" href="/admin">Back to appointments</a>
                <form method="post" action="/admin/logout">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button class="button button-secondary" type="submit">Sign out</button>
                </form>
            </div>
        </div>

        <div class="detail-grid">
            <article class="detail-card">
                <p class="eyebrow">Schedule</p>
                <dl>
                    <dt>Starts</dt>
                    <dd><?= htmlspecialchars((string) $appointment['starts_at'], ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt>Ends</dt>
                    <dd><?= htmlspecialchars((string) $appointment['ends_at'], ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt>Duration</dt>
                    <dd><?= (int) $appointment['duration_minutes'] ?> minutes</dd>
                    <dt>Therapist</dt>
                    <dd><?= htmlspecialchars((string) $appointment['therapist_name'], ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt>Status</dt>
                    <dd><?= htmlspecialchars(ucfirst((string) $appointment['status']), ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt>Price</dt>
                    <dd>$<?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8') ?></dd>
                </dl>
            </article>

            <article class="detail-card">
                <p class="eyebrow">Guest</p>
                <dl>
                    <dt>Name</dt>
                    <dd><?= htmlspecialchars((string) $appointment['customer_name'], ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt>Email</dt>
                    <dd><?= htmlspecialchars((string) $appointment['customer_email'], ENT_QUOTES, 'UTF-8') ?></dd>
                    <?php if (!empty($appointment['customer_phone'])): ?>
                        <dt>Phone</dt>
                        <dd><?= htmlspecialchars((string) $appointment['customer_phone'], ENT_QUOTES, 'UTF-8') ?></dd>
                    <?php endif; ?>
                    <?php if (!empty($appointment['customer_note'])): ?>
                        <dt>Note</dt>
                        <dd><?= nl2br(htmlspecialchars((string) $appointment['customer_note'], ENT_QUOTES, 'UTF-8')) ?></dd>
                    <?php endif; ?>
                </dl>
            </article>
        </div>

        <p class="admin-results-summary">
            Read-only view. Appointment status changes are not implemented yet.
        </p>
    </div>
</section>
