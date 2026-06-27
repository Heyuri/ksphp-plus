"use strict";

const isJapanese = navigator.language.startsWith('ja');

const TEXT_LINK_START = isJapanese ? 'NG Word(' : 'Filtered words&nbsp;(';
const TEXT_LINK_HITS  = isJapanese ? '件hit)' : '&nbsp;hits)';
const TEXT_UPDATE     = isJapanese ? '更新' : 'Update';

function contains(array, elm) {
    for (var i = 0; i < array.length; i++) {
        if (array[i] === elm) return true;
    }
    return false;
}
function equals(lhs, rhs) {
    return (lhs === rhs) ? true : false;
}
function foreach(array, fun) {
    var stopIteration = Object();
    for (var i = 0; i < array.length; i++) {
        if (fun(array[i]) === stopIteration) break;
    }
}
function foreachClass(cls, fun) {
    var nodes = document.getElementsByClassName(cls);
    foreach(nodes, fun);
}
function foreachTag(tag, fun) {
    var nodes = document.getElementsByTagName(tag);
    foreach(nodes, fun);
}
function foreachTagWithClass(tag, cls, fun) {
    var pred = cls instanceof Array ? contains : equals;
    foreachTag(tag, function(e) {
        if (pred(cls, e.className)) return fun(e);
    });
}
var cookie = {
    get:function(n, m) { return (m = ('; ' + document.cookie + ';').match('; ' + n + '(.*?);')) ? decodeURIComponent(m[1]) : ''; },
    set:function(n, v) { document.cookie = n + '=' + encodeURIComponent(v) + '; expires=Mon, 31-Dec-2029 23:59:59 GMT'; }
};
var vanisher = {
    init:function() {
        var nodes = document.getElementsByClassName('ngset');
        if (nodes.length > 0) {
            vanisher.initNgWord(nodes[0]);
            vanisher.vanishNgWord(cookie.get('ngWord='));
        }
    },
    initNgWord:function(place) {
        var ngWord = cookie.get('ngWord=');
        var ngWordDisp = false;

        this.ngWordLink = document.createElement('a');
        this.ngWordLink.href = '#';
        this.ngWordLink.onclick = function(e) {
            vanisher.ngWordBox.style.display = ngWordDisp ? 'none' : '';
            ngWordDisp = !ngWordDisp;
            return false;
        };

        this.ngWordLink.appendChild(document.createTextNode(TEXT_LINK_START + this.ngWordCount + TEXT_LINK_HITS));
        place.appendChild(this.ngWordLink);
        place.appendChild(document.createTextNode('\u00a0\u00a0'));

        var ngWordText = document.createElement('input');
        ngWordText.size = 50;
        ngWordText.name = 'ngword';
        ngWordText.value = ngWord;
        ngWordText.type = 'text';
        ngWordText.onkeydown = function(e) {
            if (e.key === 'Enter') {
                var keyClick = document.getElementById("ngupdate");
                if (/*@cc_on ! @*/ false) {
                     // IE only
                    keyClick.fireEvent("onclick");
                } else {
                    var event = document.createEvent("MouseEvents");
                    event.initEvent("click", false, true);
                    keyClick.dispatchEvent(event);
                }
                return false;
            }
        };

        this.ngWordBox = document.createElement('span');
        this.ngWordBox.appendChild(ngWordText);
        this.ngWordBox.appendChild(document.createTextNode('\u00a0\u00a0'));
        this.ngWordBox.style.display = 'none';

        this.ngWordUpdate = document.createElement('a');
        this.ngWordUpdate.href = '#';
        this.ngWordUpdate.id = 'ngupdate';
        this.ngWordUpdate.onclick = function(e) {
            vanisher.vanishNgWord(ngWordText.value);
            cookie.set('ngWord', ngWordText.value);
            return false;
        };
        this.ngWordUpdate.appendChild(document.createTextNode(TEXT_UPDATE));
        this.ngWordBox.appendChild(this.ngWordUpdate);
        place.appendChild(this.ngWordBox);
    },
    vanishNgWord:function(ngWord) {
        this.ngWordCount = 0;
        if (ngWord !== '') {
            var ngWordReg = new RegExp(ngWord);
            foreachClass('ngline', function(e) {
                if (e.innerHTML.search(ngWordReg) !== -1) {			
                    e.style.display = 'none';
                    vanisher.ngWordCount++;
                } else {
                    e.style.display = '';
                }
            });
        }
        else
        {
            foreachClass('ngline', function(e) {
                e.style.display = '';
            });
        }
        this.ngWordLink.innerHTML = TEXT_LINK_START + this.ngWordCount + TEXT_LINK_HITS;
    },
    ngWordLink:null,
    ngWordBox:null,
    ngWordUpdate:null,
    ngWordCount:0
};
vanisher.init();
