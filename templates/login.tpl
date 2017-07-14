{include file='header.tpl' title='Sign in'}
<div class="container">
	<div class="col-md-offset-3 col-md-6">
		<div class="panel panel-default" style="margin-top:40px;">
			<div class="panel-heading">
				<h3 class="panel-title">{if $totp}{t}Two-factor authentication{/t}{else}{t}Sign in{/t}{/if}</h3>
			</div>
			<div class="panel-body">
				{if $error}
					<div class="alert alert-danger">{$error|escape}</div>
				{/if}
				{if $login_text}
					<p>{$login_text}</p><hr>
				{/if}
				<form class="form-horizontal" method="post" action="?page=login">
					<input type="hidden" name="query" id="query" value="{$query|escape}">
					{if $totp}
					<div class="form-group has-warning">
						<div class="col-sm-offset-3 col-sm-9">
							<div class="input-group">
								<span class="input-group-addon"><i class="fa fa-mobile" aria-hidden="true"></i></span>
								<input type="text" class="totp-transition form-control" name="totp_verify_key" id="totp_verify_key" placeholder="{t}Two-factor token{/t}" pattern="[0-9]{literal}{6}{/literal}" autocomplete="off" required autofocus="autofocus">
							</div>
						</div>
					</div>
					{else}
					<input type="hidden" name="timezone" id="timezone">
					<input type="hidden" name="useiframe" id="useiframe">
					<div class="form-group">
						<label for="username" class="control-label col-sm-3">{t}Username{/t}</label>
						<div class="col-sm-9">
							<input type="text" class="form-control" name="username" id="username" required autofocus="autofocus">
						</div>
					</div>
					<div class="form-group">
						<label for="username" class="control-label col-sm-3">{t}Password{/t}</label>
						<div class="col-sm-9">
							<input type="password" class="form-control" name="password" required id="password">
						</div>
					</div>
					{/if}
					<div class="form-group">
						<div class="col-sm-offset-3 col-sm-9">
							<button type="submit" class="btn btn-primary"><i class="fa fa-sign-in"></i>&nbsp;{t}Sign in{/t}</button>
							{if $totp}
								<a class="btn btn-default" href="?page=logout">{t}Abort{/t}</a>
							{/if}
							{if $forgot_password}
								<a class="btn btn-default" href="?page=forgot">{t}Forgot password{/t}</a>
							{/if}
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<script>
	$("#timezone").val(new Date().getTimezoneOffset());
	
	if ($("<iframe>").prop("sandbox") !== undefined && $("<iframe>").prop("srcdoc") !== undefined) {
		$("#useiframe").val(true);
	}
</script>
{include file='footer.tpl'}
