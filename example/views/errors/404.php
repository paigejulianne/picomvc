<?php
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
ob_start();
?>

<h1>404 - Page Not Found</h1>

<div class="card">
    <p>The page you're looking for doesn't exist.</p>
    <p style="margin-top: 1rem;">
        <a href="<?= $baseUrl ?>/" class="btn">Go Home</a>
    </p>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layout.php'; ?>
