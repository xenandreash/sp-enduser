{if $type=='create'}{$title='Create password'}{else}{$title='Reset password'}{/if}
{include file='header.tpl'}
<div class="container">
	<div class="col-md-offset-3 col-md-6">
		<div class="panel panel-default" style="margin-top:40px;">
			<div class="panel-heading">
				<h3 class="panel-title">{$title}</h3>
			</div>
			<div class="panel-body">
				{if $error}
					<div class="alert alert-danger">{$error|escape}</div>
				{/if}
				{if $forgot_text}
					<p>{$forgot_text}</p><hr>
				{/if}
				{if $password_reset}
					<p class="alert alert-success">Your password has been {if $type=='create'}created{else}reset{/if}.</p>
					<div class="col-sm-offset-3 col-sm-9">
						<a class="btn btn-primary" href="?page=login">Sign in</a>
					</div>
				{elseif $reset and (not $error or $token)}
				<form class="form-horizontal" method="post" action="?page=forgot">
					<input type="hidden" name="type" value="{$type|escape}">
					<input type="hidden" name="reset" value="{$reset|escape}">
					{if $token}
						<p>Choose a new password.</p>
						<input type="hidden" name="token" value="{$token|escape}">
					{else}
						<p class="alert alert-success">Enter the token you received in your inbox, and choose a new password.</p>
						<div class="form-group">
							<label class="control-label col-sm-3" for="token">Token</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="token" autofocus>
							</div>
						</div>
					{/if}
					<div class="form-group">
						<label class="control-label col-sm-3" for="password">Password</label>
						<div class="col-sm-9">
							<input type="password" class="form-control" name="password" autofocus>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-3" for="password2">Repeat password</label>
						<div class="col-sm-9">
							<input type="password" class="form-control" name="password2">
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-offset-3 col-sm-9">
							<button type="submit" class="btn btn-primary">{if $type=='create'}Create password{else}Change password{/if}</button>
						</div>
					</div>
				</form>
				{else}
				<form class="form-horizontal" method="get">
					<input type="hidden" name="page" value="forgot">
					<div class="form-group">
						<label class="control-label col-sm-3" for="reset">E-mail</label>
						<div class="col-sm-9">
							<input type="text" class="form-control" name="reset" autofocus>
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-offset-3 col-sm-9">
							<button type="submit" class="btn btn-primary">Reset password</button>
							<a class="btn btn-default" href="?page=login">Login</a>
						</div>
					</div>
				</form>
				{/if}
			</div>
		</div>
	</div>
</div>
{include file='footer.tpl'}
