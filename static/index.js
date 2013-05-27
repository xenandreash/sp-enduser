$(document).ready(function() {
	$('.tracking-actions').each(function() {
		var b = $(this);
		var f = function() {
			var action = $(this).data("action");
			if (action == "deletebulk")
			{
				if (confirm("This action will delete all messages based on the current search, continue?")) {
					if (!confirm("Are you sure you want to delete all these messages?"))
						return;
				} else
					return;
			} else {
				var sel = $('[name^=multiselect-]:checked').length;
				if (action != "retryall" && sel == 0)
					return alert("No messages selected");
				if (action == "delete" || action == "bounce")
					if (!confirm("Really " + action + " the " + sel + " selected messages?"))
						return;
			}
			$("#multiform").append("<input type='hidden' name='" + action + "' value='yes'>");
			$("#multiform").submit();
		};
		$(this).click(function() {
			var sel = $('[name^=multiselect-]:checked').length;
			$(".dropdownmenu").remove();
			var p = $(this).offset();
			p.top += 30; p.left += 45;
			var drop = $('<div class="dropdownmenu"></div>').css('position', 'absolute').css('z-index', 2).hover(function() {}, function() {
				$(".dropdownmenu").remove();
			});
			$(drop).append($('<div class="dropdown last">Delete selected (' + sel + ')</div>').data('action', 'delete').click(f));
			$(drop).append($('<div class="dropdown last">Bounce selected (' + sel + ')</div>').data('action', 'bounce').click(f));
			$(drop).append($('<div class="dropdown last">Retry/release selected (' + sel + ')</div>').data('action', 'retry').click(f));
			$("body").append($(drop).offset(p));
		});
	});
	$("#select-all").click(function() {
		$('[name^=multiselect-]').prop('checked', this.checked);
	});
	$(".icon.go").click(function() {
		$('[name^=multiselect-]').prop('checked', false);
		$(this).closest("tr").find("input").prop('checked', true);
		$("#multiform").append("<input type='hidden' name='retry' value='yes'>");
		$("#multiform").submit();
	});
});
