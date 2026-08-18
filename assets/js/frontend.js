(function () {
	'use strict';

	if (typeof document === 'undefined') {
		return;
	}

	var VIEWPORT_WIDTH = 1440;
	var VIEWPORT_HEIGHT = 900;
	var LOAD_TIMEOUT = 12000;
	var hoverQuery = window.matchMedia ? window.matchMedia('(hover: none)') : null;

	function config() {
		return window.artPortfolio || {};
	}

	function isDebug() {
		return Boolean(config().debug);
	}

	function timeoutMs() {
		var value = parseInt(config().timeout, 10);
		return value > 0 ? value : LOAD_TIMEOUT;
	}

	function viewportWidth() {
		var value = parseInt(config().viewportWidth, 10);
		return value > 0 ? value : VIEWPORT_WIDTH;
	}

	function viewportHeight() {
		var value = parseInt(config().viewportHeight, 10);
		return value > 0 ? value : VIEWPORT_HEIGHT;
	}

	function isHoverNone() {
		return Boolean(hoverQuery && hoverQuery.matches);
	}

	function logDebug() {
		if (!isDebug() || !window.console) {
			return;
		}

		Function.prototype.apply.call(console.debug, console, arguments);
	}

	function show(el) {
		if (el) {
			el.hidden = false;
		}
	}

	function hide(el) {
		if (el) {
			el.hidden = true;
		}
	}

	function createIframe(preview, url) {
		var iframe = document.createElement('iframe');
		var title = preview.getAttribute('aria-label') || '';

		iframe.className = 'art-portfolio-card__iframe';
		iframe.setAttribute('title', title);
		iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
		iframe.setAttribute('tabindex', '-1');
		iframe.width = String(viewportWidth());
		iframe.height = String(viewportHeight());
		iframe.style.transformOrigin = '0 0';
		iframe.src = url;

		return iframe;
	}

	function initCard(card) {
		var preview = card.querySelector('.art-portfolio-card__preview');

		if (!preview) {
			return;
		}

		var url = preview.getAttribute('data-preview-url');

		if (!url) {
			return;
		}

		var live = preview.querySelector('.art-portfolio-card__live-preview');
		var loader = preview.querySelector('.art-portfolio-card__loader');
		var error = preview.querySelector('.art-portfolio-card__error');
		var iframe = null;
		var resizeObserver = null;
		var visibilityObserver = null;
		var loadTimer = 0;
		var loaded = false;
		var failed = false;
		var creating = false;
		var interactive = false;

		if (!live || !loader || !error) {
			return;
		}

		function clearTimer() {
			if (loadTimer) {
				window.clearTimeout(loadTimer);
				loadTimer = 0;
			}
		}

		function shouldUseMobileLayout() {
			return isHoverNone() && interactive;
		}

		function scaleIframe() {
			if (!preview || !iframe) {
				return;
			}

			if (shouldUseMobileLayout()) {
				iframe.style.transform = '';
				return;
			}

			var width = preview.clientWidth || 0;
			var scale = width > 0 ? width / viewportWidth() : 1;
			iframe.style.transformOrigin = '0 0';
			iframe.style.transform = 'scale(' + scale + ')';
		}

		function watchResize() {
			if (!iframe || typeof ResizeObserver !== 'function' || resizeObserver) {
				return;
			}

			resizeObserver = new ResizeObserver(function () {
				scaleIframe();
			});
			resizeObserver.observe(preview);
		}

		function getIframeWindow() {
			if (!iframe) {
				return null;
			}

			try {
				return iframe.contentWindow;
			} catch (error) {
				return null;
			}
		}

		function resetIframeScroll() {
			var win = getIframeWindow();

			if (!win) {
				return;
			}

			win.scrollTo(0, 0);

			try {
				if (win.document && win.document.documentElement) {
					win.document.documentElement.scrollTop = 0;
				}

				if (win.document && win.document.body) {
					win.document.body.scrollTop = 0;
				}
			} catch (error) {
				return;
			}
		}

		function setIframePinned(pinned) {
			var win = getIframeWindow();

			if (!win) {
				return;
			}

			try {
				win.artPortfolioPreviewPinned = Boolean(pinned);
			} catch (error) {
				return;
			}

			if (pinned) {
				resetIframeScroll();
			}
		}

		function setInteractive(on) {
			if (isHoverNone()) {
				interactive = false;
				card.classList.remove('is-interactive');
				scaleIframe();
				setIframePinned(true);
				return;
			}

			interactive = Boolean(on);
			card.classList.toggle('is-interactive', interactive);
			scaleIframe();
			setIframePinned(!interactive);
		}

		function ensureIframe() {
			if (loaded || failed || iframe || creating) {
				return;
			}

			creating = true;
			hide(error);
			show(loader);
			show(live);

			iframe = createIframe(preview, url);
			live.appendChild(iframe);
			watchResize();
			scaleIframe();

			iframe.addEventListener('load', function () {
				clearTimer();
				creating = false;
				loaded = true;
				failed = false;
				hide(loader);
				hide(error);
				show(live);
				card.classList.add('is-preview-ready');
				scaleIframe();
				resetIframeScroll();
				setIframePinned(!interactive);
				logDebug('ART Portfolio: preview loaded', url);
			});

			loadTimer = window.setTimeout(function () {
				if (loaded) {
					return;
				}

				creating = false;
				failed = true;
				hide(loader);
				show(error);
				card.classList.remove('is-preview-ready');
				logDebug('ART Portfolio: preview timeout', url);
			}, timeoutMs());
		}

		if (typeof IntersectionObserver === 'function') {
			visibilityObserver = new IntersectionObserver(
				function (entries) {
					entries.forEach(function (entry) {
						if (entry.isIntersecting) {
							ensureIframe();

							if (visibilityObserver) {
								visibilityObserver.unobserve(preview);
							}
						}
					});
				},
				{
					root: null,
					rootMargin: '80px 0px',
					threshold: 0.15,
				}
			);
			visibilityObserver.observe(preview);
		}

		preview.addEventListener('pointerenter', function () {
			if (!isHoverNone()) {
				ensureIframe();
				setInteractive(true);
			}
		});

		preview.addEventListener('pointerleave', function () {
			if (!isHoverNone()) {
				setInteractive(false);
			}
		});

		preview.addEventListener(
			'wheel',
			function (event) {
				if (!interactive || !loaded) {
					return;
				}

				var win = getIframeWindow();

				if (!win) {
					return;
				}

				event.preventDefault();
				event.stopPropagation();

				var x = event.deltaX;
				var y = event.deltaY;

				if (1 === event.deltaMode) {
					x *= 16;
					y *= 16;
				} else if (2 === event.deltaMode) {
					x *= viewportWidth();
					y *= viewportHeight();
				}

				if (!shouldUseMobileLayout()) {
					var scale = preview.clientWidth / viewportWidth();

					if (scale > 0) {
						x = x / scale;
						y = y / scale;
					}
				}

				win.scrollBy(x, y);
			},
			{ passive: false }
		);

		preview.addEventListener('focusin', function () {
			if (isHoverNone()) {
				return;
			}

			ensureIframe();
			setInteractive(true);
		});

		preview.addEventListener('focusout', function (event) {
			if (preview.contains(event.relatedTarget)) {
				return;
			}

			setInteractive(false);
		});
	}

	function initGrid(grid) {
		if (grid.getAttribute('data-art-portfolio-ready') === '1') {
			return;
		}

		grid.setAttribute('data-art-portfolio-ready', '1');
		grid.querySelectorAll('.art-portfolio-card').forEach(initCard);
	}

	function withoutHash(href) {
		try {
			var url = new URL(href, window.location.href);
			url.hash = '';
			return url.toString();
		} catch (error) {
			return String(href || '').split('#')[0];
		}
	}

	function initPluginLinks(root) {
		root.addEventListener(
			'click',
			function (event) {
				var link = event.target.closest(
					'a.art-portfolio-filters__chip, a.art-portfolio-pagination__link, a.art-portfolio-card__button, a.art-portfolio-card__title-link, a.art-portfolio-card__image-link'
				);

				if (!link || !root.contains(link)) {
					return;
				}

				var url = withoutHash(link.getAttribute('href') || '');

				if (url) {
					link.setAttribute('href', url);
				}
			},
			true
		);
	}

	function initGallery(root) {
		initPluginLinks(root);

		var grid = root.querySelector('.art-portfolio-grid');

		if (grid) {
			initGrid(grid);
		}
	}

	function initAll() {
		if (document.body && document.body.classList.contains('art-portfolio-preview-mode')) {
			return;
		}

		document.querySelectorAll('.art-portfolio').forEach(initGallery);
		document.querySelectorAll('.art-portfolio-grid').forEach(initGrid);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAll);
	} else {
		initAll();
	}
})();
