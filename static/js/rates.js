$(document).ready(function() {
	$(".rate_clear").click(function() {
		var ns = $(this).data('ns');
		var entry = $(this).data('entry');
		if (!confirm('Clear rate limit for ' + entry + '?'))
			return false;

		$.post("?xhr", {
			"page": "rates",
			"list": "clear",
			"ns": ns,
			"entry": entry
		}, function(data) {
			if (data.error) {
				if (data.error == 'soap')
					alert('SOAP error: ' + data.value);
				return;
			}
			window.location.reload();
		}).fail(function(jqXHR, textStatus, errorThrown) {
			alert('Error: ' + errorThrown);
		});
		return false;
	});
});
