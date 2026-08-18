(function () {
	'use strict';

	function getHomeHost() {
		if (!window.artPortfolioAdmin || !artPortfolioAdmin.homeHost) {
			return '';
		}

		return String(artPortfolioAdmin.homeHost).toLowerCase();
	}

	function parseHost(value) {
		if (!value) {
			return '';
		}

		try {
			var url = new URL(value, window.location.origin);
			return (url.host || '').toLowerCase();
		} catch (error) {
			return '';
		}
	}

	function initExternalNotice(root) {
		var input = root.querySelector('#art-portfolio-preview-url');
		var notice = root.querySelector('[data-art-portfolio-external-notice]');

		if (!input || !notice) {
			return;
		}

		function update() {
			var host = parseHost(input.value.trim());
			var homeHost = getHomeHost();
			var isExternal = host !== '' && homeHost !== '' && host !== homeHost;
			notice.classList.toggle('is-hidden', !isExternal);
		}

		input.addEventListener('input', update);
		input.addEventListener('change', update);
		update();
	}

	function initPicker(root) {
		var select = root.querySelector('#art-portfolio-preview-post');
		var input = root.querySelector('#art-portfolio-preview-url');

		if (!select || !input) {
			return;
		}

		select.addEventListener('change', function () {
			var option = select.options[select.selectedIndex];
			var permalink = option ? option.getAttribute('data-permalink') : '';

			if (permalink) {
				input.value = permalink;
				input.dispatchEvent(new Event('input'));
			}
		});

		input.addEventListener('input', function () {
			var option = select.options[select.selectedIndex];
			var permalink = option ? option.getAttribute('data-permalink') : '';

			if (permalink && input.value.trim() !== permalink) {
				select.value = '0';
			}
		});
	}

	function reindexRows(container) {
		var rows = container.querySelectorAll('[data-art-portfolio-row]');

		rows.forEach(function (row, index) {
			row.querySelectorAll('input').forEach(function (field) {
				var name = field.getAttribute('name');

				if (!name) {
					return;
				}

				field.setAttribute(
					'name',
					name.replace(/art_portfolio_meta_rows\[\d+\]/, 'art_portfolio_meta_rows[' + index + ']')
				);
			});
		});
	}

	function initRepeater(root) {
		var repeater = root.querySelector('[data-art-portfolio-repeater]');

		if (!repeater) {
			return;
		}

		var rows = repeater.querySelector('[data-art-portfolio-rows]');
		var template = repeater.querySelector('[data-art-portfolio-row-template]');
		var addButton = repeater.querySelector('[data-art-portfolio-add-row]');

		if (!rows || !template || !addButton) {
			return;
		}

		addButton.addEventListener('click', function () {
			var html = template.innerHTML.replace(/__index__/g, String(rows.children.length));
			var wrap = document.createElement('div');
			wrap.innerHTML = html.trim();
			var row = wrap.firstElementChild;

			if (row) {
				rows.appendChild(row);
				reindexRows(rows);
			}
		});

		repeater.addEventListener('click', function (event) {
			var button = event.target.closest('[data-art-portfolio-remove-row]');

			if (!button) {
				return;
			}

			var row = button.closest('[data-art-portfolio-row]');

			if (row && rows.contains(row)) {
				row.remove();

				if (!rows.children.length) {
					addButton.click();
				} else {
					reindexRows(rows);
				}
			}
		});
	}

	function getCopyLabel( key, fallback ) {
		if ( window.artPortfolioAdmin && artPortfolioAdmin.strings && artPortfolioAdmin.strings[ key ] ) {
			return String( artPortfolioAdmin.strings[ key ] );
		}

		return fallback;
	}

	function copyTextFallback(value) {
		var textarea = document.createElement('textarea');
		textarea.value = value;
		textarea.setAttribute('readonly', '');
		textarea.style.position = 'fixed';
		textarea.style.left = '-9999px';
		document.body.appendChild(textarea);
		textarea.select();

		var copied = false;

		try {
			copied = document.execCommand('copy');
		} catch (error) {
			copied = false;
		}

		document.body.removeChild(textarea);

		if (!copied) {
			window.prompt(getCopyLabel('copy', 'Скопировать'), value);
		}

		return copied;
	}

	function initImagePicker(root) {
		var wrap = root.querySelector('[data-art-portfolio-image]');
		var input = root.querySelector('#art-portfolio-thumbnail-id');
		var preview = root.querySelector('[data-art-portfolio-image-preview]');
		var selectButton = root.querySelector('[data-art-portfolio-image-select]');
		var removeButton = root.querySelector('[data-art-portfolio-image-remove]');

		if (!wrap || !input || !preview || !selectButton || !removeButton) {
			return;
		}

		if (typeof wp === 'undefined' || !wp.media) {
			return;
		}

		var frame;

		function setPreview(url) {
			preview.replaceChildren();

			if (!url) {
				preview.classList.add('is-empty');
				removeButton.classList.add('is-hidden');
				return;
			}

			var image = document.createElement('img');
			image.src = url;
			image.alt = '';
			preview.appendChild(image);
			preview.classList.remove('is-empty');
			removeButton.classList.remove('is-hidden');
		}

		selectButton.addEventListener('click', function (event) {
			event.preventDefault();

			if (frame) {
				frame.open();
				return;
			}

			frame = wp.media({
				title: getCopyLabel('selectImage', 'Выбрать изображение'),
				button: {
					text: getCopyLabel('useImage', 'Использовать изображение'),
				},
				library: {
					type: 'image',
				},
				multiple: false,
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first();

				if (!attachment) {
					return;
				}

				var data = attachment.toJSON();
				var url = data.url || '';

				if (data.sizes && data.sizes.medium && data.sizes.medium.url) {
					url = data.sizes.medium.url;
				}

				input.value = String(data.id || '');
				setPreview(url);
			});

			frame.open();
		});

		removeButton.addEventListener('click', function (event) {
			event.preventDefault();
			input.value = '0';
			setPreview('');
		});
	}

	function initCopyButtons() {
		document.querySelectorAll('[data-art-portfolio-copy]').forEach(function (button) {
			if (button.getAttribute('data-art-portfolio-copy-ready') === '1') {
				return;
			}

			button.setAttribute('data-art-portfolio-copy-ready', '1');

			button.addEventListener('click', function () {
				var value = button.getAttribute('data-art-portfolio-copy-text') || '';

				if (!value) {
					var selector = button.getAttribute('data-art-portfolio-copy') || '';
					var target = selector ? document.querySelector(selector) : null;
					value = target ? String(target.textContent || '').trim() : '';
				}

				if (!value) {
					return;
				}

				var original = button.textContent;
				var copiedLabel = getCopyLabel('copied', 'Скопировано');

				function markCopied() {
					button.textContent = copiedLabel;
					window.setTimeout(function () {
						button.textContent = original;
					}, 1500);
				}

				if (navigator.clipboard && window.isSecureContext && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(value).then(markCopied).catch(function () {
						if (copyTextFallback(value)) {
							markCopied();
						}
					});
					return;
				}

				if (copyTextFallback(value)) {
					markCopied();
				}
			});
		});
	}

	function init() {
		var root = document.querySelector('.art-portfolio-metabox');

		if (root) {
			initExternalNotice(root);
			initPicker(root);
			initImagePicker(root);
			initRepeater(root);
		}

		initCopyButtons();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
