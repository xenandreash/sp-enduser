{capture assign="title"}{t}Statistics{/t}{/capture}
{include file='header.tpl' title=$title page_active='stats'}
<div class="container" id="panel-container">
	<div class="btn-group">
		<button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
			{t}Add...{/t} <span class="caret"></span>
		</button>
		<ul class="dropdown-menu" role="menu">
			<li><a href="#" class="add-all">{t}All{/t}</a></li>
			<li class="divider"></li>
			{foreach $domains.inbound as $domain}
				<li><a href="#" data-direction="inbound" data-domain="{$domain}" class="add-domain">{$domain} (inbound)</a></li>
			{/foreach}
			{foreach $domains.outbound as $domain}
				<li><a href="#" data-direction="outbound" data-domain="{$domain}" class="add-domain">{$domain} (outbound)</a></li>
			{/foreach}
		</ul>
	</div>
	{if count($domains) > 5}
	<span class="text-muted pull-right many-domains">
		{t}Because you have more than 5 domains, you need to choose them specifically.{/t}
	</span>
	{/if}
	<br><br>
	<div class="panel panel-default template" style="display:none">
		<div class="panel-heading">
			<h3 class="panel-title">
				<span></span>
				<a class="pull-right stat-close" href="#"><span class="fa fa-remove"></span></a>
			</h3>
		</div>
		<div class="panel-body draw-charts">
			<div class="row"><div class="col-md-6">
				<div class="rrd-id" style="height:200px;display:none;"></div>
				<div class="realrrd" style="height:200px">{t}Loading...{/t}</div>
			</div><div class="col-md-6">
				<div class="pie" style="height:200px">{t}Loading...{/t}</div>
				<div style="position: absolute; z-index: 10; right: 20px">
					<div class="since text-muted" style="margin-top: -50px; right: 40px;"></div>
				</div>
			</div></div>
		</div>
	</div>
</div>
{include file='footer.tpl'}
