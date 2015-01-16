$(document).ready(function() {
	poll();
});

$(window).unload(function() {
	// Note: This has to be synchronous, or the unloading will abort it
	$.ajax(document.URL, {
		async: false,
		dataType: "json",
		data: {
			cmd_id: cmd_id,
			cmd_node: cmd_node,
			ajax: 1,
			action: "stop"
		}
	});
});

function poll() {
	$.ajax(document.URL, {
		dataType: "json",
		data: {
			cmd_id: cmd_id,
			cmd_node: cmd_node,
			ajax: 1,
			action: "poll"
		}
	}).done(function(data) {
		$.each(data, function(i, row) {
			$('#log').append(document.createTextNode(row));
		});
		setTimeout(poll, data.length > 0 ? 10 : 1000);
	}).fail(function(req, status, error) {
		var err = "Unknown Error";
		if (status == 'timeout')
			err = "The request timed out";
		else if (status == 'error')
			err = "HTTP Error: " + error;
		else if (status == 'abort')
			err = "Connection aborted";
		else if (status == 'parsererror')
			err = "Couldn't parse response";
		
		$('#log').append('<b class="text-danger">' + err + '</b>');
	});
}
