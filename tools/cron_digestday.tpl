<subject>Quarantine digest, {count($mails)} new messages</subject>
<p>You have {count($mails)} messages(s) received {if $recipient}to {$recipient|escape} {/if}in your <a href="{$quarantine_url}">quarantine</a> during the last 24 hours.</p>
<table style="border-collapse: collapse;" cellpadding="4">
	<tr>
		<th>Date</th>
		<th>From</th>
		{if !$recipient}<th>To</th>{/if}
		<th>Subject</th>
		{if $mails.0.release_url}<th>&nbsp;</th>{/if}
		{if $mails.0.release_url_whitelist}<th>&nbsp;</th>{/if}
	</tr>
	{foreach $mails as $mail}
	<tr style="background-color: {cycle values="#eee,#fff"};">
		<td>{$mail.time|strftime2:'%b %e %Y %H:%M:%S'}</td>
		<td>{$mail.from|substrdots:30|escape}</td>
		{if !$recipient}<td>{$mail.to|substrdots:30|escape}</td>{/if}
		<td>{$mail.subject|substrdots:30|escape}</td>
		{if $mail.release_url}<td><a href="{$mail.release_url}">Release</a></td>{/if}
		{if $mail.release_url_whitelist}<td><a href="{$mail.release_url}">Release and whitelist</a></td>{/if}
	</tr>
	{/foreach}
</table>
