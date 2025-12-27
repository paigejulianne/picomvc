<?php
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
ob_start();
?>

<h1><?= htmlspecialchars($title) ?></h1>

<div class="card">
    <h2><?= htmlspecialchars($message) ?></h2>
    <p>PicoMVC is a minimal MVC framework designed with the same simplicity as PicoORM.</p>

    <h3>Features</h3>
    <ul>
        <li>Single-file core (~800 lines)</li>
        <li>Simple routing with parameters</li>
        <li>Multiple template engine support (PHP, Blade, Smarty)</li>
        <li>Built-in validation</li>
        <li>Zero configuration required</li>
        <li>Integrates seamlessly with PicoORM</li>
    </ul>

    <p style="margin-top: 1rem;">
        <a href="<?= $baseUrl ?>/users" class="btn">View Users</a>
    </p>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/layout.php'; ?>
