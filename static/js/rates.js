$(document).ready(function() {
	$('#search').focus();
	$("#search_form").submit(function() {
		search = $("#search").val();
		$.each(views, function (index, view) {
			view.paging = 0;
			loadRateTable(view.id);
		});
		return false;
	});

	$.each(views, function (index, view) {
		loadRateTable(view.id);
	});
});

function loadRateTable(id)
{
	if (views[id].timer)
		clearTimeout(views[id].timer);
	views[id].timer = undefined;

	$.post("?xhr", {
		"page": "rates",
		"rate": id,
		"search": search,
		"paging": views[id].paging,
	}, function (data) {
		populateRateTable(id, data);
		views[id].timer = setTimeout(function () {
			loadRateTable(id);
		}, reloadTimeout * 1000);
	}).fail(function(jqXHR, textStatus, errorThrown) {
		var tbody = $("#rate_" + id + " tbody");
		tbody.empty();
		tbody.append($('<tr>').append($('<td>').attr('colspan', 6).addClass('text-muted').text('Error: ' + jqXHR.responseText)));
	});
}

function populateRateTable(id, data)
{
	var tbody = $("#rate_" + id + " tbody");
	tbody.empty();

	var tfoot = $("#rate_" + id + " tfoot");
	tfoot.empty();

	if (data.error) {
		tbody.append($('<tr>').append($('<td>').attr('colspan', 6).addClass('text-muted').text('Error: ' + data.error)));
		return;
	}

	if (data.items.length == 0) {
		if (data.page_start > 0) {
			views[id].paging = data.page_start - data.page_limit;
			loadRateTable(id);
			return;
		}
		tbody.append($('<tr>').append($('<td>').attr('colspan', 6).addClass('text-muted').text(text_nomatch)));
		return;
	}

	$.each(data.items, function (index, item) {
		var tr = $('<tr>');
		if (data.count_limit && item.count >= data.count_limit) {
			var icon = $('<span>')
							.addClass('fa-stack')
							.css('font-size', '12px')
							.append(
								$('<i>').addClass('fa fa-square fa-stack-2x').css('color', data.action_color)
							)
							.append(
								$('<i>').addClass('fa fa-lg fa-' + data.action_icon + ' fa-stack-1x').css('color', '#fff')
							);
			if (item.search_filter)
				tr.append(
					$('<td>').addClass('nopad').append($('<a>').attr('href', '?source=' + source + '&search=' + item.search_filter + (data.action_type?'+action%3D' + data.action_type:'')).append(icon))
				);
			else
				tr.append(
					$('<td>').addClass('nopad').append(icon)
				);
		} else
			tr.append($('<td>').addClass('nopad'));
		if (item.search_filter)
			tr.append(
				$('<td>')
					.attr('colspan', 3)
					.append(
						$('<a>')
							.attr('href', '?source=' + source + '&search=' + item.search_filter)
							.text(item.entry == '' ? '(Empty)' : item.entry)
					)
			);
		else
			tr.append(
				$('<td>')
					.attr('colspan', 3)
					.text(item.entry == '' ? '(Empty)' : item.entry)
			);
		if (item.entry == '')
			tr.find("td").css('font-style', 'italic');
		tr.append(
			$('<td>')
				.text(item.count)
		);
		tr.append(
			$('<td>')
				.css('vertical-align', 'middle')
				.append(
					$('<a>')
						.data('entry', item.entry)
						.data('ns', item.ns)
						.click(rateDelete)
						.attr('title', text_clear)
						.attr('href', '#')
						.append(
							$('<li>')
								.addClass('fa fa-remove')
						)
				)
		);
		tbody.append(tr);
	});

	var nav = $("<nav>").append(
			$("<ul>")
			.addClass("pager")
			.append(
					$("<li>")
					.addClass("previous" + (data.page_start == 0?' disabled':''))
					.append(
						$("<a>").attr('href', '#').text(text_previous).click(function() {
							views[id].paging = data.page_start - data.page_limit;
							loadRateTable(id);
							return false;
						})
					)
			)
			.append(
					$("<li>")
					.addClass("next" + (data.page_start + data.items.length == data.items_count?' disabled':''))
					.append(
						$("<a>").attr('href', '#').text(text_next).click(function() {
							views[id].paging = data.page_start + data.items.length;
							loadRateTable(id);
							return false;
						})
					)
			)
			.append(
					$("<span>").addClass('text-muted').text((data.page_start + 1) + '-' + (data.page_start + data.items.length) + ' (' + data.items_count + ')')
			)
	);
	tfoot.append($('<tr>').append($('<td>').attr('colspan', 6).append(nav)));
}

function rateDelete()
{
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
		$.each(views, function (index, view) {
				if (view.ns == ns)
					loadRateTable(view.id);
			});
	}).fail(function(jqXHR, textStatus, errorThrown) {
		alert('Error: ' + errorThrown);
	});

	return false;
}
