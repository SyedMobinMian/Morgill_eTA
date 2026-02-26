<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
requireAdmin();

// Public form links ki static mapping.
$forms = [
    ['name' => 'Canada Form', 'country' => 'Canada', 'path' => 'form-canada.php', 'class' => 'form-country-canada'],
    ['name' => 'Vietnam Form', 'country' => 'Vietnam', 'path' => 'form-vietnam.php', 'class' => 'form-country-vietnam'],
    ['name' => 'UK Form', 'country' => 'UK', 'path' => 'form-uk.php', 'class' => 'form-country-uk'],
];

renderAdminLayoutStart('Forms', 'forms');
?>
<div class="form-link-grid">
    <?php foreach ($forms as $form): ?>
        <?php $url = rtrim(APP_URL, '/') . '/' . $form['path']; ?>
        <a
            class="form-link-card <?= esc($form['class']) ?>"
            href="<?= esc($url) ?>"
            target="_blank"
            rel="noopener"
            title="Open <?= esc($form['country']) ?> Form"
            aria-label="Open <?= esc($form['country']) ?> Form"
        >
            <span class="form-link-badge"><?= esc($form['country']) ?></span>
            <h3><?= esc($form['name']) ?></h3>
            <p>Open the live public application form</p>
            <span class="form-link-cta">Open Form</span>
        </a>
    <?php endforeach; ?>
</div>
<?php renderAdminLayoutEnd(); ?>
