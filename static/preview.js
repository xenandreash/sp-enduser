$(document).ready(function() {
	$('.tracking-actions').each(function() {
		var f = function() {
			$("#action").val($(this).data("action"));
			$("#actionform").submit();
		};
		$(this).click(function() {
			$(".dropdownmenu").remove();
			var p = $(this).offset();
			p.top += 30; p.left += 45;
			var drop = $('<div class="dropdownmenu"></div>').css('position', 'absolute').css('z-index', 2).hover(function() {}, function() {
				$(".dropdownmenu").remove();
			});
			$(drop).append($('<div class="dropdown last">Delete</div>').data('action', 'delete').click(f));
			$(drop).append($('<div class="dropdown last">Bounce</div>').data('action', 'bounce').click(f));
			$(drop).append($('<div class="dropdown last">Retry/release</div>').data('action', 'retry').click(f));
			$("body").append($(drop).offset(p));
		});
	});
});
