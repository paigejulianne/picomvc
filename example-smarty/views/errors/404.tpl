{extends file="layout.tpl"}

{block name="content"}
<h1>404 - Page Not Found</h1>

<div class="card">
    <p>The page you're looking for doesn't exist.</p>
    <p style="margin-top: 1rem;">
        <a href="{$baseUrl}/" class="btn">Go Home</a>
    </p>
</div>
{/block}
