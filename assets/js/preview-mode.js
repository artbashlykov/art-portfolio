(function () {
	'use strict';

	if (history.scrollRestoration) {
		history.scrollRestoration = 'manual';
	}

	var pinned =
		'undefined' === typeof window.artPortfolioPreviewPinned ? true : Boolean(window.artPortfolioPreviewPinned);

	Object.defineProperty(window, 'artPortfolioPreviewPinned', {
		configurable: true,
		get: function () {
			return pinned;
		},
		set: function (value) {
			pinned = Boolean(value);
			syncPinnedClass();

			if (pinned) {
				toTop();
			}
		}
	});

	function syncPinnedClass() {
		if (!document.documentElement) {
			return;
		}

		document.documentElement.classList.toggle('art-portfolio-preview-pinned', pinned);
	}

	function toTop() {
		if (!pinned) {
			return;
		}

		if (document.documentElement) {
			document.documentElement.scrollTop = 0;
		}

		if (document.body) {
			document.body.scrollTop = 0;
		}
	}

	if (Element.prototype.scrollIntoView) {
		var nativeScrollIntoView = Element.prototype.scrollIntoView;

		Element.prototype.scrollIntoView = function () {
			if (pinned) {
				return;
			}

			return nativeScrollIntoView.apply(this, arguments);
		};
	}

	syncPinnedClass();
	toTop();
	document.addEventListener('DOMContentLoaded', toTop);
	document.addEventListener('focusin', toTop);
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
