{include file='header.tpl' title='Rate limit' page_active='rates'}
<nav class="navbar navbar-toolbar navbar-static-top">
	<div class="container-fluid">
		<form class="navbar-form navbar-left" role="search">
			<input type="hidden" name="page" value="rates">
			<div class="input-group">
				<span class="input-group-addon">
					<span class="fa fa-search"></span>
				</span>
				<input type="text" class="form-control" placeholder="{t}Search for...{/t}" name="search" value="{$search|escape}">
				<span class="input-group-btn">
					<button class="btn btn-default" type="search">{t}Search{/t}</button>
				</span>
			</div>
		</form>
	</div>
</nav>
<div class="container-fluid">
	{if $errors}
		<p class="text-muted">{t}Some messages might not be available at the moment due to maintenance.{/t}</p>
	{/if}
	{foreach from=$namespaces item=ns name=ns_loop}
		<div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
			<div class="panel panel-default">
				<div class="panel-heading"><h3 class="panel-title">{$ns.name}</h3></div>
				<table class="table table-hover">
					<thead><tr>
						<th style="width: 36px"></th>
						<th colspan="3">{t}Entry{/t}</th>
						<th>{t}Count{/t}</th>
						<th style="width: 30px"></th>
					</tr></thead>
					<tbody>
					{foreach $ns.items as $item}
						<tr><td class="nopad">
							{if $ns.count_limit and $item.count >= $ns.count_limit}
								{if $item.search_filter}<a href="?source={$source}&search={$item.search_filter}{if $ns.action.type}+action%3D{$ns.action.type}{/if}">{/if}
									<span class="fa-stack" style="font-size: 12px;">
										{if $ns.action.type}
											<i class="fa fa-square fa-stack-2x" style="color:{$ns.action.color};"></i>
											<i class="fa fa-lg fa-{$ns.action.icon} fa-stack-1x" style="color:#fff;"></i>
										{else}
											<i class="fa fa-square fa-stack-2x" style="color:#9d9d9d;"></i>
											<i class="fa fa-lg fa-exclamation fa-stack-1x" style="color:#fff;"></i>
										{/if}
									</span>
								{if $item.search_filter}</a>{/if}
							{/if}
						</td>
						<td colspan="3">
							{if $item.search_filter}<a href="?source={$source}&search={$item.search_filter}">{$item.entry|escape}</a>
							{else}{$item.entry|escape}{/if}
						</td>
						<td>{$item.count}</td>
						<td style="vertical-align: middle">
							<a data-entry="{$item.entry|escape}" data-ns="{$ns.name|escape}" class="rate_clear" title="{t}Clear{/t}" href="#"><i class="fa fa-remove"></i></a>
						</td></tr>
					{foreachelse}
						<tr><td colspan="6" class="text-muted">{t}No matches{/t}</td></tr>
					{/foreach}
					</tbody>
				</table>
			</div>
		</div>
		{if $smarty.foreach.ns_loop.iteration is div by 3}<div class="clearfix visible-lg-block visible-md-block"></div>{/if}
		{if $smarty.foreach.ns_loop.iteration is div by 2}<div class="clearfix visible-sm-block"></div>{/if}
	{/foreach}
</div>
{if $errors}
	<div style="padding-left: 17px;">
		<span class="text-muted">
		{t}Diagnostic information:{/t}
		<ul>
		{foreach $errors as $error}
			<li>{$error|escape}</li>
		{/foreach}
		</ul>
		</span>
	</div>
{/if}
<style>
	table {
		table-layout: fixed;
	}
	td {
		text-overflow: ellipsis;
		white-space: nowrap;
		overflow: hidden;
	}
	.nopad {
		padding: 6px 0px 0px 6px !important;
	}
	.nopad > a {
		padding: 0px;
	}
</style>
{include file='footer.tpl'}
