function datetime_to_obj(d) {
	// according to http://stackoverflow.com/questions/24703698/html-input-type-datetime-local-setting-the-wrong-time-zone
	d = d.replace(/-/g, "/");
	d = d.replace("T", " ");
	if (d.split(":").length < 3)
		d += ":59";
	var now = d.split(".");
	if (now.length > 1)
		d = now[0];
	return Date.parse(d);
}

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

	// Add a select all checkbox on mail listing...
	$('#select-all').change(function() {
		$(this).closest('table').find('tbody').find('input[type=checkbox]').prop('checked', $(this).prop('checked'));
	});
	if ($('#select-all').closest('table').find('tbody').find('input[type=checkbox]').length == 0)
		$('#select-all').hide();
	
	// This is for some reason needed to get the source list dropdown to
	// work on iOS, I don't have the faintest idea why...
	$('.dropdown-toggle').click(function() { });

	$("#search_domain li a").on("click", function(event) {
		event.preventDefault();
		$("#search").val($("#search").val() + " to~%@" + $(this).text());
		$('#dosearch').click();
	});

	$("#query input, #query select").on("change keyup", function() {
		var search = [];
		if ($("#query_date_1").val() || $("#query_time_1").val())
		{
			var d = $("#query_date_1").val();
			search.push("time>" + datetime_to_obj(d) / 1000);
		}

		if ($("#query_date_2").val() || $("#query_time_2").val())
		{
			var d = $("#query_date_2").val();
			search.push("time<" + datetime_to_obj(d) / 1000);
		}

		if ($("#query_mid").val())
			search.push("messageid=" + $("#query_mid").val());

		if ($("#query_qid").val())
			search.push("queueid=" + $("#query_qid").val());

		if ($("#query_from").val())
			search.push("from" + $("#query_from_op").val() + $("#query_from").val());

		if ($("#query_to").val())
			search.push("to" + $("#query_to_op").val() + $("#query_to").val());

		if ($("#query_ip").val())
			search.push("ip" + $("#query_ip_op").val() + $("#query_ip").val());

		if ($("#query_sasl").val())
			search.push("sasl" + $("#query_sasl_op").val() + $("#query_sasl").val());

		if ($("#query_subject").val())
			search.push("subject" + $("#query_subject_op").val() + '"' + $("#query_subject").val() + '"');

		if ($("#query_action").val())
			search.push("action=" + $("#query_action").val());

		if ($("#query_mailserver").val())
			search.push("server=" + $("#query_mailserver").val());

		if ($("#query_mailtransport").val())
			search.push("transport=" + $("#query_mailtransport").val());

		$("#search").val(search.join(' '));
	});
});
