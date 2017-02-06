{include file='header.tpl' title='Data store'}
<div class="container-fluid">
	<div class="col-md-7 col-lg-8">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">
					{t}Data store{/t}
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
						<div class="form-group">
							<label class="col-sm-2 control-label">{t}Namespace{/t}</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" placeholder="{t}Search for...{/t}" name="ns" value="{$search.ns|escape}">
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label">{t}Key{/t}</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" placeholder="{t}Search for...{/t}" name="key" value="{$search.key|escape}">
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label">{t}Value{/t}</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" placeholder="{t}Search for...{/t}" name="value" value="{$search.value|escape}">
							</div>
						</div>
						<button class="btn btn-primary pull-right" type="search"><span class="fa fa-search"></span></button>
					</form>
				</div>
			</div>
			<table class="table table-hover table-fixed">
				<thead class="hidden-xs">
					<tr>
						<th>{t}Namespace{/t}</th>
						<th>{t}Key{/t}</th>
						<th>{t}Value{/t}</th>
						<th style="width: 30px"></th>
						<th style="width: 30px"></th>
					</tr>
				</thead>
				<tbody>
				{foreach $items as $item}
					{$item_id = $item_id + 1}
					<tr id="item-{$item_id}" data-namespace="{$item.namespace|escape}" data-key="{$item.key|escape}" data-value="{$item.value|escape}" class="item">
					<td class="item-namespace hidden-xs">{$item.namespace|escape}</td>
					<td class="item-key hidden-xs">{$item.key|escape}</td>
					<td class="item-value hidden-xs">{$item.value|escape}</td>
						<td class="visible-xs">
							<dl class="dl-horizontal dl-horizontal-xs">
								<dt>{t}Namespace{/t}</dt><dd>{$item.namespace|escape}</dd>
								<dt>{t}Key{/t}</dt><dd>{$item.key|escape}</dd>
								<dt>{t}Value{/t}</dt><dd>{$item.value|escape}</dd>
							</dl>
						</td>
						<td style="width: 30px; vertical-align: middle">
							<a title="{t}Edit{/t}"><i class="fa fa-pencil-square-o"></i></a>
						</td>
						<td style="width: 30px; vertical-align: middle">
							<a class="item-delete" title="{t}Remove{/t}"><i class="fa fa-remove"></i></a>
						</td>
					</tr>
				{foreachelse}
					<tr><td colspan="6" class="text-muted">{t}No items{/t}</td></tr>
				{/foreach}
				</tbody>
			</table>
		</div>
		{include file='pager.tpl'}
	</div>
	<div class="col-md-5 col-lg-4">
		<div id="side-panel" class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title hidden-edit">{t}Add...{/t}</h3>
				<h3 class="panel-title visible-edit hidden">{t}Edit{/t}</h3>
			</div>
			<div class="panel-body">
				<form id="item-form">
					<input type="hidden" id="action" value="add">
					<input id="edit-id" type="hidden">
					<div class="form-group">
						<label for="type">{t}Namespace{/t}</label>
						<input id="namespace" type="text" class="form-control hidden-edit">
						<p id="edit-namespace" class="form-control-static visible-edit hidden"></p>
					</div>
					<div class="form-group">
						<label for="type">{t}Key{/t}</label>
						<input id="key" type="text" class="form-control hidden-edit">
						<p id="edit-key" class="form-control-static visible-edit hidden"></p>
					</div>
					<div class="form-group">
						<label for="type">{t}Value{/t}</label>
						<input id="value" type="text" class="form-control">
					</div>
					<div class="form-group">
						<button id="btn-add" type="submit" class="btn btn-primary hidden-edit">{t}Add{/t}</button>
						<button id="btn-edit" type="submit" class="btn btn-primary visible-edit hidden">{t}Save{/t}</button>
						<button id="btn-cancel" type="button" class="btn btn-default visible-edit hidden">{t}Cancel{/t}</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
{include file='footer.tpl'}
