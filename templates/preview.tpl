{include file='header.tpl' title='Message' show_back=true body_class='has-bottom-bar'}
<nav class="navbar navbar-toolbar navbar-static-top hidden-xs">
	<div class="container-fluid">
		<div class="navbar-header">
			<a id="history_back" class="navbar-brand" href="javascript:history.go(-1);">&larr;&nbsp;Back</a>
		</div>
		<ul class="nav navbar-nav navbar-right">
			{if isset($node)}
				{if $support_log}
					<li><a href="?page=log&id={$mail->id}&node={$node}&type={$type}"><i class="glyphicon glyphicon-book"></i>&nbsp;Text log</a></li>
				{/if}
				{if $type == 'queue'}
					<li><a href="?page=download&id={$mail->id}&node={$node}"><i class="glyphicon glyphicon-download-alt"></i>&nbsp;Download</a></li>
					<li class="divider"></li>
					<li><a data-action="delete"><i class="glyphicon glyphicon-trash"></i>&nbsp;Delete</a></li>
					<li><a data-action="bounce"><i class="glyphicon glyphicon-arrow-left"></i>&nbsp;Bounce</a></li>
					<li><a data-action="retry"><i class="glyphicon glyphicon-play-circle"></i>&nbsp;Retry/release</a></li>
				{/if}
			{/if}
		</ul>
	</div>
</nav>
{if isset($node) and ($support_log or $type == 'queue')}
<nav class="navbar navbar-default navbar-fixed-bottom visible-xs" id="bottom-bar">
	<div class="container-fluid">
		<ul class="nav navbar-nav">
			<li class="dropdown">
				<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Actions <span class="caret"></span></a>
				<ul class="dropdown-menu" role="menu">
					{if $support_log}
						<li><a href="?page=log&id={$mail->id}&node={$node}&type={$type}"><i class="glyphicon glyphicon-book"></i>&nbsp;Text log</a></li>
					{/if}
					{if $type == 'queue'}
						<li><a href="?page=download&id={$mail->id}&node={$node}"><i class="glyphicon glyphicon-download-alt"></i>&nbsp;Download</a></li>
						<li class="divider"></li>
						<li><a data-action="delete"><i class="glyphicon glyphicon-trash"></i>&nbsp;Delete</a></li>
						<li><a data-action="bounce"><i class="glyphicon glyphicon-arrow-left"></i>&nbsp;Bounce</a></li>
						<li><a data-action="retry"><i class="glyphicon glyphicon-play-circle"></i>&nbsp;Retry/release</a></li>
					{/if}
				</ul>
			</li>
		</ul>
	</div>
</nav>
{/if}
<div class="container-fluid">
	<div class="row">
		<div class="col-md-5 col-md-push-7">
			<div class="panel panel-default panel-{$action_class}">
				<div class="panel-heading">
					<h3 class="panel-title">Details</h3>
				</div>
				<div class="panel-body">
					<dl class="dl-horizontal">
						<dt>Action</dt><dd>
							<span class="glyphicon glyphicon-{$action_icon}"></span>
							{$mail->msgaction}
						</dd>
						<dt>From</dt><dd class="wrap">{$mail->msgfrom|escape}&nbsp;</dd>
						<dt>To</dt><dd class="wrap">{$mail->msgto|escape}&nbsp;</dd>
						<dt>Date</dt><dd>{$date}</dd>
						<dt>Size</dt><dd class="wrap" title="{$mail->msgsize} bytes">{$mail->msgsize|format_size}&nbsp;</dd>
						<dt>Details</dt><dd>
						{if $mail->msgaction == 'QUEUE'}
							In queue (retry {$mail->msgretries})<br><span class="text-muted">{$mail->msgerror|escape}</span>
						{else}
							{$mail->msgdescription|escape}
						{/if}
						{if $listener}<dt>Received by</dt><dd>{$listener}</dd>{/if}
						<dt>Server</dt><dd>{$mail->msgfromserver}&nbsp;</dd>
						{if $mail->msgsasl}<dt>User</dt><dd>{$mail->msgsasl|escape}</dd>{/if}
						{if $transport}<dt>Destination</dt><dd>{$transport}</dd>{/if}
						<dt>ID</dt><dd>{$mail->msgid}</dd>
					</dl>
				</div>
			</div>
			{if $scores}
			<div class="panel panel-default hidden-xs">
				<div class="panel-heading">
					<h3 class="panel-title">Scores</h3>
				</div>
				<table class="table">
					<thead>
						<tr>
							<th>Engine</th>
							<th>Result</th>
							<th class="hidden-xs">Signature</th>
						</tr>
					</thead>
					<tbody>
					{foreach $scores as $score}
						<tr>
							<td>{$score.name}</td>
							<td>{$score.score}</td>
							<td class="text-muted hidden-xs wrap">{$score.text}</td>
						</tr>
					{foreachelse}
						<tr>
							<td colspan="3" class="text-muted text-center">No Scores</td>
						</tr>
					{/foreach}
					</tbody>
				</table>
			</div>
			{/if}
		</div>
		<div class="col-md-7 col-md-pull-5">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title" style="white-space: nowrap;">{if $mail->msgsubject}{$mail->msgsubject|escape}{else}<span class="text-muted">No Subject</span>{/if}</h3>
				</div>
				{if ! isset($body)}
					<div class="panel-body msg-body"><p class="text-muted text-center">Content unavailable<br><small>Message is not in queue or quarantine</small></p></div>
				{elseif $encode == 'TEXT'}
					<pre class="panel-body msg-body">{$body}</pre>
				{elseif $encode == 'HTML'}
					<div class="panel-body msg-body">{$body}</div>
				{/if}
				{if $attachments}
				<div class="panel-footer">
					<ul class="list-inline">
						{foreach $attachments as $attachment}
							<li class="nowrap">
								<i class="glyphicon glyphicon-paperclip"></i>
								{$attachment.2|escape}&nbsp;<small class="text-muted">({$attachment.1|format_size})</small>
							</li>
						{/foreach}
					</ul>
				</div>
				{/if}
			</div>
			{if (isset($body) && $encode == 'HTML') or $show_text}
				<p style="float:right;">
				{if $encode == 'HTML'}
					Switch to <a href="{$show_text_link}">plain text</a> version
				{/if}
				{if $show_text}
					Switch to <a href="{$show_html_link}">HTML</a> version
				{/if}
				</p>
				<br style="clear:both">
			{/if}
			{if $header}
			<div class="panel panel-default">
				<div class="panel-heading">
					<div class="pull-right preview-headers-legend">
						<div style="background-color: #ddffdd; border: 1px solid #ccc;"></div>
						<p style="color: green;">Added</p>
						<div style="background-color: #ffdddd; border: 1px solid #ccc;"></div>
						<p style="color: red;">Removed</p>
					</div>
					<h3 class="panel-title">Headers</h3>
				</div>
				<div class="panel-body preview-headers-container">
					<div class="preview-headers wrap" id="preview-headers-go-here"></div>
				</div>
			</div>
			<script>
				var headers_original = {$header};
				var headers_modified = {$headerdelta};
				$("#preview-headers-go-here").html(diff_lineMode(headers_original,
					headers_modified ? headers_modified : headers_original, true));
			</script>
			{/if}
		</div>
	</div>
	{if isset($node)}
	<form id="actionform" method="post" action="?page=preview&node={$node}&id={$mail->id}">
		<input type="hidden" name="action" id="action" value="">
		<input type="hidden" name="referer" id="referer" value="{$referer|escape}">
	</form>
	{/if}
</div>
{include file='footer.tpl'}
