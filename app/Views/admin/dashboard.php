<?php

declare(strict_types=1);

/** @var array{id: int, name: string, email: string} $admin */
/** @var list<array<string, mixed>> $appointments */
?>
<section class="section-shell admin-dashboard-shell">
    <div class="container">
        <div class="admin-dashboard-header">
            <div>
                <p class="eyebrow">Operations</p>
                <h1>Upcoming appointments</h1>
                <p class="lede">Signed in as <?= htmlspecialchars($admin['name'], ENT_QUOTES, 'UTF-8') ?>.</p>
            </div>
            <form method="post" action="/admin/logout">
                <input type="hidden" name="_token" value="<?= htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <button class="button button-secondary" type="submit">Sign out</button>
            </form>
        </div>

        <?php if ($appointments === []): ?>
            <div class="admin-empty-state">
                <h2>No upcoming appointments</h2>
                <p>The booking queue is clear for now.</p>
            </div>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th scope="col">When</th>
                            <th scope="col">Service</th>
                            <th scope="col">Guest</th>
                            <th scope="col">Therapist</th>
                            <th scope="col">Status</th>
                            <th scope="col">Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $appointment['starts_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $appointment['service_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $appointment['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $appointment['therapist_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="status-pill status-<?= htmlspecialchars((string) $appointment['status'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars(ucfirst((string) $appointment['status']), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td><code><?= htmlspecialchars((string) $appointment['reference'], ENT_QUOTES, 'UTF-8') ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
