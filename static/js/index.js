$(document).ready(function() {
	$('[data-bulk-action]').click(function(e) {
		var action = $(this).data('bulk-action');
		var count = $('[name^=multiselect-]:checked').length;
		if(confirm("Are you sure you want to " + action + " these " + count + " messages?")) {
			$('#multiform').append('<input type="hidden" name="' + action + '" value="yes">');
			$('#multiform').submit();
		}
		e.preventDefault();
	});
	
	$('td [data-action]').click(function(e) {
		var action = $(this).data('action');
		if(confirm("Really " + action + " message?")) {
			$('[name^=multiselect-]').prop('checked', false);
			$(this).closest("tr").find("input").prop('checked', true);
			$("#multiform").append('<input type="hidden" name="' + action + '" value="yes">');
			$("#multiform").submit();
		}
	});
	
	// Make the checkboxes show/hide the action bar
	$('[name^=multiselect-]').change(function() {
		var count = $('[name^=multiselect-]:checked').length;
		$('#bottom-bar').toggle(count > 0);
		$('body').toggleClass('has-bottom-bar', count > 0);
	});
	
	$('td[data-href], tr[data-href] td').wrapInner(function() {
		return '<a class="data-link" href="' + ($(this).data('href') || $(this).parent().data('href')) + '"></a>';
	});
	
	// This is for some reason needed to get the source list dropdown to
	// work on iOS, I don't have the faintest idea why...
	$('.dropdown-toggle').click(function() { });
});
