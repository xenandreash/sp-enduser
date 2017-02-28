$(document).ready(function() {
	$('[data-action]').click(function(e) {
		var action = $(this).data('action');
		if(action == "whitelist" || action == "blacklist") {
			if (confirm("Are you sure you want to " + action + " this sender?")) {
				if ($('#bwlist-from').val() != "") {
					var post = {
						"page": "bwlist",
						"list": "add",
						"value": $("#bwlist-from").val(),
						"type": action,
						"access": [$("#bwlist-to").val()]
					};

					$.post("?xhr", post, function(data) {
						if (data.error) {
							if (data.error == 'syntax')
								alert('Syntax error on field ' + data.field + ': ' + data.reason);
							if (data.error == 'permission')
								alert('No permission for ' + data.value);
							return;
						}
						window.location.reload();
					}).fail(function(jqXHR, textStatus, errorThrown) {
						alert('Error: ' + errorThrown);
					});
				}
			}
		} else {
			if (confirm("Are you sure you want to " + action + " this message?")) {
				$('#action').val(action);
				$('#actionform').submit();
			}
		}
	});

	// Hide back button if page was opened in a new tab
	if (window.history.length == 1)
		$('#history_back').hide();
});
