<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
requireAdmin();

// Public form links ki static mapping.
$forms = [
    ['name' => 'Canada Form', 'country' => 'Canada', 'path' => 'form-canada.php'],
    ['name' => 'Vietnam Form', 'country' => 'Vietnam', 'path' => 'form-vietnam.php'],
    ['name' => 'UK Form', 'country' => 'UK', 'path' => 'form-uk.php'],
];

renderAdminLayoutStart('Forms', 'forms');
?>
<div class="cards">
    <!-- Har form card loop se render ho raha hai -->
    <?php foreach ($forms as $form): ?>
        <article>
            <h3><?= esc($form['name']) ?></h3>
            <p>Country: <?= esc($form['country']) ?></p>
            <p>Public base form link:</p>
            <code><?= esc(rtrim(APP_URL, '/') . '/' . $form['path']) ?></code>
            <p style="margin-top:10px;">
                <a href="<?= esc(rtrim(APP_URL, '/') . '/' . $form['path']) ?>" target="_blank" rel="noopener">Open <?= esc($form['country']) ?> Form</a>
            </p>
        </article>
    <?php endforeach; ?>
</div>
<?php renderAdminLayoutEnd(); ?>
