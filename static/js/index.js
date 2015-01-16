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
	
	// Make the checkboxes show/hide the action bar
	$('[name^=multiselect-]').change(function() {
		var count = $('[name^=multiselect-]:checked').length;
		$('#bottom-bar').toggle(count > 0);
		$('body').toggleClass('has-bottom-bar', count > 0);
	});
	
	// Use `data-href` to make the unclickable clickable (eg. table rows)
	$('[data-href]').click(function() {
		window.location.href = $(this).data('href');
	});
	
	// This is for some reason needed to get the source list dropdown to
	// work on iOS, I don't have the faintest idea why...
	$('.dropdown-toggle').click(function() { });
});
