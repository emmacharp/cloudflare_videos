/**
* Copyright: Deux Huit Huit 2019
* License: MIT, see the LICENSE file
*/
(function ($, Sym) {
	
	'use strict';

	var init = function () {
		Sym.Elements.contents.find('div.field-cloudflare_video').each(function () {
			var t = $(this);
			var removeBtn = t.find('.js-cloudflare-video-remove');
			var input = t.find('input');
			var stateCtn = t.find('.js-cloudflare-video-state-ctn');

			if (!!input.val()) {
				input.addClass('is-hidden');
			} else {
				removeBtn.addClass('is-hidden');
			}

			removeBtn.on('click', function (event) {
				stateCtn.empty().addClass('is-hidden');
				removeBtn.addClass('is-hidden');
				input.removeClass('is-hidden');
				input.val('').attr('type', 'file');
				event.preventDefault();
				event.stopPropagation();
				return false;
			});
			
		});
	};
	
	$(init);
	
})(window.jQuery, window.Symphony);
