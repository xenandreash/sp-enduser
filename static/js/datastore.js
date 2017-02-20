$(document).ready(function() {
	$('#namespace').focus();
	$('#link-add').click(function() {
		$('#btn-cancel').click();
		$('html, body').animate({
			scrollTop: $('#side-panel').offset().top - 100
		}, 0);
	});

	$('.item').click(function() {
		$(this).closest('tbody').children('tr.active').removeClass('active');
		$(this).closest('tr').addClass('active');

		$('#side-panel').removeClass('panel-default');
		$('#side-panel').addClass('panel-primary');

		$('#action').val('edit');
		$('#value').val($(this).data('value')).focus();
		$('#edit-id').val($(this).attr('id'));
		$('#edit-namespace').text($(this).closest('tr').children('.item-namespace').text());
		$('#edit-key').text($(this).closest('tr').children('.item-key').text());

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
		$('#value').val(null);
		$('#edit-id').val(null);
		$('#edit-recipient').text(null);

		$('.visible-edit').addClass('hidden');
		$('.hidden-edit').removeClass('hidden');

		$('#namespace').focus();
	});

	$('#item-form').submit(function() {
		var post = {
			"page": "datastore",
			"list": $("#action").val(),
			"value": $("#value").val(),
		};

		if ($('#action').val() == 'add') {
			post["namespace"] = $('#namespace').val();
			post["key"] = $('#key').val();
		}

		if ($('#action').val() == 'edit') {
			post["namespace"] = $('#' + $('#edit-id').val()).data('namespace');
			post["key"] = $('#' + $('#edit-id').val()).data('key');
		}

		$.post('?xhr', post, function(data) {
			if (data.error) {
				alert('Error: ' + data.error);
				return false;
			}
			window.location.reload();
		}).fail(function(jqXHR, textStatus, errorThrown) {
			alert('Error: ' + errorThrown);
		})
		return false;
	});

	$('.item-delete').click(function() {
		var namespace = $(this).closest('tr').data('namespace');
		var key = $(this).closest('tr').data('key');
		if (!confirm('Delete key ' + key + ' in namespace ' + namespace + '?'))
			return false;

		$.post('?xhr', {
			"page": "datastore",
			"list": "delete",
			"namespace": namespace,
			"key": key
		}, function(data) {
			if (data.error) {
				alert('Error: ' + data.error);
				return;
			}
			window.location.reload();
		}).fail(function(jqXHR, textStatus, errorThrown) {
			alert('Error: ' + errorThrown);
		});
		return false;
	});
});
