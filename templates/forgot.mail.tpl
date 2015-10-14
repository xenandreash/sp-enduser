<subject>{t}Reset password{/t}</subject>
<p>
	{t escape=no url=$public_url}You have requested to reset your password on <a href="%1">%1</a>. Click the link to reset your password.{/t}
</p>
<p>
	<a href="{$reset_url}">{t}Reset password{/t}</a>
</p>
<p>
	{t}Important! If you didn't request a new password, ignore and delete this message. Another user might have typed your email by mistake.{/t}
</p>
<p>
	{t}E-mail{/t}: {$email|escape}<br>
	{t}Token{/t}: {$publictoken}<br>
	{t}IP{/t}: {$ipaddress}<br>
</p>
