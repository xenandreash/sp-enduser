{include file='header.tpl' title='Sign in'}
<div class="container">
	<div class="col-md-offset-3 col-md-6">
		<div class="panel panel-default" style="margin-top:40px;">
			<div class="panel-heading">
				<h3 class="panel-title">{t}Sign in{/t}</h3>
			</div>
			<div class="panel-body">
				{if $error}
					<div class="alert alert-danger">{$error|escape}</div>
				{/if}
				{if $login_text}
					<p>{$login_text}</p><hr>
				{/if}
				<form class="form-horizontal" method="post" action="?page=login">
					<input type="hidden" name="timezone" id="timezone">
					<input type="hidden" name="query" id="query" value="{$query|escape}">
					<div class="form-group">
						<label for="username" class="control-label col-sm-3">{t}Username{/t}</label>
						<div class="col-sm-9">
							<input type="text" class="form-control" name="username" id="username" autofocus="autofocus">
						</div>
					</div>
					<div class="form-group">
						<label for="username" class="control-label col-sm-3">{t}Password{/t}</label>
						<div class="col-sm-9">
							<input type="password" class="form-control" name="password" id="password">
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-offset-3 col-sm-9">
							<button type="submit" class="btn btn-primary"><i class="fa fa-sign-in"></i>&nbsp;{t}Sign in{/t}</button>
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
</script>
{include file='footer.tpl'}
