{include file='header.tpl' title='Manage users two-factor authentication'}
<div class="container-fluid">
	<div id="items-view" class="col-md-7 col-lg-8">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">{t}Manage two-factor authentication{/t}
					<a class="pull-right" data-toggle="collapse" href="#search">
						<span class="fa fa-search"></span>
					</a>
				</h3>
			</div>
			<div id="search" class="{if $search}collapse in{else}collapse{/if}" aria-expanded="{if $search}true{else}false{/if}">
				<div class="panel-body">
					<form class="form-horizontal" method="get">
						<input type="hidden" name="page" value="{$page_active}">
						<input type="hidden" name="limit" value="{$limit}">
						<div class="input-group">
							<input type="text" class="form-control" placeholder="{t}Search for...{/t}" name="search" value="{$search|escape}">
							<span class="input-group-btn">
								<a href="?page={$page_active}&amp;limit={$limit}" class="btn btn-default" type="clear"><span class="fa fa-times"></span></a>
								<button class="btn btn-primary" type="search"><span class="fa fa-search"></span></button>
							</span>
						</div>
					</form>
				</div>
			</div>
			<table class="table table-hover table-fixed">
				<thead>
					<tr>
						<th>{t}Username{/t}</th>
					</tr>
				</thead>
				<tbody>
					{foreach $users as $user}
					<tr class="item" data-value="{$user.username|escape}">
						<td>{$user.username|escape}</td>
					</tr>
					{foreachelse}
					<tr><td class="text-muted">{t}No username with two-factor authentication found.{/t}</td></tr>
					{/foreach}
				</tbody>
			</table>
		</div>
		{include file='pager.tpl'}
	</div>
	<div id="side-col" class="col-md-5 col-lg-4">
		<div id="side-panel" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">{t}Edit{/t}</h3>
			</div>
			<div class="panel-body">
				<form id="item-form">
					<input id="action" value="remove" type="hidden">
					<input id="username" type="hidden">
					<div class="form-group">
						<label class="control-label">{t}Remove two-factor authentication{/t}</label>
						<p id="display-username" class="visible-edit hidden"></p>
						<p class="hidden-edit"><em>{t}No username selected.{/t}</em></p>
					</div>
					<div class="form-group">
						<button id="btn-remove" type="submit" class="btn btn-primary" disabled>{t}Remove{/t}</button>
						<button id="btn-cancel" type="button" class="btn btn-default" disabled>{t}Cancel{/t}</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
{include file='footer.tpl'}
