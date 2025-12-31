<?php
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
ob_start();
?>

<h1><?= htmlspecialchars($title) ?></h1>

<div class="card">
    <h2>About NanoMVC</h2>
    <p>NanoMVC is a lightweight Model-View-Controller framework for PHP 8.0+ that follows the same design philosophy as PicoORM:</p>

    <ul>
        <li><strong>Minimal footprint</strong> - Single file core</li>
        <li><strong>Zero dependencies</strong> - Only requires PHP 8.0+</li>
        <li><strong>Simple configuration</strong> - Uses .config file</li>
        <li><strong>Convention over configuration</strong> - Sensible defaults</li>
    </ul>

    <h3 style="margin-top: 1.5rem;">Template Engines</h3>
    <p>NanoMVC supports multiple template engines:</p>
    <ul>
        <li><strong>PHP</strong> - Native PHP templates (no dependencies)</li>
        <li><strong>Blade</strong> - Laravel's template engine (requires jenssegers/blade)</li>
        <li><strong>Smarty</strong> - Popular template engine (requires smarty/smarty)</li>
    </ul>

    <h3 style="margin-top: 1.5rem;">Integration with PicoORM</h3>
    <p>NanoMVC is designed to work seamlessly with PicoORM for database operations. Simply create your model classes and use them in your controllers.</p>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/layout.php'; ?>
