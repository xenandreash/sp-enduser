{include file='header.tpl' title='Text log'}
{if isset($preview_query)}
<nav class="navbar navbar-default navbar-toolbar navbar-static-top hidden-xs">
	<div class="container-fluid">
		<ul class="nav navbar-nav">
			<li>
				<a href="?{$preview_query|escape}"><i class="fa fa-arrow-circle-o-left"></i>&nbsp;{t}Back to preview{/t}</a>
			</li>
		</ul>
	</div>
</nav>
{/if}

<div class="container-fluid">
	<div class="pre-header">{t}Text log{/t}</div>
	<pre class="pre-body" id="log"><span class="text-info" id="loading">Loading<span class="dot">.</span><span class="dot">.</span><span class="dot">.</span></span></pre>
</div>
<script>
	cmd_id = {$id};
	cmd_node = {$node};
</script>
{include file='footer.tpl'}
