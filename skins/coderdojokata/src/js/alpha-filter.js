!(function($) {
	$(function() {
		$('.alpha-filter').not('.no-js').each(function() {
			var cont = $(this);
			if (cont.data('filter-initialized')) {
				return;
			}
			var filters = cont.find('.filters>*[data-filter]');
			var elements = cont.find('.filter-elements>*[data-filter]');
			filters.off('click.alpha-filter').not('.disabled').on('click.alpha-filter', function() {
				var $t = $(this);
				var f = $t.data('filter');
				elements.hide().filter('[data-filter=' + f + ']').show();
				filters.removeClass('selected');
				$t.addClass('selected');
				return false;
			});
			elements.hide();
			cont.data('filter-initialized', true);
			if (filters.filter('.selected').length > 0) {
				filters.filter('.selected').triggerHandler('click');
			} else {
				filters.not('.disabled').slice(0, 1).triggerHandler('click');
			}
		});
	});
}(jQuery));