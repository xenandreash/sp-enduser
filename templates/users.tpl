{include file='header.tpl' title='Database users'}
<div class="container-fluid">
	<div class="col-md-7 col-lg-8">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">
					{t}Database users{/t}
					<a class="pull-right" data-toggle="collapse" href="#search">
						<span class="fa fa-search"></span>
					</a>
					<a id="link-add" class="pull-right visible-sm visible-xs" style="margin-right: 15px;">
						<span class="fa fa-plus"></span>
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
								<button class="btn btn-primary" type="search"><span class="fa fa-search"></span></button>
							</span>
						</div>
					</form>
				</div>
			</div>
			<table id="items" class="table table-hover table-fixed">
				<thead class="hidden-xs">
					<tr>
						<th>{t}Username{/t}</th>
						<th>{t}Permissions{/t}</th>
						<th style="width: 30px"></th>
						<th style="width: 30px"></th>
						<th style="width: 30px"></th>
					</tr>
				</thead>
				<tbody>
				{foreach from=$items key=value item=accesses}
					{if count($accesses) > 0}
						{$item_id = $item_id + 1}
						{$toggle_id = $toggle_id + 1}
						<tr id="item-{$item_id}" data-toggle="{$toggle_id}" data-value="{$value|escape}" data-access="{implode(',', $accesses)|escape}">
							<td class="toggle hidden-xs">{$value|escape}</td>
							<td class="toggle hidden-xs"><span class="badge">{count($accesses)}</span></td>
							<td class="toggle visible-xs">
								<dl class="dl-horizontal dl-horizontal-xs">
									<dt>{t}Username{/t}</dt><dd>{$value|escape}</dd>
									<dt>{t}Permissions{/t}</dt><dd><span class="badge">{count($accesses)}</span></dd>
								</dl>
							</td>
							<td class="toggle" style="width: 30px; vertical-align: middle">
								<span class="expand-icon fa fa-expand"></span>
							</td>
							<td class="edit-user item" style="width: 30px; vertical-align: middle">
								<a title="{t}Edit{/t}"><i class="item fa fa-pencil-square-o"></i></a>
							</td>
							<td class="toggle" style="width: 30px; vertical-align: middle">
								<a class="item-delete" title="{t}Remove{/t}"><i class="fa fa-remove"></i></a>
							</td>
						</tr>
						{foreach $accesses as $access}
						{$item_id = $item_id + 1}
						<tr id="item-{$item_id}" data-value="{$value|escape}" data-access="{$access|escape}" style="display: none;" class="edit-access item item-hidden hidden-{$toggle_id} info">
							<td class="item-value hidden-xs">{$value|escape}</td>
							<td class="item-access hidden-xs">{if $access}{$access|escape}{else}<span class="text-muted">{t}No restrictions{/t}</span>{/if}</td>
							<td class="visible-xs">{$access|escape}</td>
							<td style="width: 30px;"></td>
							<td style="width: 30px; vertical-align: middle">
								{if count($accesses) > 1}<a title="{t}Edit{/t}"><i class="fa fa-pencil-square-o"></i></a>{/if}
							</td>
							<td style="width: 30px; vertical-align: middle">
								{if count($accesses) > 1}
									<a class="item-delete" title="{t}Remove{/t}"><i class="fa fa-remove"></i></a>
								{else}
									<a title="{t}Edit{/t}"><i class="fa fa-pencil-square-o"></i></a>
								{/if}
							</td>
						</tr>
						{/foreach}
					{else}
						{$item_id = $item_id + 1}
						<tr id="item-{$item_id}" data-value="{$value|escape}" data-access="" class="edit-user item">
							<td class="item-value hidden-xs">{$value|escape}</td>
							<td class="item-access hidden-xs"><span class="text-muted">{t}No restrictions{/t}</span></td>
							<td class="visible-xs">
								<dl class="dl-horizontal dl-horizontal-xs">
									<dt>{t}Username{/t}</dt><dd>{$value|escape}</dd>
									<dt>{t}Permissions{/t}</dt><dd><span class="text-muted">{t}No restrictions{/t}</span></dd>
								</dl>
							</td>
							<td style="width: 30px;"></td>
							<td style="width: 30px; vertical-align: middle">
								<a title="{t}Edit{/t}"><i class="fa fa-pencil-square-o"></i></a>
							</td>
							<td style="width: 30px; vertical-align: middle">
								<a class="item-delete" title="{t}Remove{/t}"><i class="fa fa-remove"></i></a>
							</td>
						</tr>
					{/if}
				{foreachelse}
					<tr><td colspan="6" class="text-muted">{t}No users{/t}</td></tr>
				{/foreach}
				</tbody>
			</table>
		</div>
		{include file='pager.tpl'}
	</div>
	<div class="col-md-5 col-lg-4">
		<div id="side-panel" class="panel panel-default">
			<div class="panel-heading">
				<h3 id="side-panel-title" class="panel-title">{t}Add user{/t}</h3>
			</div>
			<div class="panel-body">
				<form class="form-horizontal" id="item-form">
					<input id="action" value="add-user" type="hidden">
					<input id="edit-id" type="hidden">
					<div class="form-group">
						<label class="control-label col-md-3">{t}Username{/t}</label>
						<div class="col-md-9">
							<input id="value" type="text" class="form-control hidden-edit-access" placeholder="{t}E.g. email address{/t}">
							<p id="value-static" class="form-control-static visible-edit-access hidden">{$value|escape}</p>
						</div>
					</div>
					<div class="form-group hidden-edit-access">
						<label class="control-label col-md-3">{t}Password{/t}</label>
						<div class="col-md-9">
							<button id="btn-pwd" type="button" class="btn btn-default visible-edit-user hidden">{t}Change{/t}</button>
							<input id="password-1" type="password" class="form-control hidden-edit-user" placeholder="{t}New password{/t}">
						</div>
					</div>
					<div id="repeat-password-group" class="form-group hidden-edit-user hidden-edit-access">
						<div class="col-md-offset-3 col-md-9">
							<input id="password-2" type="password" class="form-control" placeholder="{t}Repeat new password{/t}">
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-md-3 hidden-edit-access">{t}Permissions{/t}</label>
						<label class="control-label col-md-3 visible-edit-access hidden">{t}Permission{/t}</label>
						<div class="col-md-9">
							<input id="access" type="text" class="form-control visible-edit-access hidden" placeholder="{t}Email or domain{/t}">
							<div class="checkbox hidden-edit-user hidden-edit-access">
								<label>
									<input id="full-access" type="checkbox" checked> {t}No restrictions{/t}
								</label>
							</div>
							<p id="user-description" class="form-control-static visible-edit-user hidden"></p>
							<button id="btn-access" type="button" class="btn btn-default visible-edit-user hidden">{t}Add permissions{/t}</button>
							<button id="btn-clear" type="button" class="btn btn-info hidden">{t}Clear{/t}</button>
							<div id="extra-accesses" class="hidden-edit-access"></div>
						</div>
					</div>
					<div class="form-group">
						<div class="col-md-offset-3 col-md-9">
							<button id="btn-submit" type="submit" class="btn btn-primary">{t}Add{/t}</button>
							<button id="btn-cancel" type="button" class="btn btn-default visible-edit-user visible-edit-access hidden">{t}Cancel{/t}</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<script>
	var title_add_user = '{t}Add user{/t}';
	var title_edit_user = '{t}Edit user{/t}';
	var title_edit_permission = '{t}Edit permission{/t}';
	var placeholder_access = '{t}Email or domain{/t}';
	var button_add = '{t}Add{/t}';
	var button_edit = '{t}Save{/t}';
	var description_full_access = '{t}No restrictions{/t}';
	var description_restricted_access = '{t}existing permission(s){/t}';
</script>
{include file='footer.tpl'}
