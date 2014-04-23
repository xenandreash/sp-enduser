$(document).ready(function() {
	if (window.history.length == 1)
		$(".back").parent().hide();
	poll();
});

$(window).unload(function() {
	$.ajax({
		type: "GET",
		async: false,
		url: document.URL,
		dataType: "json",
		data: {
			cmd_id: cmd_id,
			ajax: 1,
			action: "stop"
		}
	});
});

function poll() {
	$.ajax({
		type: "GET",
		url: document.URL,
		dataType: "json",
		data: {
			cmd_id: cmd_id,
			ajax: 1,
			action: "poll"
		},
		success: function(result) {
			$.each(result, function(k, v) {
				$("#log").append(document.createTextNode(v));
			});
			setTimeout(function () { poll(); }, result.length ? 10 : 1000);
		},
		error: function(jqXHR, textStatus, errorThrown) {
			if (textStatus == 'error' && jqXHR.responseText.length)
				$("#log").append("<b>Error: " + jqXHR.responseText + "</b>");
		}
	});
}
