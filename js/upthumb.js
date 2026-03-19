(function() {
	'use strict';

	const thumbnailingAllowed = false;

	const instances = [
		{
			uploadDir: 'https://up.heyuri.net/src/',
			thumbDir: 'https://up.heyuri.net/thumb/',
			thumb_suffix: 's',
			thumbnailExtension: 'jpg'
		},
		{
			uploadDir: 'https://up.heyuri.net/user/boards/*/src/',
			thumbDir: 'https://up.heyuri.net/user/boards/*/thmb/',
			thumb_suffix: '_thumb',
			thumbnailExtension: 'jpg'
		},
		{
			uploadDir: 'https://example.com/uploads/',
			thumbDir: 'https://example.com/thumbs/',
			thumb_suffix: '_thumb',
			thumbnailExtension: 'jpg'
		}
	];

	if (!thumbnailingAllowed) {
		return;
	}

	function normalizeBase(url) {
		return url.replace(/^[a-z]+:\/\//i, '');
	}

	function escapeRegex(text) {
		return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	}

	function buildUploadRegex(uploadDir) {
		const normalized = normalizeBase(uploadDir);
		const parts = normalized.split('*').map(escapeRegex);
		return new RegExp('^(?:[a-z]+:\\/\\/)?' + parts.join('([^/]+)'));
	}

	function findInstanceMatch(url) {
		for (const instance of instances) {
			const regex = buildUploadRegex(instance.uploadDir);
			const match = url.match(regex);
			if (match) {
				return {
					instance: instance,
					match: match
				};
			}
		}

		return null;
	}

	function addCheckbox() {
		const smallDiv = document.querySelector('.small');
		if (!smallDiv) {
			return;
		}

		if (document.getElementById('enableThumbnails')) {
			return;
		}

		const submitButton = smallDiv.querySelector('input[type="submit"]');
		if (!submitButton) {
			return;
		}

		const label = document.createElement('label');
		label.setAttribute('for', 'enableThumbnails');
		label.textContent = 'Uploader thumbnails';

		const checkbox = document.createElement('input');
		checkbox.type = 'checkbox';
		checkbox.name = 'enableThumbnails';
		checkbox.accessKey = 'T';
		checkbox.value = 'checked';
		checkbox.title = 'Alt(+Shift)+T';
		checkbox.id = 'enableThumbnails';
		checkbox.checked = localStorage.getItem('enableThumbnails') !== 'false';

		smallDiv.insertBefore(label, submitButton);
		smallDiv.insertBefore(checkbox, submitButton);
	}

	function createThumbnail(thumbnailUrl, originalUrl) {
		const anchor = document.createElement('a');
		anchor.href = originalUrl;
		anchor.target = '_blank';

		const img = document.createElement('img');
		img.src = thumbnailUrl;
		img.loading = 'lazy';
		img.style.maxHeight = '95px';
		img.style.maxWidth = '200px';
		img.style.margin = '5px';

		anchor.appendChild(img);
		return anchor;
	}

	function thumbnailExists(url) {
		return new Promise(resolve => {
			const xhr = new XMLHttpRequest();
			xhr.open('HEAD', url, true);
			xhr.onload = function() {
				resolve(xhr.status >= 200 && xhr.status < 400);
			};
			xhr.onerror = function() {
				resolve(false);
			};
			xhr.send();
		});
	}

	function swapJpgPngExtension(url) {
		if (url.endsWith('.jpg')) {
			return url.slice(0, -4) + '.png';
		}

		if (url.endsWith('.png')) {
			return url.slice(0, -4) + '.jpg';
		}

		return url;
	}

	function getThumbnailCandidates(url) {
		const found = findInstanceMatch(url);
		if (!found) {
			return [];
		}

		const instance = found.instance;
		const match = found.match;
		const wildcardValues = match.slice(1);
		let thumbDir = instance.thumbDir;

		for (const value of wildcardValues) {
			thumbDir = thumbDir.replace('*', value);
		}

		const remainder = url.slice(match[0].length);
		const fileWithoutExtension = remainder.replace(/\.[^.\/?#]+([?#].*)?$/, '');
		const primaryUrl = thumbDir + fileWithoutExtension + instance.thumb_suffix + '.' + instance.thumbnailExtension;
		const alternateUrl = swapJpgPngExtension(primaryUrl);

		if (alternateUrl !== primaryUrl) {
			return [primaryUrl, alternateUrl];
		}

		return [primaryUrl];
	}

	function clearExistingThumbnailContainers() {
		document.querySelectorAll('.twintail-thumbnail-container').forEach(container => {
			container.remove();
		});
	}

	async function runScript() {
		clearExistingThumbnailContainers();

		const postContents = document.querySelectorAll('.contents pre.msgnormal, .msgtree .ngline');

		for (const postContent of postContents) {
			const links = postContent.querySelectorAll('a');
			const twintailLinks = [];

			links.forEach(link => {
				const parentLine = link.closest('span.q') || link.parentElement;
				if (!parentLine) {
					return;
				}

				const lineContent = parentLine.textContent.trim();
				const previousSiblingText = link.previousSibling && typeof link.previousSibling.textContent === 'string'
					? link.previousSibling.textContent.trim()
					: '';

				if (lineContent.startsWith('>') && previousSiblingText.startsWith('>')) {
					return;
				}

				if (findInstanceMatch(link.href)) {
					twintailLinks.push(link.href);
				}
			});

			if (twintailLinks.length === 0) {
				continue;
			}

			const thumbContainer = document.createElement('div');
			thumbContainer.className = 'twintail-thumbnail-container';
			thumbContainer.style.marginTop = '10px';

			for (const url of twintailLinks) {
				const candidates = getThumbnailCandidates(url);
				if (candidates.length === 0) {
					continue;
				}

				let workingThumbnailUrl = null;

				for (const candidate of candidates) {
					if (await thumbnailExists(candidate)) {
						workingThumbnailUrl = candidate;
						break;
					}
				}

				if (workingThumbnailUrl) {
					thumbContainer.appendChild(createThumbnail(workingThumbnailUrl, url));
				}
			}

			if (thumbContainer.childNodes.length > 0) {
				postContent.appendChild(thumbContainer);
			}
		}
	}

	function init() {
		addCheckbox();

		const checkbox = document.getElementById('enableThumbnails');
		if (!checkbox) {
			return;
		}

		checkbox.addEventListener('change', function() {
			const isChecked = checkbox.checked;
			localStorage.setItem('enableThumbnails', String(isChecked));

			if (isChecked) {
				runScript();
			} else {
				clearExistingThumbnailContainers();
			}
		});

		if (checkbox.checked) {
			runScript();
		}
	}

	init();
})();
