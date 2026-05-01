Hello, {$username}! Welcome to TUDO -admin :)

{if $motd_message}
    <div class="motd">
        {$motd_message|escape:'htmlall':'UTF-8'|nl2br}
    </div>
{/if}