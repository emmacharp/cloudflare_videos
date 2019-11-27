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

			var uploadCtn = t.find('.js-cloudflare-video-upload-ctn');
			var removeCtn = t.find('.js-cloudflare-video-remove-ctn');
			var stateCtn = t.find('.js-cloudflare-video-state-ctn');

			if (!!input.val()) {
				uploadCtn.addClass('is-hidden');
			} else {
				removeCtn.addClass('is-hidden');
			}

			removeBtn.on('click', function (event) {
				stateCtn.empty().addClass('is-hidden');
				removeCtn.addClass('is-hidden');
				uploadCtn.removeClass('is-hidden');
				input.val('').attr('type', 'file');
				event.preventDefault();
				event.stopPropagation();
				return false;
			});
			
		});
	};
	
	$(init);
	
})(window.jQuery, window.Symphony);
