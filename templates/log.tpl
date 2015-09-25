{include file='header.tpl' title='Text log' show_back=true}
<nav class="navbar navbar-toolbar navbar-static-top hidden-xs">
	<div class="container-fluid">
		<div class="navbar-header">
			<a id="history_back" class="navbar-brand" href="javascript:history.go(-1);">&larr;&nbsp;Back</a>
		</div>
	</div>
</nav>
<div class="container-fluid">
	<pre id="log"><span class="text-info" id="loading">Loading<span class="dot">.</span><span class="dot">.</span><span class="dot">.</span></span></pre>
</div>
<script>
	cmd_id = {$id};
	cmd_node = {$node};
</script>
{include file='footer.tpl'}
