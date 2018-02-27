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
	$('#search').focus();
	$('[data-bulk-action]').parent('li').addClass('disabled');
	$('[data-bulk-action]').click(function(e) {
		var action = $(this).data('bulk-action');
		var count = $('[name^=multiselect-]:checked').length;

		if (count == 0) return;

		if(confirm("Are you sure you want to " + action + " these " + count + " messages?")) {
			$('#multiform').append('<input type="hidden" name="' + action + '" value="yes">');
			$('#multiform').submit();
		}
		e.preventDefault();
	});

	$('[name^=multiselect-]').click(function() {
		if ($('[name^=multiselect-]:checked').length > 0)
			$('[data-bulk-action]').parent('li').removeClass('disabled');
		else
			$('[data-bulk-action]').parent('li').addClass('disabled');
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
	
	$('td[data-href], tr[data-href] td').wrapInner(function() {
		return '<a class="data-link" href="' + ($(this).data('href') || $(this).parent().data('href')) + '"></a>';
	});

	// Add a select all checkbox on mail listing...
	$('#select-all').change(function() {
		if ($(this).prop('checked'))
			$('[data-bulk-action]').parent('li').removeClass('disabled');
		else
			$('[data-bulk-action]').parent('li').addClass('disabled');

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

	$('#querybuilder').on('show.bs.modal', function () {
		var searchStr = $('#search').val();

		var action = searchStr.match(/action=\b(QUARANTINE|DELIVER|DELETE|REJECT|DEFER|ERROR|BOUNCE|QUEUE)\b/gi);
		if (action != null && action.length == 1)
			$('#query_action').val(action[0].match(/action=(.*)/)[1].toUpperCase());

		var timeFrom = searchStr.match(/time>[0-9]+/g);
		if (timeFrom != null && timeFrom.length == 1)
			$('#query_date_1').val(getSearchDate(timeFrom[0].match(/[0-9]+/) * 1000));

		var timeTo = searchStr.match(/time<[0-9]+/g);
		if (timeTo != null && timeTo.length == 1)
			$('#query_date_2').val(getSearchDate(timeTo[0].match(/[0-9]+/) * 1000));

		var messageId = searchStr.match(/messageid=[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}/gi);
		if (messageId != null && messageId.length == 1) {
			var messageIdStr = messageId[0].match(/messageid[=|~](.*)/);
			if (messageIdStr[1])
				$('#query_mid').val(messageIdStr[1]);
		}

		var from = searchStr.match(/from[=|~][^\s]+/g);
		if (from != null && from.length == 1) {
			var fromStr = from[0].match(/from([=|~])([^\s]+)/);
			if (fromStr[1] && fromStr[2]) {
				$('#query_from_op').val(fromStr[1]);
				$('#query_from').val(fromStr[2]);
			}
		}

		var to = searchStr.match(/to[=|~][^\s]+/g);
		if (to != null && to.length == 1) {
			var toStr = to[0].match(/to([=|~])([^\s]+)/);
			if (toStr[1] && toStr[2]) {
				$('#query_to_op').val(toStr[1]);
				$('#query_to').val(toStr[2]);
			}
		}

		var ip = searchStr.match(/ip[=|~][^\s]+/g);
		if (ip != null && ip.length == 1) {
			var ipStr = ip[0].match(/ip([=|~])([^\s]+)/);
			if (ipStr[1] && ipStr[2]) {
				$('#query_ip_op').val(ipStr[1]);
				$('#query_ip').val(ipStr[2]);
			}
		}

		var username = searchStr.match(/sasl[=|~][^\s]+/g);
		if (username != null && username.length == 1) {
			var usernameStr = username[0].match(/sasl([=|~])([^\s]+)/);
			if (usernameStr[1] && usernameStr[2]) {
				$('#query_sasl_op').val(usernameStr[1]);
				$('#query_sasl').val(usernameStr[2]);
			}
		}

		var subject = searchStr.match(/subject[=|~]".*?"|subject[=|~][^\s]+/g);
		if (subject != null && subject.length == 1) {
			var subjectType = subjectStr = null;
			if (subjectType = subject[0].match(/subject([=|~])"(.*?)"/))
				subjectStr = subjectType;
			else if (subjectType = subject[0].match(/subject([=|~])([^\s]+)/))
				subjectStr = subjectType;
			if (subjectStr[1] && subjectStr[2]) {
				$('#query_subject_op').val(subjectStr[1]);
				$('#query_subject').val(subjectStr[2]);
			}
		}
	});

	$('#query_date_1').click(function() {
		if ($('#query_date_1').val() == '') {
			$('#query_date_1').val(getSearchDate()).change();
		}
	});

	$('#query_date_2').click(function() {
		if ($('#query_date_2').val() == '') {
			$('#query_date_2').val(getSearchDate()).change();
		}
	});

	$('#btn_query_clear').click(function() {
		$('[id^=query_]').each(function() {
			$(this).val('');
		});
		$("#search").val('');
	});
});

function getSearchDate(ts = null) {
	if (ts == null) {
		var currentDate = new Date();
		var hours = '00';
		var minutes = '00';
		var seconds = '00';
	} else {
		var currentDate = new Date(ts);
		var hours = currentDate.getHours();
		hours = (hours < 10) ? '0' + hours : hours;
		var minutes = currentDate.getMinutes();
		minutes = (minutes < 10) ? '0' + minutes : minutes;
		var seconds = currentDate.getSeconds();
		seconds = (seconds < 10) ? '0' + seconds : seconds;
	}
	var year = currentDate.getFullYear();
	var month = currentDate.getMonth()+1;
	month = (month < 10) ? '0' + month : month;
	var day = currentDate.getDate();
	day = (day < 10) ? '0' + day : day;
	return year + '/' + month + '/' + day + ' ' + hours + ':' + minutes + ':' + seconds;
}
