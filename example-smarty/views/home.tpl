{extends file="layout.tpl"}

{block name="content"}
<h1>{$title}</h1>

<div class="card">
    <h2>{$message}</h2>
    <p>This example demonstrates NanoMVC with the <strong>Smarty</strong> templating engine.</p>

    <h3>Smarty Features Used</h3>
    <ul>
        <li><code>{ldelim}extends{rdelim}</code> - Template inheritance</li>
        <li><code>{ldelim}block{rdelim}</code> - Content blocks</li>
        <li><code>{ldelim}$variable{rdelim}</code> - Variable output</li>
        <li><code>{ldelim}foreach{rdelim}</code> - Loops</li>
        <li><code>{ldelim}if{rdelim}</code> / <code>{ldelim}else{rdelim}</code> - Conditionals</li>
        <li>Modifiers like <code>|escape</code> and <code>|date_format</code></li>
    </ul>

    <p style="margin-top: 1rem;">
        <a href="{$baseUrl}/users" class="btn">View Users</a>
    </p>
</div>
{/block}
