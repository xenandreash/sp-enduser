$(document).ready(function() {
	$('#value').focus();
	
	$('#link-add').click(function() {
		$('#btn-cancel').click();
		$('html, body').animate({
			scrollTop: $('#side-panel').offset().top - 100
		}, 0);
	});

	$('.item').click(function() {
		$('#btn-cancel').click();

		$(this).closest('tr').removeClass('info');
		$(this).closest('tr').addClass('active');

		$('#side-panel').removeClass('panel-default');
		$('#side-panel').addClass('panel-primary');

		$('#edit-id').val($(this).closest('tr').attr('id'));
		$('#btn-submit').html(button_edit);

		if ($(this).hasClass('edit-user')) {
			$('#action').val('edit-user');
			$('#value').val($(this).closest('tr').data('value'));
			$('#side-panel-title').html(title_edit_user + ' (' + $('#value').val() + ')');

			$('.hidden-edit-user').addClass('hidden');
			$('.visible-edit-user').removeClass('hidden');

			if ($(this).closest('tr').data('access')) {
				var access_count = $(this).closest('tr').data('access').split(',').length;
				$('#user-description').html(access_count + ' ' + description_restricted_access);
			} else {
				$('#user-description').html(description_full_access);
				$('#btn-access').addClass('hidden');
			}

			$('#value').focus();
		}

		if ($(this).hasClass('edit-access')) {
			$('#action').val('edit-access');
			$('#access').val($(this).closest('tr').data('access'));
			$('#side-panel-title').html(title_edit_permission + ' (' + $('#access').val() + ')');
			$('#value-static').text($(this).closest('tr').children('.item-value').text());

			$('.hidden-edit-access').addClass('hidden');
			$('.visible-edit-access').removeClass('hidden');

			$('#access').focus();
		}

		$('html, body').animate({
			scrollTop: $('#side-panel').offset().top - 100
		}, 0);
	});
	
	$('.toggle').click(function() {
		$('.hidden-' + $(this).closest('tr').data("toggle")).toggle();
		var icon = $(this).closest('tr').find(".expand-icon");
		if (icon.hasClass('fa-expand')) {
			icon.addClass('fa-compress').removeClass('fa-expand');
		} else {
			icon.addClass('fa-expand').removeClass('fa-compress');
		}
	});

	$('#btn-pwd').click(function() {
		$(this).addClass('hidden');
		$('#password-1, #repeat-password-group').removeClass('hidden');
	});

	$('#full-access:checkbox').change(function() {
		if ($(this).is(':checked')) {
			$('#btn-access').addClass('hidden');
			$('#btn-clear').click();
			$('#btn-submit').removeClass('disabled');
		}
		if (!$(this).is(':checked')) {
			$('#btn-access').removeClass('hidden');
			$('#btn-access').click();
		}
	}); 

	$('#btn-access').click(function() {
		$('#btn-clear').removeClass('hidden');
		$('#extra-accesses').append('<div style="padding-top: 7px"><input type="text" class="form-control accesses" placeholder="' + placeholder_access + '"></div>');
		$('#btn-submit').removeClass('disabled');
	});

	$('#btn-clear').click(function() {
		$(this).addClass('hidden');
		$('#extra-accesses').empty();
		if ($('#action').val() === 'add-user') {
			$('#btn-submit').addClass('disabled');
		}
	});

	$('#btn-cancel').click(function() {
		$('#' + $('#edit-id').val()).removeClass('active');
		if ($('#' + $('#edit-id').val()).hasClass('item-hidden')) {
			$('#' + $('#edit-id').val()).addClass('info')
		}

		$('#side-panel').removeClass('panel-primary');
		$('#side-panel').addClass('panel-default');

		$('#side-panel-title').html(title_add_user);
		$('#action').val('add-user');
		$('#value, #access, #edit-id, #password-1, #password-2').val(null);
		$('#value, #password-1, #password-2').addClass('hidden').removeClass('hidden') // Fix to display placeholder in Safari
		$('#value-static').text(null);
		$('#btn-clear').click();
		$('#full-access').prop('checked', true);
		$('#btn-submit').html(button_add);
		$('#btn-submit').removeClass('disabled');

		$('.visible-edit-user').addClass('hidden');
		$('.hidden-edit-user').removeClass('hidden');
		$('.visible-edit-access').addClass('hidden');
		$('.hidden-edit-access').removeClass('hidden');

		$('#value').focus();
	});

	$('#item-form').submit(function() {
		if ($('#btn-submit').hasClass('disabled'))
			return false;

		var post = {
			"page": "users",
			"list": $("#action").val(),
		};
		if ($('#action').val() == 'add-user' || $('#action').val() == 'edit-user') {
			post["username"] = $('#value').val();
			post["access"] = $('#item-form .accesses').map(function(){return $(this).val();}).get();
			post["password_1"] = $('#password-1').val();
			post["password_2"] = $('#password-2').val();
			if ($('#action').val() == 'edit-user') {
				post["old_username"] = $('#' + $('#edit-id').val()).data('value');
			}
		} else if ($('#action').val() == 'edit-access') {
			post["username"] = $('#' + $('#edit-id').val()).data('value');
			post["access"] = $('#access').val();
			post["old_access"] = $('#' + $('#edit-id').val()).data('access');
		}

		$.post('?xhr', post, function(data) {
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

	$('.item-delete').click(function() {
		var username = $(this).closest('tr').data('value');
		var post = {
			"page": "users",
			"list": "delete",
			"username": username
		};
		if ($(this).closest('tr').hasClass('edit-user') || $(this).closest('tr').children('td').hasClass('edit-user')) {
			post["type"] = "user";
			if (!confirm('Delete user "' + username + '"?')) {
				return false;
			}
		} else if ($(this).closest('tr').hasClass('edit-access')) {
			var access = $(this).closest('tr').data('access');
			post["type"] = "access";
			post["access"] = access;
			if (!confirm('Delete access "' + access + '" for user "' + username + '"?')) {
				return false;
			}
		}

		$.post('?xhr', post, function(data) {
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
