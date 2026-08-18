(function () {
	'use strict';

	if (history.scrollRestoration) {
		history.scrollRestoration = 'manual';
	}

	if ( 'undefined' === typeof window.artPortfolioPreviewPinned ) {
		window.artPortfolioPreviewPinned = true;
	}

	function toTop() {
		if (false === window.artPortfolioPreviewPinned) {
			return;
		}

		window.scrollTo(0, 0);

		if (document.documentElement) {
			document.documentElement.scrollTop = 0;
		}

		if (document.body) {
			document.body.scrollTop = 0;
		}
	}

	toTop();
	document.addEventListener('DOMContentLoaded', toTop);
	window.addEventListener('load', toTop);
	window.addEventListener(
		'scroll',
		function () {
			toTop();
		},
		{ passive: true }
	);
	window.setTimeout(toTop, 50);
	window.setTimeout(toTop, 250);
	window.setTimeout(toTop, 800);
})();
