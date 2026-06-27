/* Thanks Anonymous-san from Strange World@Heyuri.net! */
/* Ayashii Breaker v0.3.1 */
/* Adds a button to instantly add line breaks to your post! */
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

	function breaker() {
		const MAX_LENGTH = 72;
		let lines = document.getElementById('contents1').value.split('\n');
		let newlines = [];
		for (let i in lines) {
			if (lines[i].charAt(0) == ">") {
				newlines.push(lines[i]);
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

		// if any single word is longer than MAX_LENGTH, breaker can't help -> no alert
		const words = raw.split(' ').filter(w => w.trim() !== '');
		// suppress glow ONLY if the entire line is one single long unbreakable word
		if (words.length === 1 && words[0].length > MAX_LENGTH) continue;

		// otherwise, this is a fixable overlong line -> glow
		alert = true;
		break;
	}

	const btn = document.getElementById("breakbutt");
	btn.classList.toggle('flash', alert);
}


	function addButton() {
		var element = document.querySelector('[title="Alt(+Shift)+K"]');
		var newElement = ' <input type="button" id="breakbutt" value="Make line breaks"> '
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
