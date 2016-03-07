// Get mouse position
var mouseX;
var mouseY;
$(document).mousemove(function (e) {
	mouseX = e.pageX;
	mouseY = e.pageY;
});

$(function() {
	
	// Hide menu when clicking outside the menu
	$(document).mousedown(function (e) {
		if (!$(e.target).hasClass('filter-button') 
			&& !$(e.target).hasClass('filter-menu')
			&& !$(e.target).parents().hasClass('filter-menu')) {
			$('.filter-menu').hide(200);
		}
	});
	
	// Toggle menu and move to mouse position when clicking filter button
	$('.filter-button').click(function() {
		var menu = $('#filter_' + $(this).attr('filter'));
		$('.filter-menu').not(menu).hide();
		menu.toggle(200).css({'top': mouseY, 'left': mouseX});
	});
	
	// Filter results on menu selection
	$('.filter-menu select').change(function() {
		var page = typeof getUrlParameter('page') !== 'undefined' ? getUrlParameter('page') : '';
		var sort = typeof getUrlParameter('sort') !== 'undefined' ? getUrlParameter('sort') : '';
		var filterattr = $(this).attr('filter');
		var filterval = encodeURIComponent($(this).val());
		var url = '?page=' + page + '&sort=' + sort + '&filterattr=' + filterattr + '&filterval=' + filterval;
		window.location = url;
	});
});