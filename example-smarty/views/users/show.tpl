{extends file="layout.tpl"}

{block name="content"}
<h1>{$title}</h1>

<div class="card">
    <h2>{$user.name|escape}</h2>

    <table>
        <tr>
            <th>ID</th>
            <td>{$user.id}</td>
        </tr>
        <tr>
            <th>Name</th>
            <td>{$user.name|escape}</td>
        </tr>
        <tr>
            <th>Email</th>
            <td>{$user.email|escape}</td>
        </tr>
    </table>

    <p style="margin-top: 1rem;">
        <a href="{$baseUrl}/users" class="btn">Back to Users</a>
    </p>
</div>
{/block}
