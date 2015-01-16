$(document).ready(function() {
	$('[data-action]').click(function(e) {
		var action = $(this).data('action');
		if (confirm("Are you sure you want to " + action + " this message?")) {
			$('#action').val(action);
			$('#actionform').submit();
		}
	});
});
