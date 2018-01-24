$(document).ready(function() {
	$('.item').click(function() {
		$(this).closest('tbody').children('tr.active').removeClass('active');
		$(this).closest('tr').addClass('active');

		$('#side-panel').removeClass('panel-default');
		$('#side-panel').addClass('panel-primary');

		$('#display-username').text($(this).data('value'));
		$('#username').val($(this).data('value'));

		$('.hidden-edit').addClass('hidden');
		$('.visible-edit').removeClass('hidden');

		$('#btn-remove').prop('disabled', false);
		$('#btn-cancel').prop('disabled', false);

		$('html, body').animate({
			scrollTop: $('#side-panel').offset().top - 50
		}, 0);
	});

	$('#btn-cancel').click(function() {
		$('.item.active').removeClass('active');

		$('#side-panel').addClass('panel-default');
		$('#side-panel').removeClass('panel-primary');

		$('.hidden-edit').removeClass('hidden');
		$('.visible-edit').addClass('hidden');

		$('#btn-remove').prop('disabled', true);
		$('#btn-cancel').prop('disabled', true);
	});

	$('#item-form').submit(function() {
		var username = $('#username').val();
		var action = $('#action').val();

		if (!confirm('Delete two-factor authentication for ' + username + '?'))
			return false;

		var post = {
			"page": "totp",
			"list": action,
			"username": username
		};

		$.post('?xhr', post, function(data) {
			if (data.error) {
				alert(data.error);
				return;
			}
			window.location.reload();
		}).fail(function(jqXHR, textStatus, errorThrown) {
			alert('Error: ' + errorThrown);
		});
		return false;
	});
});