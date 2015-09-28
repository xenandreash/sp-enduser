{include file='header.tpl' title='Black/whitelist' page_active='bwlist'}
{if $error}
<div class="container-fluid">
	<div class="alert alert-danger" role="alert">
		{$error}
	</div>
</div>
{/if}
<div class="container-fluid">
	<div class="col-md-6 col-lg-8">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">
					Black/whitelist
					<a class="pull-right" data-toggle="collapse" href="#search">
						<span class="glyphicon glyphicon-search"></span>
					</a>
				</h3>
			</div>
			<div id="search" class="{if not $search}collapse{/if}"><div class="panel-body">
				<form class="form-horizontal" method="get">
					<input type="hidden" name="page" value="bwlist">
					<div class="input-group">
						<span class="input-group-addon"><span class="glyphicon glyphicon-search"></span></span>
						<input type="text" class="form-control" placeholder="Search for..." name="search" value="{$search|escape}">
						<span class="input-group-btn">
							<button class="btn btn-default" type="search">Search</button>
						</span>
					</div>
				</form>
			</div></div>
			<table class="table">
				<thead class="hidden-xs">
					<tr>
						<th class="hidden-xs" style="width: 30px"></th>
						<th class="hidden-xs" style="width: 100px">Type</th>
						<th class="hidden-xs">Sender</th>
						<th class="hidden-xs">For recipient</th>
						<th class="visible-xs"></th>
						<th style="width: 30px"></th>
					</tr>
				</thead>
				<tbody>
				{$id = 0}
				{foreach from=$items key=type item=item}
					{foreach from=$item key=value item=accesses}
						{if count($accesses) > 1}
							{$id = $id + 1}
							<tr style="cursor:pointer" data-toggle="{$id}" class="toggle {if $type=='whitelist'}success{elseif $type=='blacklist'}danger{else}info{/if}">
								<td class="hidden-xs" style="width:30px"><span class="expand-icon glyphicon glyphicon-expand"></span></td>
								<td class="hidden-xs">{$type}</td>
								<td class="hidden-xs">{$value|escape}</td>
								<td class="hidden-xs"><span class="badge">{count($accesses)}</span></td>
								<td class="visible-xs">
									<p>
										<span class="glyphicon glyphicon-pencil"></span>&nbsp; {$value|escape}
									</p>
									<p>
										<span class="glyphicon glyphicon-inbox"></span>&nbsp;
										<span class="badge">{count($accesses)}</span>
									</p>
								</td>
								<td style="width: 30px; vertical-align: middle">
									<a onclick="return confirm('Really delete {$type} {$value|addslashes} for {count($accesses)} recipients?')" title="Remove" href="?page=bwlist&list=delete&access={implode(',', $accesses)|urlencode}&type={$type}&value={$value|urlencode}"><i class="glyphicon glyphicon-remove"></i></a>
								</td>
							</tr>
							{foreach $accesses as $access}
							<tr style="display:none" class="hidden-{$id} {if $type=='whitelist'}success{elseif $type=='blacklist'}danger{else}info{/if}">
								<td class="hidden-xs" style="width:30px"><sup style="opacity:.5">L</sup></td>
								<td class="hidden-xs">{$type}</td>
								<td class="hidden-xs">{$value|escape}</td>
								<td class="hidden-xs">{if $access}{$access|escape}{else}<span class="text-muted">everyone</span>{/if}</td>
								<td class="visible-xs">
									<p>
										<span class="glyphicon glyphicon-pencil"></span>&nbsp; {$value|escape}
									</p>
									<p>
										<span class="glyphicon glyphicon-inbox"></span>&nbsp;
										{if $access}{$access|escape}{else}<span class="text-muted">everyone</span>{/if}
									</p>
								</td>
								<td style="width: 30px; vertical-align: middle">
									<a onclick="return confirm('Really delete {$type} {$value|addslashes} for 1 recipient?')" title="Remove" href="?page=bwlist&list=delete&access={$access|urlencode}&type={$type}&value={$value|urlencode}"><i class="glyphicon glyphicon-remove"></i></a>
								</td>
							</tr>
							{/foreach}
						{else}
							<tr class="{if $type=='whitelist'}success{elseif $type=='blacklist'}danger{else}info{/if}">
								<td class="hidden-xs" style="width:30px"></td>
								<td class="hidden-xs">{$type}</td>
								<td class="hidden-xs">{$value|escape}</td>
								<td class="hidden-xs">{if $accesses.0}{$accesses.0|escape}{else}<span class="text-muted">everyone</span>{/if}</td>
								<td class="visible-xs">
									<p>
										<span class="glyphicon glyphicon-pencil"></span>&nbsp; {$value|escape}
									</p>
									<p>
										<span class="glyphicon glyphicon-inbox"></span>&nbsp;
										{if $accesses.0}{$accesses.0|escape}{else}<span class="text-muted">everyone</span>{/if}
									</p>
								</td>
								<td style="width: 30px; vertical-align: middle">
									<a onclick="return confirm('Really delete {$type} {$value|addslashes} for {count($accesses)} recipients?')" title="Remove" href="?page=bwlist&list=delete&access={$accesses.0|urlencode}&type={$type}&value={$value|urlencode}"><i class="glyphicon glyphicon-remove"></i></a>
								</td>
							</tr>
						{/if}
					{/foreach}
				{foreachelse}
					<tr><td colspan="4" class="text-muted">No black/whitelist</td></tr>
				{/foreach}
				</tbody>
			</table>
		</div>
		<nav>
			{if $total}
			<ul class="pagination">
				{foreach $pages as $p}
					{if $p === '...'}
					<li class="disabled"><a href="#">...</a></li>
					{elseif $p === $currpage}
					<li class="active"><a href="#">{$p+1}</a></li>
					{else}
					<li><a href="?page=bwlist&offset={$limit*$p}&limit={$limit}&search={$search|urlencode}">{$p+1}</a></li>
					{/if}
				{/foreach}
			</ul>
			{else}
			<ul class="pager">
				<li class="previous{if $offset == 0} disabled{/if}"><a href="javascript:history.go(-1);"><span aria-hidden="true">&larr;</span> Previous</a></li>
				<li class="next{if !$pagemore} disabled{/if}"><a href="?page=bwlist&offset={$offset+$limit}&limit={$limit}&search={$search|urlencode}">Next <span aria-hidden="true">&rarr;</span></a></li>
			</ul>
			{/if}
		</nav>
	</div>
	<div class="col-md-6 col-lg-4">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">Add...</h3>
			</div>
			<div class="panel-body">
				<form class="form-horizontal" action="?page=bwlist&list=add" method="post">
					<div class="form-group">
						<label for="type" class="control-label col-md-3">Action</label>
						<div class="col-md-9">
							<select name="type" class="form-control">
								<option value="blacklist">Blacklist</option>
								<option value="whitelist">Whitelist</option>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-md-3">Sender</label>
						<div class="col-md-9">
							<input type="text" class="form-control" name="value">
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-md-3" style="white-space: nowrap;">For recipient</label>
						<div class="col-md-9">
							{if count($useraccess) == 1}
								<input type="hidden" class="form-control" name="access[]" value="{$useraccess.0|escape}">
								<p class="form-control-static">{$useraccess.0|escape}</p>
							{elseif count($useraccess) > 0}
								<button id="check-all" class="btn btn-info">Select all</button>
								<button id="add-access" class="btn btn-default">Add custom</button>
								{if count($useraccess) > 5}<div class="panel panel-default" style="height: 115px; padding-left: 10px; margin-top: 5px; overflow-y: scroll;">{/if}
								<div id="extra-accesses"></div>
								{foreach $useraccess as $a}
								<div class="checkbox">
									<label>
										<input type="checkbox" class="recipient" name="access[]" value="{$a|escape}"> {$a|escape}
									</label>
								</div>
								{/foreach}
								{if count($useraccess) > 5}</div>{/if}
							{else}
								<input type="text" class="form-control" name="access[]" placeholder="everyone">
							{/if}
							<p class="help-block">
								Sender may be an IP address, an e-mail address, a domain name or a wildcard domain name starting with a dot (eg. .co.uk).
							</p>
						</div>
					</div>
					<div class="col-md-offset-3 col-md-9">
						<button type="submit" class="btn btn-primary">Add</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<style>
	table {
		table-layout: fixed;
	}
	td {
		text-overflow: ellipsis;
		white-space: nowrap;
		overflow: hidden;
	}
</style>
{include file='footer.tpl'}
