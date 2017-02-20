$(document).ready(function() {
	$('#level').focus();
	$('#link-add').click(function() {
		$('#btn-cancel').click();
		$('html, body').animate({
			scrollTop: $('#side-panel').offset().top - 100
		}, 0);
	});

	$('.item').click(function() {
		var levels = {Disabled: 1, Low: 2, Medium: 3, High: 4};
		var level = $(this).data('level');

		$(this).closest('tbody').children('tr.active').removeClass('active');
		$(this).closest('tr').addClass('active');

		$('#side-panel').removeClass('panel-default');
		$('#side-panel').addClass('panel-primary');

		$('#action').val('edit');
		$('#edit-id').val($(this).attr('id'));
		$('#edit-recipient').text($(this).closest('tr').children('.item-access').text());
		$('#level>option:eq(' + levels[level] +')').prop('selected', true);
		$('#level').focus();

		$('.hidden-edit').addClass('hidden');
		$('.visible-edit').removeClass('hidden');

		$('html, body').animate({
			scrollTop: $('#side-panel').offset().top - 100
		}, 0);
	});

	$('#btn-cancel').click(function() {
		$('.item.active').removeClass('active');

		$('#side-panel').removeClass('panel-primary');
		$('#side-panel').addClass('panel-default');

		$('#action').val('add');
		$('#edit-id').val(null);
		$('#edit-recipient').text(null);
		$('#level>option:eq("0")').prop('selected', true);

		$('.visible-edit').addClass('hidden');
		$('.hidden-edit').removeClass('hidden');

		$('#level').focus();
	});

	$('#check-all').click(function() {
		$('input.recipient').prop('checked', true);
		return false;
	});

	$('#add-access').click(function() {
		$("#extra-accesses").prepend("<div class='checkbox'><input type='text' class='form-control recipient'></div>");
		return false;
	});

	$('#spam_add').submit(function() {
		var post = {
			"page": "spam",
			"list": $("#action").val(),
			"level": $("#level").val(),
		};

		if ($('#action').val() == 'add')
			post["access"] = $('#spam_add input[type="checkbox"].recipient:checked, #spam_add input[type="text"].recipient, #spam_add input[type="hidden"].recipient').map(function(){return $(this).val();}).get();

		if ($('#action').val() == 'edit')
			post["access"] = [$('#' + $('#edit-id').val()).data('access')];

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

	$(".spam_delete").click(function() {
		var access = $(this).closest('tr').data('access');
		if (!confirm('Delete ' + access + '?'))
			return false;

		$.post("?xhr", {
			"page": "spam",
			"list": "delete",
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
