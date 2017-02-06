{include file='header.tpl' title='Rate limit' page_active='rates'}
<nav class="navbar navbar-default navbar-toolbar navbar-static-top">
	<div class="container-fluid">
		<form class="navbar-form navbar-left" role="search" id="search_form">
			<div class="input-group">
				<input type="text" class="form-control" size="40" placeholder="{t}Search for...{/t}" id="search" value="{$search|escape}">
				<span class="input-group-btn">
					<button class="btn btn-primary" type="search"><span class="fa fa-search"></span></button>
				</span>
			</div>
		</form>
	</div>
</nav>
<div class="container-fluid">
	{foreach from=$views item=ns name=ns_loop}
		<div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
			<div class="panel panel-default">
				<div class="panel-heading"><h3 class="panel-title">{$ns.name}</h3></div>
				<table class="table table-hover table-fixed" id="rate_{$ns.id}">
					<thead><tr>
						<th style="width: 36px"></th>
						<th colspan="3">{t}Entry{/t}</th>
						<th>{t}Count{/t}</th>
						<th style="width: 30px"></th>
					</tr></thead>
					<tbody>
						<tr>
							<td colspan="6" class="text-muted">{t}Loading...{/t}</td>
						</tr>
					</tbody>
					<tfoot></tfoot>
				</table>
			</div>
		</div>
		{if $smarty.foreach.ns_loop.iteration is div by 3}<div class="clearfix visible-lg-block visible-md-block"></div>{/if}
		{if $smarty.foreach.ns_loop.iteration is div by 2}<div class="clearfix visible-sm-block"></div>{/if}
	{/foreach}
</div>
<script>
	var search = $("#search").val();
	var views = {$views|json_encode};
	var reloadTimeout = 10;
	var text_clear = '{t}Clear{/t}';
	var text_nomatch = '{t}No matches{/t}';
	var text_previous = '{t}Previous{/t}';
	var text_next = '{t}Next{/t}';
	var source = {$source|json_encode};
</script>
<style>
	.nopad {
		padding: 6px 0px 0px 6px !important;
	}
	.nopad > a {
		padding: 0px;
	}
</style>
{include file='footer.tpl'}
