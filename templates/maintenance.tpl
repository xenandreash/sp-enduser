{include file='header.tpl' title='Maintenance'}
<div class="container">
	<div class="col-md-offset-3 col-md-6">
		<div class="panel panel-default" style="margin-top:40px;">
			<div class="panel-heading">
				<h3 class="panel-title"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i>&nbsp;{t}Maintenance{/t}</h3>
			</div>
			<div class="panel-body">
				<p>{t}Update required. Please run this command on your server.{/t}</p>
                <p><i class="fa fa-terminal" aria-hidden="true"></i>&nbsp;<i>php update.php</i></p>
				<div class="pull-right">
					<a class="btn btn-primary" href="?page=login"><i class="fa fa-refresh"></i>&nbsp;{t}Refresh{/t}</a>
				</div>
			</div>
		</div>
	</div>
</div>
{include file='footer.tpl'}