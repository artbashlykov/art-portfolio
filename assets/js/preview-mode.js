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
				blurActive();
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

	function withPreventScroll(options) {
		if (!options || 'object' !== typeof options) {
			return { preventScroll: true };
		}

		var next = {};
		var key;

		for (key in options) {
			if (Object.prototype.hasOwnProperty.call(options, key)) {
				next[key] = options[key];
			}
		}

		next.preventScroll = true;
		return next;
	}

	function patchFocus(proto) {
		if (!proto || 'function' !== typeof proto.focus) {
			return;
		}

		var nativeFocus = proto.focus;

		proto.focus = function (options) {
			return nativeFocus.call(this, withPreventScroll(options));
		};
	}

	function scrollElementIntoIframeView(el, arg) {
		if (!el || pinned) {
			return;
		}

		var rect = el.getBoundingClientRect();
		var viewport = window.innerHeight || 0;
		var current =
			(document.documentElement && document.documentElement.scrollTop) ||
			(document.body && document.body.scrollTop) ||
			0;
		var delta = 0;
		var block = 'start';

		if (arg && 'object' === typeof arg && arg.block) {
			block = String(arg.block);
		} else if ('string' === typeof arg) {
			block = arg;
		}

		if ('nearest' === block) {
			if (rect.top < 0) {
				delta = rect.top;
			} else if (rect.bottom > viewport) {
				delta = rect.bottom - viewport;
			}
		} else if ('center' === block) {
			delta = rect.top - viewport / 2 + rect.height / 2;
		} else if ('end' === block) {
			delta = rect.bottom - viewport;
		} else {
			delta = rect.top;
		}

		if (!delta) {
			return;
		}

		if (document.documentElement) {
			document.documentElement.scrollTop = current + delta;
		}

		if (document.body) {
			document.body.scrollTop = current + delta;
		}
	}

	function stripAutofocus(root) {
		if (!root) {
			return;
		}

		if (root.nodeType === 1 && root.hasAttribute && root.hasAttribute('autofocus')) {
			root.removeAttribute('autofocus');
		}

		if (!root.querySelectorAll) {
			return;
		}

		root.querySelectorAll('[autofocus]').forEach(function (el) {
			el.removeAttribute('autofocus');
		});
	}

	function blurActive() {
		if (!pinned || !document.activeElement) {
			return;
		}

		var active = document.activeElement;

		if (active === document.body || active === document.documentElement) {
			return;
		}

		if ('function' === typeof active.blur) {
			try {
				active.blur();
			} catch (error) {
				return;
			}
		}
	}

	patchFocus(HTMLElement.prototype);

	if ('undefined' !== typeof SVGElement) {
		patchFocus(SVGElement.prototype);
	}

	if (Element.prototype.scrollIntoView) {
		Element.prototype.scrollIntoView = function (arg) {
			scrollElementIntoIframeView(this, arg);
		};
	}

	if (document.documentElement && typeof MutationObserver === 'function') {
		stripAutofocus(document.documentElement);

		new MutationObserver(function (mutations) {
			mutations.forEach(function (mutation) {
				mutation.addedNodes.forEach(function (node) {
					if (node.nodeType === 1) {
						stripAutofocus(node);
					}
				});
			});
		}).observe(document.documentElement, {
			childList: true,
			subtree: true
		});
	}

	syncPinnedClass();
	toTop();
	stripAutofocus(document);
	blurActive();

	document.addEventListener('DOMContentLoaded', function () {
		stripAutofocus(document);
		toTop();
		blurActive();
	});

	document.addEventListener(
		'focusin',
		function (event) {
			if (!pinned) {
				return;
			}

			toTop();

			if (event.target && event.target !== document.body && 'function' === typeof event.target.blur) {
				try {
					event.target.blur();
				} catch (error) {
					return;
				}
			}
		},
		true
	);

	window.addEventListener('load', function () {
		toTop();
		blurActive();
	});

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
