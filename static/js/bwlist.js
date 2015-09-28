$(document).ready(function() {
	$('#check-all').click(function() {
			$('input.recipient').prop('checked', true);
			return false;
	});
	$('#add-access').click(function() {
		$("#extra-accesses").prepend("<div class='checkbox'><input type='text' name='access[]' class='form-control' placeholder='Email or domain'></div>");
		return false;
	});
	$(".toggle").click(function() {
		$(".hidden-" + $(this).data("toggle")).toggle();
		var icon = $(this).find(".expand-icon");
		if (icon.hasClass('glyphicon-expand'))
			icon.addClass('glyphicon-collapse-down').removeClass('glyphicon-expand');
		else
			icon.addClass('glyphicon-expand').removeClass('glyphicon-collapse-down');
	});
});
