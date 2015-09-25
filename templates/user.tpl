{include file='header.tpl' title='Account' page_active='user'}
<div class="container">
	<div class="col-md-6">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">Permissions</h3>
			</div>
			<div class="panel-body">
				{if not $access_mail and not $access_domain}
					<p>
						You have no restrictions, you may view messages to/from any domain.
					</p>
				{else}
					<p>
					You are authorized to view messages sent from/to the following 
					{if $access_domain}
						domains:</p>
						<ul>
							{foreach $access_domain as $access} 
								<li>{$access|escape}</li>
							{/foreach}
						</ul>
						{if $access_mail}
							<p>And the following users:</p>
						{/if}
					{else}
						users:</p>
					{/if}
					<ul>
						{foreach $access_mail as $access} 
							<li>{$access|escape}</li>
						{/foreach}
					</ul>
				{/if}
			</div>
		</div>
	</div>
	<div class="col-md-6">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">Change password</h3>
			</div>
			<div class="panel-body">
				{if $password_changed}
					<div class="alert alert-success">Password changed</div>
				{/if}
				{if $error}
					<div class="alert alert-danger">{$error|escape}</div>
				{/if}
				{if $password_changeable}
					<form class="form-horizontal" method="post" action="?page=user">
						<div class="form-group">
							<label class="col-sm-3 control-label" for="old_password">Old Password</label>
							<div class="col-sm-9">
								<input type="password" class="form-control" name="old_password" id="old_password" placeholder="Your old password">
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label" for="password">New Password</label>
							<div class="col-sm-9">
								<input type="password" class="form-control" name="password" id="password" placeholder="Your new password">
								<input type="password" class="form-control" name="password2" id="password2" placeholder="And again, just to be sure">
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-offset-2 col-sm-10">
								<button type="submit" class="btn btn-primary">Change</button>
							</div>
						</div>
					</form>
				{else}
					<p>
						You are authenticated externally, so password changes are not done here.
					</p>
				{/if}
			</div>
		</div>
	</div>
</div>
{include file='footer.tpl'}
