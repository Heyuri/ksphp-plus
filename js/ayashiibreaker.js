/* Thanks Anonymous-san from Strange World@Heyuri.net! */
/* Ayashii Breaker v0.4.0 */
/* Adds a button to instantly add line breaks to your post! */
/* v0.4.0: Added Japanese kinsoku shori (禁則処理) line breaking.
   Lines containing Japanese characters are broken by character count
   with kinsoku rules applied; other lines keep the original
   space-delimited word-wrap behavior. Multilingual: detection is
   per-line, so mixed-language posts are handled automatically. */
/*
                                       あやしいブレイク工業
 
 
                  _
                 ◎＼
                /X/X∥
               /X/X/∥
              /X/X/ ∥
             /X/X/  ∥
            /X/X/   ∥
           /X/X/    ∥
          /X/X/     ∥
         /X/X/      ∥                     [LONGPOST]
      __/X/X/       ∥                [LONGPOST][LONGPOST]
     / /X/X/ ￣|    ∥           [LONGPOST][LONGPOST][LONGPOST]
 ___/_/X/X/´ｰ`|    ∥        [LONGPOST][LONGPOST][LONGPOST][LONGPOST][LONGPOST]
|__σ＼/X/|____|    ●   [LONGPOST][LONGPOST][LONGPOST][LONGPOST][LONGPOST][LONGPOST]
 (◎◎◎◎)=))=)
  ~~~~~~~~￣ ￣
 
 
 
                Break alright Break alright Now we're ready for your BBS
               Dismantle away Dismantle away The just one rule we'll obey
          Conclusion of the duration is comin'up, Concrete is losin' its unity
           There are the delayers of buildin' our peaceful days Break'em out!
                    AYASHII Break KOGYO Smashin' steel ball Da Da Da!
     AYASHII Break KOGYO Chemical anchor bolt driven to beat&wave the hardest rock!!
            Any texts! Any lines! And any comments can't stop us in any way!
                      Going ahead! Going ahead! AYASHII Break KOGYO
 
             Break alright Break alright Terrible defective formatting mess
                Longposts, teh raegs and copy pasted texts over internets
Have you ever seen mighty skills to treat pile heads, rough clenched fists to support you
           By the justice, like a hammer, Yumbo swung to raise!! Break'em out!
                  AYASHII Break KOGYO Shinin' diamonded cutter Da Da Da
        AYASHII Break KOGYO Compressor roaring loud between the earth & the sky!!
            Any texts! Any lines! And any comments can't stop us in any way!
                     Going ahead! Going ahead! AYASHII Break KOGYO
*/
 
(function() {
	'use strict';

	// 行頭禁則: characters that must not start a line (get pulled back
	// onto the tail of the previous line instead).
	const KINSOKU_LEADING = "、。，．,.）」』】〕〉》」〙〗〟)]｝》〕、。！？!?ぁぃぅぇぉっゃゅょゎァィゥェォッャュョヮーゝゞ・：；:;";
	// 行末禁則: characters that must not end a line (get pushed onto
	// the head of the next line instead).
	const KINSOKU_TRAILING = "（「『【〔〈《〘〖〝([｛《〔";

	function isJapanese(text) {
		return /[\u3040-\u30ff\u3400-\u9fff\uff00-\uffef]/.test(text);
	}

	// Character-based line breaking with kinsoku shori, used for
	// lines that contain Japanese text (no spaces to break on).
	function breakJapaneseLine(line, maxLength) {
		const chars = Array.from(line);
		let rows = [];
		let current = [];
		for (const ch of chars) {
			current.push(ch);
			if (current.length >= maxLength) {
				rows.push(current);
				current = [];
			}
		}
		if (current.length > 0) rows.push(current);

		for (let i = 0; i < rows.length - 1; i++) {
			// 行頭禁則: pull disallowed leading characters back to
			// the end of the current row.
			while (rows[i + 1].length > 0 && KINSOKU_LEADING.includes(rows[i + 1][0])) {
				rows[i].push(rows[i + 1].shift());
			}
			// 行末禁則: push disallowed trailing characters forward
			// to the start of the next row.
			while (rows[i].length > 0 && KINSOKU_TRAILING.includes(rows[i][rows[i].length - 1])) {
				rows[i + 1].unshift(rows[i].pop());
			}
		}
		return rows.filter(r => r.length > 0).map(r => r.join(''));
	}

	function breaker() {
		const MAX_LENGTH = 72;
		let lines = document.getElementById('contents1').value.split('\n');
		let newlines = [];
		for (let i in lines) {
			if (lines[i].charAt(0) == ">") {
				newlines.push(lines[i]);
				continue;
			}
			if (isJapanese(lines[i])) {
				for (const row of breakJapaneseLine(lines[i], MAX_LENGTH)) {
					newlines.push(row);
				}
				continue;
			}
			let idx = 0;
			let words = lines[i].split(' ').filter(w => w.trim() != "");
			let newline = "";
			for (let word of words) {
				if (idx+word.length > MAX_LENGTH) {
					newline += '\n';
					idx = 0;
				}
				newline += word + ' ';
				idx += word.length + 1;
			}
			newlines.push(newline.trim());
		}
		document.getElementById('contents1').value = newlines.join('\n');
		checkLineLengths();
	}

function checkLineLengths() {
	const MAX_LENGTH = 72;
	const lines = document.getElementById('contents1').value.split('\n');
	let alert = false;

	for (const raw of lines) {
		const trimmedStart = raw.replace(/^\s+/, '');

		// skip quote lines (even if they have leading spaces)
		if (trimmedStart.startsWith('>')) continue;

		// ignore lines that are not too long
		if (raw.length <= MAX_LENGTH) continue;

		// Japanese lines are always breakable per-character, so the
		// "single unbreakable word" exemption below doesn't apply to them.
		if (!isJapanese(raw)) {
			// if any single word is longer than MAX_LENGTH, breaker can't help -> no alert
			const words = raw.split(' ').filter(w => w.trim() !== '');
			// suppress glow ONLY if the entire line is one single long unbreakable word
			if (words.length === 1 && words[0].length > MAX_LENGTH) continue;
		}

		// otherwise, this is a fixable overlong line -> glow
		alert = true;
		break;
	}

	const btn = document.getElementById("breakbutt");
	btn.classList.toggle('flash', alert);
}


	function addButton() {
		var element = document.querySelector('[title="Alt(+Shift)+K"]');
		var label = (window.KSPHP_LANG && window.KSPHP_LANG.MAKE_LINE_BREAKS_BTN) || "Make line breaks";
			var newElement = ' <input type="button" id="breakbutt" value="' + label + '"> '
		element.insertAdjacentHTML('afterend', newElement);
		document.getElementById("breakbutt").addEventListener("click", breaker, false);
		document.getElementById("contents1").addEventListener("input", checkLineLengths, false);
		injectStyles();
		checkLineLengths();
	}

	function injectStyles() {
		const style = document.createElement('style');
		style.textContent = `
#breakbutt.flash {
	animation: flashy 1s infinite;
}

@keyframes flashy {
	0% { background-color: red; color: white; }
	50% { background-color: darkred; color: white; }
	100% { background-color: red; color: white; }
}`;
		document.head.appendChild(style);
	}

	addButton();
})();
