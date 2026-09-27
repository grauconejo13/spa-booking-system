<?php

declare(strict_types=1);

/** @var array{id: int, name: string, email: string} $admin */
/** @var list<array<string, mixed>> $appointments */
/** @var list<string> $statusOptions */
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

        <form method="get" action="/admin" class="admin-filter-form" aria-label="Filter appointments">
            <div class="form-field">
                <label for="status-filter">Status</label>
                <select id="status-filter" name="status">
                    <option value="">All upcoming appointments</option>
                    <?php foreach ($statusOptions as $status): ?>
                        <option
                            value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
                            <?= $selectedStatus === $status ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="detail-actions">
                <button class="button" type="submit">Apply filter</button>
                <?php if ($selectedStatus !== ''): ?>
                    <a class="button button-secondary" href="/admin">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($appointments === []): ?>
            <div class="admin-empty-state">
                <h2>No matching appointments</h2>
                <p>
                    <?= $selectedStatus === ''
                        ? 'The booking queue is clear for now.'
                        : 'There are no upcoming appointments with this status.' ?>
                </p>
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
