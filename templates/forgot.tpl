{capture assign="title"}{if $type=='create'}{t}Create password{/t}{else}{t}Reset password{/t}{/if}{/capture}
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
						<a class="btn btn-primary" href="?page=login">{t}Sign in{/t}</a>
					</div>
				{elseif $reset and (not $error or $token)}
				<form class="form-horizontal" method="post" action="?page=forgot">
					<input type="hidden" name="type" value="{$type|escape}">
					<input type="hidden" name="reset" value="{$reset|escape}">
					{if $token}
						<p>{t}Choose a new password.{/t}</p>
						<input type="hidden" name="token" value="{$token|escape}">
					{else}
						<p class="alert alert-success">{t}Enter the token you received in your inbox, and choose a new password.{/t}</p>
						<div class="form-group">
							<label class="control-label col-sm-3" for="token">{t}Token{/t}</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="token" autofocus>
							</div>
						</div>
					{/if}
					<div class="form-group">
						<label class="control-label col-sm-3" for="password">{t}Password{/t}</label>
						<div class="col-sm-9">
							<input type="password" class="form-control" name="password" autofocus>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-3" for="password2">{t}Repeat password{/t}</label>
						<div class="col-sm-9">
							<input type="password" class="form-control" name="password2">
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-offset-3 col-sm-9">
							<button type="submit" class="btn btn-primary">{if $type=='create'}{t}Create password{/t}{else}{t}Change password{/t}{/if}</button>
						</div>
					</div>
				</form>
				{else}
				<form class="form-horizontal" method="get">
					<input type="hidden" name="page" value="forgot">
					<div class="form-group">
						<label class="control-label col-sm-3" for="reset">{t}E-mail{/t}</label>
						<div class="col-sm-9">
							<input type="text" class="form-control" name="reset" autofocus>
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-offset-3 col-sm-9">
							<button type="submit" class="btn btn-primary">{t}Reset password{/t}</button>
							<a class="btn btn-default" href="?page=login">{t}Sign in{/t}</a>
						</div>
					</div>
				</form>
				{/if}
			</div>
		</div>
	</div>
</div>
{include file='footer.tpl'}
