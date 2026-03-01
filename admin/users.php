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

$editApp = null;
$editTravellers = [];
$editAppId = (int)($_GET['edit_app'] ?? 0);
$editId = (int)($_GET['edit'] ?? 0);

if ($canManage && $editAppId <= 0 && $editId > 0) {
    $stmt = $db->prepare('SELECT application_id FROM travellers WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $editId]);
    $editAppId = (int)$stmt->fetchColumn();
}

if ($canManage && $editAppId > 0) {
    $stmt = $db->prepare('SELECT * FROM applications WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $editAppId]);
    $editApp = $stmt->fetch() ?: null;

    if ($editApp) {
        $tStmt = $db->prepare('SELECT * FROM travellers WHERE application_id = :id ORDER BY traveller_number');
        $tStmt->execute([':id' => $editAppId]);
        $editTravellers = $tStmt->fetchAll() ?: [];
    }
}

$isEdit = $canManage && $editApp !== null && !empty($editTravellers);

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

    if ($action === 'update_application') {
        if (!$canManage) {
            flash('error', 'Only MasterAdmin can edit users.');
            redirectUsers();
        }

        $appId = (int)($_POST['application_id'] ?? 0);
        if ($appId <= 0) {
            flash('error', 'Invalid application id.');
            redirectUsers();
        }

        $travelMode = sanitizeText($_POST['travel_mode'] ?? 'solo', 10);
        $totalTravellers = (int)($_POST['total_travellers'] ?? 1);
        $paymentStatus = sanitizeText($_POST['payment_status'] ?? 'draft', 20);

        $allowedMode = ['solo', 'group'];
        $allowedStatus = ['draft', 'submitted', 'paid', 'processing', 'approved', 'rejected'];
        if (!in_array($travelMode, $allowedMode, true)) $travelMode = 'solo';
        if (!in_array($paymentStatus, $allowedStatus, true)) $paymentStatus = 'draft';
        if ($totalTravellers < 1) $totalTravellers = 1;

        /** @var array<string, mixed> $payloadTravellers */
        $payloadTravellers = $_POST['travellers'] ?? [];
        if (!is_array($payloadTravellers) || empty($payloadTravellers)) {
            flash('error', 'No traveller details submitted.');
            redirectUsers();
        }

        $editable = [
            'first_name','middle_name','last_name','email','phone','travel_date','purpose_of_visit',
            'date_of_birth','gender','country_of_birth','city_of_birth','marital_status','nationality',
            'passport_country','passport_number','passport_issue_date','passport_expiry','dual_citizen',
            'other_citizenship_country','prev_canada_app','uci_number','address_line','street_number',
            'apartment_number','country','city','postal_code','state','occupation','has_job','job_title',
            'employer_name','employer_country','employer_city','start_year','visa_refusal','visa_refusal_details',
            'tuberculosis','tuberculosis_details','criminal_history','criminal_details','health_condition',
            'decl_accurate','decl_terms','step_completed'
        ];
        $boolFields = ['dual_citizen','prev_canada_app','has_job','visa_refusal','tuberculosis','criminal_history','decl_accurate','decl_terms'];
        $dateFields = ['travel_date','date_of_birth','passport_issue_date','passport_expiry'];

        try {
            $db->beginTransaction();

            $checkApp = $db->prepare('SELECT id FROM applications WHERE id = :id LIMIT 1');
            $checkApp->execute([':id' => $appId]);
            if (!(int)$checkApp->fetchColumn()) {
                throw new RuntimeException('Application not found.');
            }

            $db->prepare('UPDATE applications SET travel_mode = :mode, total_travellers = :total, status = :status WHERE id = :id')
                ->execute([':mode' => $travelMode, ':total' => $totalTravellers, ':status' => $paymentStatus, ':id' => $appId]);

            $ownerStmt = $db->prepare('SELECT id FROM travellers WHERE id = :id AND application_id = :app_id LIMIT 1');

            foreach ($payloadTravellers as $travellerIdRaw => $rowRaw) {
                $travellerId = (int)$travellerIdRaw;
                if ($travellerId <= 0 || !is_array($rowRaw)) continue;

                $ownerStmt->execute([':id' => $travellerId, ':app_id' => $appId]);
                if (!(int)$ownerStmt->fetchColumn()) {
                    continue;
                }

                $setParts = [];
                $params = [':id' => $travellerId];
                foreach ($editable as $field) {
                    if (!array_key_exists($field, $rowRaw)) continue;
                    $val = $rowRaw[$field];

                    if (in_array($field, $boolFields, true)) {
                        $val = ((string)$val === '1') ? 1 : 0;
                    } elseif (in_array($field, $dateFields, true)) {
                        $v = trim((string)$val);
                        if ($v === '') {
                            $val = null;
                        } else {
                            $d = DateTime::createFromFormat('Y-m-d', $v);
                            $val = ($d && $d->format('Y-m-d') === $v) ? $v : null;
                        }
                    } elseif ($field === 'start_year') {
                        $v = trim((string)$val);
                        if ($v === '' || !preg_match('/^\d{4}$/', $v)) {
                            $val = null;
                        } else {
                            $y = (int)$v;
                            $val = ($y >= 1900 && $y <= 2100) ? $y : null;
                        }
                    } elseif ($field === 'email') {
                        $email = sanitizeEmail((string)$val);
                        $val = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
                        if ($val === '') {
                            throw new RuntimeException('Invalid email found in traveller details.');
                        }
                    } else {
                        $val = sanitizeText((string)$val, 1000);
                    }

                    $paramKey = ':' . $field;
                    $setParts[] = "{$field} = {$paramKey}";
                    $params[$paramKey] = $val;
                }

                if (!empty($setParts)) {
                    $sql = 'UPDATE travellers SET ' . implode(', ', $setParts) . ' WHERE id = :id';
                    $up = $db->prepare($sql);
                    $up->execute($params);
                }
            }

            $db->commit();
            flash('success', 'Application updated successfully.');
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            flash('error', 'Update failed: ' . $e->getMessage());
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
    t.application_id,
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
    fat.email_sent_at,
    CASE WHEN pdx.application_id IS NULL THEN 0 ELSE 1 END AS has_docs
FROM travellers t
INNER JOIN applications a ON a.id = t.application_id
LEFT JOIN form_access_tokens fat ON fat.traveller_id = t.id
LEFT JOIN (
    SELECT DISTINCT application_id
    FROM payment_documents
) pdx ON pdx.application_id = t.application_id
ORDER BY t.created_at DESC";

$rows = $db->query($sql)->fetchAll();
renderAdminLayoutStart('Users / Reports', 'users');
?>
<!-- Auto fil card on edit -->
<!-- <div class="user-top-layout">
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
</div> -->

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
                                href="<?= esc(baseUrl('users.php?edit_app=' . (int)$row['application_id'])) ?>"
                                class="icon-btn icon-edit"
                                title="Edit user"
                                aria-label="Edit user"
                            >
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.8 9.94l-3.75-3.75L3 17.25zm17.7-10.04a1 1 0 0 0 0-1.41l-2.5-2.5a1 1 0 0 0-1.41 0L14.9 5.2l3.75 3.75 2.05-1.74z"/></svg>
                            </a>
                            <?php if (!empty($row['application_id']) && !empty($row['has_docs'])): ?>
                                <a
                                    href="<?= esc(baseUrl('documents.php?application_id=' . (int)$row['application_id'])) ?>"
                                    class="icon-btn icon-pdf"
                                    title="View PDFs"
                                    aria-label="View PDFs"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7l-5-5zm1 7V3.5L18.5 7H15zM8 13h2.2a2 2 0 1 1 0 4H9v2H8v-6zm1 1v2h1.2a1 1 0 1 0 0-2H9zm4-1h2a2 2 0 0 1 0 4h-1v2h-1v-6zm1 1v2h1a1 1 0 1 0 0-2h-1z"/></svg>
                                </a>
                            <?php else: ?>
                                <span
                                    class="icon-btn icon-pdf is-disabled"
                                    title="No PDF generated yet"
                                    aria-label="No PDF generated yet"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7l-5-5zm1 7V3.5L18.5 7H15zM8 13h2.2a2 2 0 1 1 0 4H9v2H8v-6zm1 1v2h1.2a1 1 0 1 0 0-2H9zm4-1h2a2 2 0 0 1 0 4h-1v2h-1v-6zm1 1v2h1a1 1 0 1 0 0-2h-1z"/></svg>
                                </span>
                            <?php endif; ?>
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
                            <?php if (!empty($row['application_id']) && !empty($row['has_docs'])): ?>
                                <a
                                    href="<?= esc(baseUrl('documents.php?application_id=' . (int)$row['application_id'])) ?>"
                                    class="icon-btn icon-pdf"
                                    title="View PDFs"
                                    aria-label="View PDFs"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7l-5-5zm1 7V3.5L18.5 7H15zM8 13h2.2a2 2 0 1 1 0 4H9v2H8v-6zm1 1v2h1.2a1 1 0 1 0 0-2H9zm4-1h2a2 2 0 0 1 0 4h-1v2h-1v-6zm1 1v2h1a1 1 0 1 0 0-2h-1z"/></svg>
                                </a>
                            <?php else: ?>
                                <span
                                    class="icon-btn icon-pdf is-disabled"
                                    title="No PDF generated yet"
                                    aria-label="No PDF generated yet"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7l-5-5zm1 7V3.5L18.5 7H15zM8 13h2.2a2 2 0 1 1 0 4H9v2H8v-6zm1 1v2h1.2a1 1 0 1 0 0-2H9zm4-1h2a2 2 0 0 1 0 4h-1v2h-1v-6zm1 1v2h1a1 1 0 1 0 0-2h-1z"/></svg>
                                </span>
                            <?php endif; ?>
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
<?php if ($isEdit): ?>
<?php
$statusOptions = ['draft','submitted','paid','processing','approved','rejected'];
$modeOptions = ['solo', 'group'];
?>
<style>
/* Fallback to enforce on-screen modal even if admin.css is stale/cached. */
.ua-modal-backdrop{
    display:block !important;
    position:fixed !important;
    inset:0 !important;
    background:rgba(9,19,35,.45) !important;
    z-index:9998 !important;
}
.ua-modal{
    display:flex !important;
    position:fixed !important;
    top:4vh !important;
    left:50% !important;
    transform:translateX(-50%) !important;
    width:min(1220px,96vw) !important;
    height:92vh !important;
    z-index:9999 !important;
}
</style>
<div class="ua-modal-backdrop"></div>
<section class="ua-modal" role="dialog" aria-modal="true" aria-labelledby="ua-modal-title">
    <div class="ua-modal-head">
        <h3 id="ua-modal-title">Edit Application: <?= esc((string)$editApp['reference']) ?></h3>
        <a href="<?= esc(baseUrl('users.php')) ?>" class="ua-close" aria-label="Close">x</a>
    </div>
    <form method="post" class="ua-modal-body">
        <input type="hidden" name="action" value="update_application">
        <input type="hidden" name="application_id" value="<?= (int)$editApp['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= esc(csrfToken()) ?>">

        <div class="ua-app-grid">
            <label>Travel Mode
                <select name="travel_mode">
                    <?php foreach ($modeOptions as $m): ?>
                        <option value="<?= esc($m) ?>" <?= ((string)$editApp['travel_mode'] === $m) ? 'selected' : '' ?>><?= esc(ucfirst($m)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Total Travellers
                <input type="number" min="1" max="10" name="total_travellers" value="<?= (int)$editApp['total_travellers'] ?>">
            </label>
            <label>Payment Status
                <select name="payment_status">
                    <?php foreach ($statusOptions as $s): ?>
                        <option value="<?= esc($s) ?>" <?= ((string)$editApp['status'] === $s) ? 'selected' : '' ?>><?= esc(ucfirst($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="ua-tabs" id="ua-tabs">
            <?php foreach ($editTravellers as $idx => $t): ?>
                <button type="button" class="ua-tab-btn<?= $idx === 0 ? ' active' : '' ?>" data-target="ua-tab-<?= (int)$t['id'] ?>">
                    Traveller <?= (int)$t['traveller_number'] ?>
                </button>
            <?php endforeach; ?>
        </div>

        <?php
        $fieldMap = [
            'first_name'=>'First Name','middle_name'=>'Middle Name','last_name'=>'Last Name','email'=>'Email','phone'=>'Phone',
            'travel_date'=>'Travel Date','purpose_of_visit'=>'Purpose of Visit','date_of_birth'=>'Date of Birth','gender'=>'Gender',
            'country_of_birth'=>'Country of Birth','city_of_birth'=>'City of Birth','marital_status'=>'Marital Status','nationality'=>'Nationality',
            'passport_country'=>'Passport Country','passport_number'=>'Passport Number','passport_issue_date'=>'Passport Issue Date',
            'passport_expiry'=>'Passport Expiry','dual_citizen'=>'Dual Citizen','other_citizenship_country'=>'Other Citizenship Country',
            'prev_canada_app'=>'Prev Canada App','uci_number'=>'UCI Number','address_line'=>'Address Line','street_number'=>'Street Number',
            'apartment_number'=>'Apartment Number','country'=>'Residential Country','city'=>'Residential City','postal_code'=>'Postal Code',
            'state'=>'State/Province','occupation'=>'Occupation','has_job'=>'Has Job','job_title'=>'Job Title','employer_name'=>'Employer Name',
            'employer_country'=>'Employer Country','employer_city'=>'Employer City','start_year'=>'Start Year','visa_refusal'=>'Visa Refusal',
            'visa_refusal_details'=>'Visa Refusal Details','tuberculosis'=>'Tuberculosis','tuberculosis_details'=>'Tuberculosis Details',
            'criminal_history'=>'Criminal History','criminal_details'=>'Criminal Details','health_condition'=>'Health Condition',
            'decl_accurate'=>'Declaration Accurate','decl_terms'=>'Declaration Terms','step_completed'=>'Step Completed'
        ];
        $dateFields = ['travel_date','date_of_birth','passport_issue_date','passport_expiry'];
        $boolFields = ['dual_citizen','prev_canada_app','has_job','visa_refusal','tuberculosis','criminal_history','decl_accurate','decl_terms'];
        ?>
        <?php foreach ($editTravellers as $idx => $t): ?>
            <section class="ua-tab-panel<?= $idx === 0 ? ' active' : '' ?>" id="ua-tab-<?= (int)$t['id'] ?>">
                <div class="ua-field-grid">
                    <?php foreach ($fieldMap as $key => $label): ?>
                        <label><?= esc($label) ?>
                            <?php if (in_array($key, $boolFields, true)): ?>
                                <select name="travellers[<?= (int)$t['id'] ?>][<?= esc($key) ?>]">
                                    <option value="0" <?= ((string)($t[$key] ?? '0') === '0') ? 'selected' : '' ?>>No</option>
                                    <option value="1" <?= ((string)($t[$key] ?? '0') === '1') ? 'selected' : '' ?>>Yes</option>
                                </select>
                            <?php elseif (in_array($key, $dateFields, true)): ?>
                                <input type="date" name="travellers[<?= (int)$t['id'] ?>][<?= esc($key) ?>]" value="<?= esc((string)($t[$key] ?? '')) ?>">
                            <?php elseif ($key === 'start_year'): ?>
                                <input type="number" min="1900" max="2100" name="travellers[<?= (int)$t['id'] ?>][<?= esc($key) ?>]" value="<?= esc((string)($t[$key] ?? '')) ?>">
                            <?php elseif (str_contains($key, 'details')): ?>
                                <textarea name="travellers[<?= (int)$t['id'] ?>][<?= esc($key) ?>]" rows="2"><?= esc((string)($t[$key] ?? '')) ?></textarea>
                            <?php elseif ($key === 'email'): ?>
                                <input type="email" name="travellers[<?= (int)$t['id'] ?>][<?= esc($key) ?>]" value="<?= esc((string)($t[$key] ?? '')) ?>">
                            <?php else: ?>
                                <input type="text" name="travellers[<?= (int)$t['id'] ?>][<?= esc($key) ?>]" value="<?= esc((string)($t[$key] ?? '')) ?>">
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <div class="ua-actions">
            <button type="submit">Save</button>
            <a href="<?= esc(baseUrl('users.php')) ?>" class="btn-link-secondary">Cancel</a>
        </div>
    </form>
</section>
<script>
document.body.style.overflow = 'hidden';
document.querySelectorAll('.ua-tab-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.querySelectorAll('.ua-tab-btn').forEach(function(b){ b.classList.remove('active'); });
        document.querySelectorAll('.ua-tab-panel').forEach(function(p){ p.classList.remove('active'); });
        btn.classList.add('active');
        const panel = document.getElementById(btn.dataset.target);
        if (panel) panel.classList.add('active');
    });
});
</script>
<?php endif; ?>
<?php renderAdminLayoutEnd(); ?>
