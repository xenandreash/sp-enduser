{include file='header.tpl' title='Integration'}
<div class="container">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">{t}Halon node integration{/t}</h3>
		</div>
		<div class="panel-body">
			<div class="dropdown  pull-right">
				<button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownIntegrationMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					{t}Scripts{/t}
					<span class="caret"></span>
				</button>
				<ul class="dropdown-menu" aria-labelledby="dropdownIntegrationMenu">
					<li><a href="?page={$page_active}&script=api">{t}API authentication{/t}</a></li>
					<li role="separator" class="divider"></li>
					<li><a href="?page={$page_active}&script=history">{t}History log{/t}</a></li>
					<li role="separator" class="divider"></li>
					<li><a href="?page={$page_active}&script=bwlist">{t}Blacklist and whitelist{/t}</a></li>
					<li><a href="?page={$page_active}&script=datastore">{t}Data store settings{/t}</a></li>
					<li><a href="?page={$page_active}&script=spam">{t}Spam settings{/t}</a></li>
					<li role="separator" class="divider"></li>
					<li><a href="?page={$page_active}&script=usercreation">{t}Automatic user creation{/t}</a></li>
				</ul>
			</div>
			{if $show_script == "bwlist"}
				<h4>{t}Blacklist and whitelist{/t}</h4>
				<p>{t}Used to lookup against the blacklist and whitelist on the Enduser (with a cache). The function should be called before spam checks.{/t}</p>
				{if !$feature_bwlist}<div class="alert alert-warning" role="alert"><i class="fa fa-exclamation-triangle"></i>&nbsp;{t}This feature is not enabled under the settings.php file!{/t}</div>{/if}
				<div class="pre-header">Data context</div>
				<pre class="pre-body">{include file='scripts/hsl_bwlist.tpl'}</pre>
				<h4 style="margin-top: 20px">{t}ScanBWList with per-user check{/t}</h4>
				<p>{t}This implementation is suboptimal as it queries each $sender and $recipient for the black/white list. It's not necessary to cache these as cache hits would be close to none.{/t}</p>
				<div class="pre-header">Data context</div>
				<pre class="pre-body">{include file='scripts/hsl_bwlist_peruser.tpl'}</pre>
			{else if $show_script == "spam"}
				<h4>{t}Spam settings{/t}</h4>
				<p>{t}If you want to fetch the spam settings from the End-user interface.{/t}</p>
				{if !$feature_spam}<div class="alert alert-warning" role="alert"><i class="fa fa-exclamation-triangle"></i>&nbsp;{t}This feature is not enabled under the settings.php file!{/t}</div>{/if}
				<div class="pre-header">Data context</div>
				<pre class="pre-body">{include file='scripts/hsl_spam.tpl'}</pre>
			{else if $show_script == "datastore"}
				<h4>{t}Data store settings{/t}</h4>
				<p>{t}If you want to fetch the datastore settings from the End-user interface.{/t}</p>
				{if !$feature_datastore}<div class="alert alert-warning" role="alert"><i class="fa fa-exclamation-triangle"></i>&nbsp;{t}This feature is not enabled under the settings.php file!{/t}</div>{/if}
				<div class="pre-header">Data context</div>
				<pre class="pre-body">{include file='scripts/hsl_datastore.tpl'}</pre>
			{else if $show_script == "history"}
				<h4>{t}History log{/t}</h4>
				<p>{t}There's a limit to how many messages can stored in a Halon database, for performance reasons. In order to store large volumes of email history we encourage the use of the end user interface's built-in history log feature. Simply append the following script to the Halon nodes' DATA flow, to push logging information to the End-user.{/t}</p>
				{if !$feature_dblog}<div class="alert alert-warning" role="alert"><i class="fa fa-exclamation-triangle"></i>&nbsp;{t}This feature is not enabled under the settings.php file!{/t}</div>{/if}
				<div class="pre-header">Data context</div>
				<pre class="pre-body">{include file='scripts/hsl_logging.tpl'}</pre>
				<p>{t}The code above could be placed in a virtual text file, and included in the top of the script. Please note that the "direction" field should probably be changed to "outbound" instead of "inbound" for outbound traffic. For delivery status updates, the following script should be called from (again, preferably by including code from a virtual text file) in the Post-delivery flow.{/t}</p>
				<div class="pre-header">Post-delivery context</div>
				<pre class="pre-body">{include file='scripts/hsl_loggingpost.tpl'}</pre>
				<h4 style="margin-top: 20px">{t}Removing old logs{/t}</h4>
				<p>{t}When using the history log feature you should also edit the crontab file to periodically remove old logs from the database. You do that by typing crontab -e in the terminal and add the following line at the bottom:{/t}</p>
				<pre>0 * * * * /usr/bin/php /var/www/html/sp-enduser/cron.php.txt cleanmessagelog</pre>
			{else if $show_script == "usercreation"}
				<h4>{t}Automatic user creation{/t}</h4>
				<p>{t}If you want users to be automatically created when a message is received, add the following script to your data flow.{/t}</p>
				{if !$feature_users}<div class="alert alert-warning" role="alert"><i class="fa fa-exclamation-triangle"></i>&nbsp;{t}This feature is not enabled under the settings.php file!{/t}</div>{/if}
				<div class="pre-header">Data context</div>
				<pre class="pre-body">{include file='scripts/hsl_usercreation.tpl'}</pre>
			{else}
				<h4>{t}API authentication script{/t}</h4>
				<p>{t}This is a sample authentication script (API script) with all currently enabled features, to be used on your Halon node.{/t}</p>
				<div class="pre-header">API context</div>
				<pre class="pre-body">{$hsl_script}</pre>
			{/if}
		</div>
	</div>
</div>
{include file='footer.tpl'}
