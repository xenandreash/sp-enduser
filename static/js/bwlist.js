$(document).ready(function() {
	$('#type').focus();
	$('[data-toggle="popover"]').popover({
		delay: {
			show: "200",
			hide: "100"
		 }
	});

	$('#link-add').click(function() {
		$('#btn-cancel').click();
		$('html, body').animate({
			scrollTop: $('#side-panel').offset().top - 100
		}, 0);
	});

	$('#enable-comment').click(function() {
		if ($(this).prop('checked'))
			$('#comment').focus();
	});

	$('#enable-comment').change(function() {
		if ($(this).prop('checked')) {
			if ($('#comment').val()) {
				$('#comment-group').hide();
				$('#comment').appendTo('#comment-field');
			}
			$('#comment').prop('disabled', false);
			if (!$('#comment').val())
				$('#comment').prop('placeholder', $('#comment-default').val());
		} else {
			$('#comment-group').show();
			$('#comment').appendTo('#comment-group');
			$('#comment').prop('disabled', true);
			$('#comment').prop('placeholder', '');
		}
	});

	$('.item').click(function() {
		var types = {blacklist: 0, whitelist: 1};
		var type = $(this).data('type');

		$('.item-hidden').addClass('info');
		$(this).closest('tr').removeClass('info');
		$(this).closest('tbody').children('tr.active').removeClass('active');
		$(this).closest('tr').addClass('active');

		$('#side-panel').removeClass('panel-default');
		$('#side-panel').addClass('panel-primary');

		$('#action').val('edit');
		$('#value').val($(this).data('value')).focus();
		$('#comment').val($(this).data('comment'));
		if ($(this).data('comment'))
			$('#enable-comment').prop('checked', true).change();
		else
			$('#enable-comment').prop('checked', false).change();
		$('#edit-id').val($(this).attr('id'));
		$('#edit-recipient').text($(this).closest('tr').children('.item-access').text());
		$('#type>option:eq(' + (types[type] + 1) +')').prop('selected', true);

		$('.hidden-edit').addClass('hidden');
		$('.visible-edit').removeClass('hidden');

		$('html, body').animate({
			scrollTop: $('#side-panel').offset().top - 100
		}, 0);
	});

	$('#btn-cancel').click(function() {
		$('.item.active').removeClass('active');
		$('.item-hidden').addClass('info');

		$('#side-panel').removeClass('panel-primary');
		$('#side-panel').addClass('panel-default');

		$('#action').val('add');
		$('#value').val(null);
		$('#comment').val(null);
		$('#enable-comment').prop('checked', false).change();
		$('#edit-id').val(null);
		$('#edit-recipient').text(null);
		$('#type>option:eq("0")').prop('selected', true);

		$('.visible-edit').addClass('hidden');
		$('.hidden-edit').removeClass('hidden');

		$('#type').focus();
	});

	$('#check-all').click(function() {
		$('input.recipient').prop('checked', true);
		return false;
	});

	$('#add-access').click(function() {
		$("#extra-accesses").prepend("<div class='checkbox'><input type='text' class='form-control recipient'></div>");
		return false;
	});

	$(".toggle").click(function() {
		$(".hidden-" + $(this).data("toggle")).toggle();
		var icon = $(this).find(".expand-icon");
		if (icon.hasClass('fa-expand'))
			icon.addClass('fa-compress').removeClass('fa-expand');
		else
			icon.addClass('fa-expand').removeClass('fa-compress');
	});

	$('#bwlist_add').submit(function() {
		var post = {
			"page": "bwlist",
			"list": $("#action").val(),
			"value": $("#value").val(),
			"type": $("#type").val()
		};

		if ($('#enable-comment').prop('checked')) {
			if (!$("#comment").val())
				post["comment"] = $("#comment").attr("placeholder");
			else
				post["comment"] = $("#comment").val();
		}

		if ($('#action').val() == 'add')
			post["access"] = $('#bwlist_add input[type="checkbox"].recipient:checked, #bwlist_add input[type="text"].recipient, #bwlist_add input[type="hidden"].recipient').map(function(){return $(this).val();}).get();

		if ($('#action').val() == 'edit') {
			post["access"] = [$('#' + $('#edit-id').val()).data('access')];
			post["old_value"] = $('#' + $('#edit-id').val()).data('value');
			post["old_type"] = $('#' + $('#edit-id').val()).data('type');
		}

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
		return false;
	});

	$(".bwlist_delete").click(function() {
		var value = $(this).closest('tr').data('value');
		var type = $(this).closest('tr').data('type');
		var access = $(this).closest('tr').data('access');
		if (!confirm('Delete ' + type + ' item "' + value + '" for: ' + access + '?'))
			return false;

		$.post("?xhr", {
			"page": "bwlist",
			"list": "delete",
			"value": value,
			"type": type,
			"access": access
		}, function(data) {
			if (data.error) {
				if (data.error == 'permission')
					alert('No permission for ' + data.value);
				return;
			}
			window.location.reload();
		}).fail(function(jqXHR, textStatus, errorThrown) {
			alert('Error: ' + errorThrown);
		});
		return false;
	});
});
