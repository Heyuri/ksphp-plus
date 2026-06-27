(function () {
  'use strict';

  /* ---------- guard: only act on the actual deletion list ---------- */
  const CHECKBOX_SEL = 'input[type="checkbox"][name="x[]"]';
  const table = document.querySelector('table.postlists');
  if (!table || !table.querySelector(CHECKBOX_SEL)) return;
  const tbody = table.querySelector('tbody') || table;
  const thead = table.querySelector('thead');

  /* ---------- constants ---------- */
  const HIDDEN = 'swdh-hidden';
  const ARMED  = 'swdh-armed';

  /* ---------- language of the board ----------
     The EN board is also tagged <html lang="ja">, and navigator.language is the
     browser locale rather than the board's, so detect from the header labels. */
  const isJapanese = /ホスト|投稿者|投稿日/.test(thead ? thead.textContent : '');
  const T = isJapanese ? {
    filtering: '絞り込み中: ',
    host: 'ホスト', name: '投稿者',
    clear: '解除 ✕',
    armed: '始点を設定。投稿日をもう一度クリックで範囲選択',
  } : {
    filtering: 'Filtering by ',
    host: 'host', name: 'name',
    clear: 'clear ✕',
    armed: 'range start set — click another Date cell to finish',
  };

  /* ---------- styles ---------- */
  const css = document.createElement('style');
  css.textContent = `
    tr.${HIDDEN} { display: none !important; }
    tr.${ARMED} > td { background-color: #0f6b6b; }
    tr.${ARMED} > td:first-child { box-shadow: inset 4px 0 0 #1ee; }
    .swdh-bar { display: none; margin: 6px 0; padding: 5px 8px; border: 2px solid #c0c0c0;
                border-radius: 2px; background: #003434; color: #efefef; font-size: 13px; }
    .swdh-bar .tag { background: #007f7f; color: #fff; padding: 0 6px; border-radius: 2px; margin-right: 4px; }
    .swdh-bar code { color: #cfe; word-break: break-all; }
    .swdh-bar button { -webkit-appearance: none; appearance: none; border: 2px solid #999;
                       border-radius: 2px; background: #d2d2d2; padding: 0 .5em; margin-left: 8px; cursor: pointer; }
    .swdh-bar button:hover { background: #cdf; }
    .swdh-click { cursor: pointer; }
  `;
  document.head.appendChild(css);

  /* ---------- locate columns (EN + JP headers, with fallbacks) ----------
     Order: Delete | View | Date | Subject | Username | Host | Comment. */
  const COL = (function () {
    const ths = Array.from(table.querySelectorAll('thead th'));
    const find = (labels, fallback) => {
      const i = ths.findIndex(th =>
        labels.some(l => th.textContent.trim().toLowerCase() === l.toLowerCase()));
      return i >= 0 ? i : fallback;
    };
    return {
      date: find(['Date Posted', '投稿日'], 2),
      name: find(['Username', '投稿者'], 4),
      host: find(['Host', 'ホスト'], 5),
    };
  })();

  /* ---------- helpers ---------- */
  const cellText = (tr, idx) => {
    const td = tr.querySelectorAll('td')[idx];
    return td ? td.textContent.trim() : '';
  };
  const allCb = () => Array.from(table.querySelectorAll(CHECKBOX_SEL));
  const visCb = () => allCb().filter(cb => {
    const tr = cb.closest('tr');
    return tr && !tr.classList.contains(HIDDEN);
  });
  const esc = s => s.replace(/[&<>"']/g,
    c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));

  /* ---------- state ---------- */
  let active = { type: null, value: null };
  let rangeAnchorCb = null;   // pending date-range anchor
  let lastChecked   = null;   // for shift-range

  /* ---------- filtering: one type at a time ---------- */
  function applyFilter() {
    const col = active.type === 'host' ? COL.host : active.type === 'name' ? COL.name : -1;
    tbody.querySelectorAll('tr').forEach(tr => {
      if (!tr.querySelector(CHECKBOX_SEL)) return;
      const show = !active.value || cellText(tr, col) === active.value;
      tr.classList.toggle(HIDDEN, !show);
    });
    renderBar();
  }

  function setFilter(type, value) {
    if (!value) return;
    if (active.type === type && active.value === value) { clearFilter(); return; } // re-click clears
    active = { type, value };   // replaces any other filter
    applyFilter();
  }

  function clearFilter() {
    active = { type: null, value: null };
    applyFilter();
  }

  /* ---------- status bar (shown only when relevant) ---------- */
  const bar = document.createElement('div');
  bar.className = 'swdh-bar';
  table.parentNode.insertBefore(bar, table);

  function renderBar() {
    let html = '';
    if (active.value) {
      const label = active.type === 'host' ? T.host : T.name;
      html = `${T.filtering}<span class="tag">${label}</span><code>${esc(active.value)}</code>`
           + `<button type="button" data-act="clear">${T.clear}</button>`;
    }
    if (rangeAnchorCb) html += `${html ? ' &nbsp; ' : ''}<span class="tag">${T.armed}</span>`;
    bar.innerHTML = html;
    bar.style.display = html ? 'block' : 'none';
    const btn = bar.querySelector('[data-act="clear"]');
    if (btn) btn.addEventListener('click', clearFilter);
  }

  /* ---------- selection wiring ---------- */
  function setArmed(cb) {
    if (rangeAnchorCb) { const p = rangeAnchorCb.closest('tr'); if (p) p.classList.remove(ARMED); }
    rangeAnchorCb = cb;
    if (cb) { const r = cb.closest('tr'); if (r) r.classList.add(ARMED); }
    renderBar();
  }

  function wireRow(tr) {
    if (tr.dataset.swdhRow) return;
    const cb = tr.querySelector(CHECKBOX_SEL);
    if (!cb) return;
    tr.dataset.swdhRow = '1';

    const tds = tr.querySelectorAll('td');
    const dateCell = tds[COL.date], nameCell = tds[COL.name], hostCell = tds[COL.host];

    // Date cell -> two-click range select (only adds checks)
    if (dateCell) {
      dateCell.classList.add('swdh-click');
      dateCell.addEventListener('click', e => {
        e.stopPropagation();
        if (!rangeAnchorCb) { cb.checked = true; setArmed(cb); return; }
        const vis = visCb();
        const a = vis.indexOf(rangeAnchorCb), b = vis.indexOf(cb);
        if (a === -1) { cb.checked = true; setArmed(cb); return; } // anchor hidden -> restart
        const lo = Math.min(a, b), hi = Math.max(a, b);
        vis.slice(lo, hi + 1).forEach(x => x.checked = true);
        setArmed(null);
      });
    }

    // Shift-click range on the checkbox
    cb.addEventListener('click', e => {
      const vis = visCb();
      if (e.shiftKey && lastChecked) {
        const a = vis.indexOf(lastChecked), b = vis.indexOf(cb);
        if (a !== -1 && b !== -1) {
          const lo = Math.min(a, b), hi = Math.max(a, b);
          vis.slice(lo, hi + 1).forEach(x => x.checked = true);
        }
      }
      lastChecked = cb;
    });

    // Host cell -> filter by that IP/host
    if (hostCell) {
      hostCell.classList.add('swdh-click');
      hostCell.addEventListener('click', () => setFilter('host', cellText(tr, COL.host)));
    }
    // Username cell -> filter by that name
    if (nameCell) {
      nameCell.classList.add('swdh-click');
      nameCell.addEventListener('click', () => setFilter('name', cellText(tr, COL.name)));
    }
  }
  const wireRows = () => tbody.querySelectorAll('tr').forEach(wireRow);

  /* ---------- init ---------- */
  wireRows();
})();