<?php
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
ob_start();
?>

<h1><?= htmlspecialchars($title) ?></h1>

<div class="card">
    <h2><?= htmlspecialchars($user['name']) ?></h2>

    <table>
        <tr>
            <th>ID</th>
            <td><?= htmlspecialchars($user['id']) ?></td>
        </tr>
        <tr>
            <th>Name</th>
            <td><?= htmlspecialchars($user['name']) ?></td>
        </tr>
        <tr>
            <th>Email</th>
            <td><?= htmlspecialchars($user['email']) ?></td>
        </tr>
    </table>

    <p style="margin-top: 1rem;">
        <a href="<?= $baseUrl ?>/users" class="btn">Back to Users</a>
    </p>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layout.php'; ?>
