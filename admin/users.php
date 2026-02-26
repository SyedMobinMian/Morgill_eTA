<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
requireAdmin();

$db = adminDB();
$canCreate = canCreateRecords();   // master + admin
$canManage = canManageRecords();   // master only (edit/delete)

function redirectUsers(): void {
    redirectTo(baseUrl('users.php'));
}

$editRow = null;
$editId = (int)($_GET['edit'] ?? 0);
if ($canManage && $editId > 0) {
    $stmt = $db->prepare("SELECT
        t.id AS traveller_id,
        t.application_id,
        t.first_name,
        t.last_name,
        t.email,
        t.date_of_birth,
        COALESCE(NULLIF(t.nationality, ''), NULLIF(t.country_of_birth, ''), '') AS country_from,
        a.status AS payment_status,
        a.travel_mode,
        a.total_travellers
    FROM travellers t
    INNER JOIN applications a ON a.id = t.application_id
    WHERE t.id = :id
    LIMIT 1");
    $stmt->execute([':id' => $editId]);
    $editRow = $stmt->fetch() ?: null;
}

$isEdit = $canManage && $editRow !== null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf_token'] ?? '');
    if (!verifyCsrf($csrf)) {
        flash('error', 'Invalid request token.');
        redirectUsers();
    }

    $action = sanitizeText($_POST['action'] ?? '', 20);

    if ($action === 'add' || $action === 'update') {
        if ($action === 'add' && !$canCreate) {
            flash('error', 'Only MasterAdmin/Admin can create users.');
            redirectUsers();
        }
        if ($action === 'update' && !$canManage) {
            flash('error', 'Only MasterAdmin can edit users.');
            redirectUsers();
        }

        $travellerId = (int)($_POST['traveller_id'] ?? 0);
        $firstName = sanitizeText($_POST['first_name'] ?? '', 100);
        $lastName = sanitizeText($_POST['last_name'] ?? '', 100);
        $email = sanitizeEmail($_POST['email'] ?? '');
        $dob = sanitizeText($_POST['date_of_birth'] ?? '', 20);
        $countryFrom = sanitizeText($_POST['country_from'] ?? '', 100);
        $travelMode = sanitizeText($_POST['travel_mode'] ?? 'solo', 10);
        $totalTravellers = (int)($_POST['total_travellers'] ?? 1);
        $paymentStatus = sanitizeText($_POST['payment_status'] ?? 'draft', 20);

        $allowedMode = ['solo', 'group'];
        $allowedStatus = ['draft', 'submitted', 'paid', 'processing', 'approved', 'rejected'];

        if ($firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Valid first name, last name, and email are required.');
            redirectUsers();
        }
        if ($countryFrom === '') {
            flash('error', 'Country from is required.');
            redirectUsers();
        }
        if (!in_array($travelMode, $allowedMode, true)) {
            $travelMode = 'solo';
        }
        if ($totalTravellers < 1) {
            $totalTravellers = 1;
        }
        if (!in_array($paymentStatus, $allowedStatus, true)) {
            $paymentStatus = 'draft';
        }

        if ($dob !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $dob);
            if (!$d || $d->format('Y-m-d') !== $dob) {
                flash('error', 'Date of birth format must be YYYY-MM-DD.');
                redirectUsers();
            }
        } else {
            $dob = null;
        }

        try {
            $db->beginTransaction();

            if ($action === 'add') {
                $reference = generateReference();
                $appStmt = $db->prepare('INSERT INTO applications (reference, travel_mode, total_travellers, status) VALUES (:reference, :travel_mode, :total_travellers, :status)');
                $appStmt->execute([
                    ':reference' => $reference,
                    ':travel_mode' => $travelMode,
                    ':total_travellers' => $totalTravellers,
                    ':status' => $paymentStatus,
                ]);
                $applicationId = (int)$db->lastInsertId();

                $tStmt = $db->prepare('INSERT INTO travellers (application_id, traveller_number, first_name, last_name, email, date_of_birth, country_of_birth, nationality) VALUES (:application_id, 1, :first_name, :last_name, :email, :date_of_birth, :country_of_birth, :nationality)');
                $tStmt->execute([
                    ':application_id' => $applicationId,
                    ':first_name' => $firstName,
                    ':last_name' => $lastName,
                    ':email' => $email,
                    ':date_of_birth' => $dob,
                    ':country_of_birth' => $countryFrom,
                    ':nationality' => $countryFrom,
                ]);

                flash('success', 'User created successfully.');
            } else {
                if ($travellerId <= 0) {
                    throw new RuntimeException('Invalid user id.');
                }

                $getApp = $db->prepare('SELECT application_id FROM travellers WHERE id = :id LIMIT 1');
                $getApp->execute([':id' => $travellerId]);
                $applicationId = (int)$getApp->fetchColumn();
                if ($applicationId <= 0) {
                    throw new RuntimeException('User not found.');
                }

                $uTrav = $db->prepare('UPDATE travellers SET first_name=:first_name, last_name=:last_name, email=:email, date_of_birth=:date_of_birth, country_of_birth=:country_of_birth, nationality=:nationality WHERE id=:id');
                $uTrav->execute([
                    ':first_name' => $firstName,
                    ':last_name' => $lastName,
                    ':email' => $email,
                    ':date_of_birth' => $dob,
                    ':country_of_birth' => $countryFrom,
                    ':nationality' => $countryFrom,
                    ':id' => $travellerId,
                ]);

                $uApp = $db->prepare('UPDATE applications SET travel_mode=:travel_mode, total_travellers=:total_travellers, status=:status WHERE id=:id');
                $uApp->execute([
                    ':travel_mode' => $travelMode,
                    ':total_travellers' => $totalTravellers,
                    ':status' => $paymentStatus,
                    ':id' => $applicationId,
                ]);

                flash('success', 'User updated successfully.');
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            flash('error', 'Save failed: ' . $e->getMessage());
        }

        redirectUsers();
    }

    if ($action === 'delete') {
        if (!$canManage) {
            flash('error', 'Only MasterAdmin can delete users.');
            redirectUsers();
        }

        $travellerId = (int)($_POST['traveller_id'] ?? 0);
        if ($travellerId <= 0) {
            flash('error', 'Invalid user id.');
            redirectUsers();
        }

        try {
            $db->beginTransaction();

            $rowStmt = $db->prepare('SELECT application_id FROM travellers WHERE id = :id LIMIT 1');
            $rowStmt->execute([':id' => $travellerId]);
            $applicationId = (int)$rowStmt->fetchColumn();
            if ($applicationId <= 0) {
                throw new RuntimeException('User not found.');
            }

            $db->prepare('DELETE FROM travellers WHERE id = :id')->execute([':id' => $travellerId]);

            $countStmt = $db->prepare('SELECT COUNT(*) FROM travellers WHERE application_id = :application_id');
            $countStmt->execute([':application_id' => $applicationId]);
            $remaining = (int)$countStmt->fetchColumn();

            if ($remaining <= 0) {
                $db->prepare('DELETE FROM applications WHERE id = :id')->execute([':id' => $applicationId]);
            } else {
                $mode = $remaining > 1 ? 'group' : 'solo';
                $db->prepare('UPDATE applications SET total_travellers = :total, travel_mode = :mode WHERE id = :id')
                    ->execute([':total' => $remaining, ':mode' => $mode, ':id' => $applicationId]);

                $renumber = $db->prepare('SELECT id FROM travellers WHERE application_id = :application_id ORDER BY id');
                $renumber->execute([':application_id' => $applicationId]);
                $ids = $renumber->fetchAll(PDO::FETCH_COLUMN);
                $seq = 1;
                $up = $db->prepare('UPDATE travellers SET traveller_number = :num WHERE id = :id');
                foreach ($ids as $id) {
                    $up->execute([':num' => $seq, ':id' => (int)$id]);
                    $seq++;
                }
            }

            $db->commit();
            flash('success', 'User deleted successfully.');
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            flash('error', 'Delete failed: ' . $e->getMessage());
        }

        redirectUsers();
    }
}

$sql = "SELECT
    t.id AS traveller_id,
    CONCAT(TRIM(t.first_name), ' ', TRIM(t.last_name)) AS traveler_name,
    t.date_of_birth,
    COALESCE(NULLIF(t.nationality, ''), NULLIF(t.country_of_birth, ''), '-') AS country_from,
    a.status AS payment_status,
    CASE
        WHEN t.decl_accurate = 1 AND t.decl_terms = 1 THEN 'Completed'
        WHEN t.step_completed IS NULL OR t.step_completed = '' THEN 'Not Started'
        ELSE CONCAT('In Progress (', t.step_completed, ')')
    END AS form_status,
    a.travel_mode,
    a.total_travellers,
    t.email,
    fat.form_number,
    fat.email_sent_at
FROM travellers t
INNER JOIN applications a ON a.id = t.application_id
LEFT JOIN form_access_tokens fat ON fat.traveller_id = t.id
ORDER BY t.created_at DESC";

$rows = $db->query($sql)->fetchAll();
renderAdminLayoutStart('Users / Reports', 'users');
?>
<div class="user-top-layout">
    <div class="user-form-wrap">
        <?php if ($canCreate): ?>
        <form method="post" class="panel user-form-panel" autocomplete="on">
            <h3><?= $isEdit ? 'Edit User' : 'Create User' ?></h3>
            <div class="user-form-grid">
                <div class="form-field">
                    <label for="user-first-name">First Name</label>
                    <input id="user-first-name" type="text" name="first_name" required maxlength="100" autocomplete="given-name" value="<?= esc($isEdit ? (string)($editRow['first_name'] ?? '') : '') ?>">
                </div>
                <div class="form-field">
                    <label for="user-last-name">Last Name</label>
                    <input id="user-last-name" type="text" name="last_name" required maxlength="100" autocomplete="family-name" value="<?= esc($isEdit ? (string)($editRow['last_name'] ?? '') : '') ?>">
                </div>
                <div class="form-field">
                    <label for="user-email">Email</label>
                    <input id="user-email" type="email" name="email" required maxlength="255" autocomplete="email" value="<?= esc($isEdit ? (string)($editRow['email'] ?? '') : '') ?>">
                </div>
                <div class="form-field">
                    <label for="user-dob">Date of Birth</label>
                    <input id="user-dob" type="date" name="date_of_birth" autocomplete="on" value="<?= esc($isEdit ? (string)($editRow['date_of_birth'] ?? '') : '') ?>">
                </div>
                <div class="form-field">
                    <label for="user-country-from">Country From</label>
                    <input id="user-country-from" type="text" name="country_from" required maxlength="100" autocomplete="country-name" value="<?= esc($isEdit ? (string)($editRow['country_from'] ?? '') : '') ?>">
                </div>
                <div class="form-field">
                    <label for="user-travel-mode">Travel Mode</label>
                    <select id="user-travel-mode" name="travel_mode" required>
                        <option value="solo" <?= (($isEdit ? ($editRow['travel_mode'] ?? 'solo') : 'solo') === 'solo') ? 'selected' : '' ?>>Solo</option>
                        <option value="group" <?= (($isEdit ? ($editRow['travel_mode'] ?? '') : '') === 'group') ? 'selected' : '' ?>>Group</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="user-total-travellers">Total Travellers</label>
                    <input id="user-total-travellers" type="number" min="1" max="10" name="total_travellers" required value="<?= esc((string)($isEdit ? ($editRow['total_travellers'] ?? 1) : 1)) ?>">
                </div>
                <div class="form-field">
                    <label for="user-payment-status">Payment Status</label>
                    <select id="user-payment-status" name="payment_status" required>
                        <?php $statusOptions = ['draft','submitted','paid','processing','approved','rejected']; ?>
                        <?php foreach ($statusOptions as $status): ?>
                            <option value="<?= esc($status) ?>" <?= (($isEdit ? ($editRow['payment_status'] ?? 'draft') : 'draft') === $status) ? 'selected' : '' ?>><?= esc(ucfirst($status)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'add' ?>">
            <input type="hidden" name="traveller_id" value="<?= (int)($isEdit ? ($editRow['traveller_id'] ?? 0) : 0) ?>">
            <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>">

            <div class="user-form-actions">
                <button type="submit"><?= $isEdit ? 'Update User' : 'Create User' ?></button>
                <?php if ($isEdit): ?>
                    <a href="<?= esc(baseUrl('users.php')) ?>" class="btn-link-secondary">Cancel Edit</a>
                <?php endif; ?>
            </div>
        </form>
        <?php else: ?>
        <div class="panel user-form-panel">
            <h3>View-only Access</h3>
            <p style="margin:0;color:var(--muted);">Staff can view records. Create, edit, and delete actions are disabled.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>DOB</th>
            <th>Country From</th>
            <th>Payment Status</th>
            <th>Form Status</th>
            <th>Travel Solo/Group</th>
            <th>Email Status</th>
            <th>Form No.</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= esc($row['traveler_name'] ?: '-') ?></td>
                <td><?= esc($row['date_of_birth'] ?: '-') ?></td>
                <td><?= esc($row['country_from'] ?: '-') ?></td>
                <td><?= esc(ucfirst((string)$row['payment_status'])) ?></td>
                <td><?= esc($row['form_status']) ?></td>
                <td><?= esc(ucfirst((string)$row['travel_mode'])) ?> (<?= (int)$row['total_travellers'] ?>)</td>
                <td><?= $row['email_sent_at'] ? 'Sent' : 'Pending' ?></td>
                <td><?= esc($row['form_number'] ?: '-') ?></td>
                <td>
                    <div class="action-icons">
                        <?php if ($canManage): ?>
                            <a
                                href="<?= esc(baseUrl('users.php?edit=' . (int)$row['traveller_id'])) ?>"
                                class="icon-btn icon-edit"
                                title="Edit user"
                                aria-label="Edit user"
                            >
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.8 9.94l-3.75-3.75L3 17.25zm17.7-10.04a1 1 0 0 0 0-1.41l-2.5-2.5a1 1 0 0 0-1.41 0L14.9 5.2l3.75 3.75 2.05-1.74z"/></svg>
                            </a>
                            <a
                                href="<?= esc(baseUrl('documents.php')) ?>"
                                class="icon-btn icon-pdf"
                                title="View PDFs"
                                aria-label="View PDFs"
                            >
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7l-5-5zm1 7V3.5L18.5 7H15zM8 13h2.2a2 2 0 1 1 0 4H9v2H8v-6zm1 1v2h1.2a1 1 0 1 0 0-2H9zm4-1h2a2 2 0 0 1 0 4h-1v2h-1v-6zm1 1v2h1a1 1 0 1 0 0-2h-1z"/></svg>
                            </a>
                            <form method="post" onsubmit="return confirm('Delete this user?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="traveller_id" value="<?= (int)$row['traveller_id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>">
                                <button
                                    type="submit"
                                    class="icon-btn icon-delete"
                                    title="Delete user"
                                    aria-label="Delete user"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3h6l1 2h4v2H4V5h4l1-2zm1 6h2v9h-2V9zm4 0h2v9h-2V9zM7 9h2v9H7V9z"/></svg>
                                </button>
                            </form>
                        <?php else: ?>
                            <a
                                href="<?= esc(baseUrl('documents.php')) ?>"
                                class="icon-btn icon-pdf"
                                title="View PDFs"
                                aria-label="View PDFs"
                            >
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7l-5-5zm1 7V3.5L18.5 7H15zM8 13h2.2a2 2 0 1 1 0 4H9v2H8v-6zm1 1v2h1.2a1 1 0 1 0 0-2H9zm4-1h2a2 2 0 0 1 0 4h-1v2h-1v-6zm1 1v2h1a1 1 0 1 0 0-2h-1z"/></svg>
                            </a>
                            <span
                                class="icon-btn icon-view is-disabled"
                                title="View only access"
                                aria-label="View only access"
                            >
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5c5.5 0 9.7 4.1 11 7-1.3 2.9-5.5 7-11 7S2.3 14.9 1 12c1.3-2.9 5.5-7 11-7zm0 2C8.2 7 5 9.7 3.4 12 5 14.3 8.2 17 12 17s7-2.7 8.6-5C19 9.7 15.8 7 12 7zm0 2.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5z"/></svg>
                            </span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php renderAdminLayoutEnd(); ?>
