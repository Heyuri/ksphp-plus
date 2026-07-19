(function() {
	'use strict';

	// Default set of extensions offered on the settings page.
	// All are enabled by default; the person can narrow this down on the
	// settings page ({setup=1}). Selection is stored in localStorage only
	// (no server round-trip), matching js/upthumb.js's approach.
	const DEFAULT_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'avif', 'svg'];

	const STORAGE_ENABLED_KEY = 'imgThumbEnabled';
	const STORAGE_EXTENSIONS_KEY = 'imgThumbExtensions';

	function isEnabled() {
		return localStorage.getItem(STORAGE_ENABLED_KEY) !== 'false';
	}

	function setEnabled(value) {
		localStorage.setItem(STORAGE_ENABLED_KEY, String(value));
	}

	function getEnabledExtensions() {
		try {
			const stored = localStorage.getItem(STORAGE_EXTENSIONS_KEY);
			if (stored) {
				const parsed = JSON.parse(stored);
				if (Array.isArray(parsed)) {
					return parsed;
				}
			}
		} catch (e) {
			// fall through to default
		}
		return DEFAULT_EXTENSIONS.slice();
	}

	function setEnabledExtensions(list) {
		localStorage.setItem(STORAGE_EXTENSIONS_KEY, JSON.stringify(list));
	}

	function getUrlExtension(url) {
		const withoutQuery = url.split(/[?#]/)[0];
		const match = withoutQuery.match(/\.([a-zA-Z0-9]+)$/);
		return match ? match[1].toLowerCase() : null;
	}

	// Confirms the URL is actually reachable and actually an image, via a
	// HEAD request. This is a deliberate choice (over trusting the file
	// extension alone) so a non-image response never gets embedded as a
	// broken thumbnail.
	function verifyIsImage(url) {
		return new Promise(resolve => {
			fetch(url, { method: 'HEAD' })
				.then(res => {
					if (!res.ok) {
						resolve(false);
						return;
					}
					const contentType = res.headers.get('Content-Type') || '';
					resolve(contentType.indexOf('image/') === 0);
				})
				.catch(() => resolve(false));
		});
	}

	function createThumbnail(url) {
		const img = document.createElement('img');
		img.src = url;
		img.loading = 'lazy';
		img.className = 'imgthumb-thumb';
		img.style.maxHeight = '95px';
		img.style.maxWidth = '200px';
		img.style.marginLeft = '5px';
		img.style.verticalAlign = 'middle';
		return img;
	}

	function clearExistingThumbnails() {
		document.querySelectorAll('.imgthumb-thumb').forEach(el => el.remove());
	}

	async function runScript() {
		clearExistingThumbnails();

		if (!isEnabled()) {
			return;
		}

		const extensions = getEnabledExtensions();
		if (extensions.length === 0) {
			return;
		}

		const postContents = document.querySelectorAll('.contents pre.msgnormal, .msgtree .ngline');

		for (const postContent of postContents) {
			const links = postContent.querySelectorAll('a');

			for (const link of links) {
				const ext = getUrlExtension(link.href);
				if (!ext || extensions.indexOf(ext) === -1) {
					continue;
				}

				const confirmed = await verifyIsImage(link.href);
				if (!confirmed) {
					continue;
				}

				link.insertAdjacentElement('afterend', createThumbnail(link.href));
			}
		}
	}

	// Adds the master on/off checkbox next to the post form, in the same
	// row as the existing "Uploader thumbnails" checkbox from upthumb.js.
	function addMainCheckbox() {
		const smallDiv = document.querySelector('.small');
		if (!smallDiv) {
			return;
		}

		if (document.getElementById('enableImgThumbnails')) {
			return;
		}

		const submitButton = smallDiv.querySelector('input[type="submit"]');
		if (!submitButton) {
			return;
		}

		const label = document.createElement('label');
		label.setAttribute('for', 'enableImgThumbnails');
		label.textContent = (window.KSPHP_LANG && window.KSPHP_LANG.LINK_THUMBNAILS_LABEL) || 'Link thumbnails';

		const checkbox = document.createElement('input');
		checkbox.type = 'checkbox';
		checkbox.id = 'enableImgThumbnails';
		checkbox.checked = isEnabled();

		checkbox.addEventListener('change', function() {
			setEnabled(checkbox.checked);
			runScript();
		});

		smallDiv.insertBefore(label, submitButton);
		smallDiv.insertBefore(checkbox, submitButton);
	}

	// Adds the per-extension checkboxes to the personal settings page
	// ({setup=1}). Purely client-side: these checkboxes have no name
	// attribute and live outside the settings <form>, so they are never
	// submitted to the server.
	function addSettingsUI() {
		// The settings page is identified by its unique "cr" (restore
		// defaults) button, which only exists on that page.
		const restoreButton = document.querySelector('input[name="cr"]');
		if (!restoreButton) {
			return;
		}

		const form = restoreButton.closest('form');
		if (!form || document.getElementById('imgThumbExtensionFieldset')) {
			return;
		}

		const enabledExtensions = getEnabledExtensions();

		const fieldset = document.createElement('fieldset');
		fieldset.id = 'imgThumbExtensionFieldset';

		const legend = document.createElement('legend');
		legend.textContent = (window.KSPHP_LANG && window.KSPHP_LANG.LINK_THUMBNAIL_EXTENSIONS_LEGEND) || 'Link thumbnail extensions';
		fieldset.appendChild(legend);

		DEFAULT_EXTENSIONS.forEach((ext, index) => {
			const id = 'imgThumbExt' + index;

			const checkbox = document.createElement('input');
			checkbox.type = 'checkbox';
			checkbox.id = id;
			checkbox.checked = enabledExtensions.indexOf(ext) !== -1;
			checkbox.addEventListener('change', function() {
				const current = getEnabledExtensions();
				const withoutExt = current.filter(item => item !== ext);
				const updated = checkbox.checked ? withoutExt.concat([ext]) : withoutExt;
				setEnabledExtensions(updated);
				runScript();
			});

			const label = document.createElement('label');
			label.setAttribute('for', id);
			label.textContent = '.' + ext;

			fieldset.appendChild(checkbox);
			fieldset.appendChild(label);
			fieldset.appendChild(document.createTextNode(' '));
		});

		form.insertAdjacentElement('afterend', fieldset);
	}

	function init() {
		addMainCheckbox();
		addSettingsUI();
		runScript();
	}

	init();
})();
