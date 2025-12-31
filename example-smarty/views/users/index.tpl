{extends file="layout.tpl"}

{block name="content"}
<h1>{$title}</h1>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            {foreach $users as $user}
            <tr>
                <td>{$user.id}</td>
                <td>{$user.name|escape}</td>
                <td>{$user.email|escape}</td>
                <td>
                    <a href="{$baseUrl}/users/{$user.id}" class="btn">View</a>
                </td>
            </tr>
            {/foreach}
        </tbody>
    </table>
</div>
{/block}
