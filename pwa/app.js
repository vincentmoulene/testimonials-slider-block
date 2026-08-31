/* Lanceur PWA — pwa.vincentmoulene.fr
   Vanilla JS, aucune dépendance, aucun build. */
(function () {
  'use strict';

  var VERSION = '1.0.0';
  var STORE_KEY = 'vm-pwa.apps.v1';
  var HINT_KEY = 'vm-pwa.hint.v1';

  var COLORS = [
    '#4f46e5', '#0ea5e9', '#10b981', '#f59e0b',
    '#ef4444', '#ec4899', '#8b5cf6', '#334155'
  ];

  var $ = function (sel) { return document.querySelector(sel); };

  var grid = $('#grid');
  var empty = $('#empty');
  var noResult = $('#no-result');
  var search = $('#search');
  var sheetApp = $('#sheet-app');
  var sheetSettings = $('#sheet-settings');
  var backdrop = $('#sheet-backdrop');
  var formApp = $('#form-app');
  var btnDelete = $('#btn-delete');
  var toastEl = $('#toast');

  var apps = [];
  var editing = false;
  var query = '';
  var currentId = null;   // app en cours d'édition, null = création
  var currentColor = null; // null = couleur automatique

  /* ---------- Stockage ---------- */

  function readStore() {
    try {
      var raw = localStorage.getItem(STORE_KEY);
      if (!raw) return null;
      var data = JSON.parse(raw);
      return Array.isArray(data) ? data : (data && data.apps) || null;
    } catch (e) {
      return null;
    }
  }

  function save() {
    try {
      localStorage.setItem(STORE_KEY, JSON.stringify({ version: 1, apps: apps }));
    } catch (e) {
      toast('Sauvegarde impossible (stockage plein ou navigation privée).');
    }
  }

  function uid() {
    return 'a' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
  }

  /* ---------- Utilitaires ---------- */

  function normalizeUrl(value) {
    var v = String(value || '').trim();
    if (!v) return '';
    if (!/^https?:\/\//i.test(v)) v = 'https://' + v.replace(/^\/+/, '');
    try {
      return new URL(v).href;
    } catch (e) {
      return '';
    }
  }

  function hostOf(url) {
    try {
      return new URL(url).hostname.replace(/^www\./, '');
    } catch (e) {
      return url;
    }
  }

  function nameFromUrl(url) {
    var host = hostOf(url);
    var label = host.split('.')[0] || host;
    return label.charAt(0).toUpperCase() + label.slice(1);
  }

  function hueOf(str) {
    var h = 0;
    for (var i = 0; i < str.length; i++) h = (h * 31 + str.charCodeAt(i)) % 360;
    return h;
  }

  function tileBackground(app) {
    if (app.color) {
      return 'linear-gradient(160deg, rgba(255,255,255,.20), rgba(0,0,0,.16)), ' + app.color;
    }
    var h = hueOf(hostOf(app.url));
    return 'linear-gradient(150deg, hsl(' + h + ' 72% 58%), hsl(' + ((h + 32) % 360) + ' 70% 44%))';
  }

  function initials(name) {
    var parts = String(name || '?').trim().split(/[\s._-]+/).filter(Boolean);
    if (parts.length > 1) return (parts[0][0] + parts[1][0]).toUpperCase();
    return (parts[0] || '?').slice(0, 2).toUpperCase();
  }

  function isImage(icon) {
    return /^https?:\/\//i.test(icon || '');
  }

  var toastTimer;
  function toast(message, actionLabel, onAction) {
    clearTimeout(toastTimer);
    toastEl.textContent = message;
    if (actionLabel) {
      var b = document.createElement('button');
      b.type = 'button';
      b.textContent = actionLabel;
      b.addEventListener('click', onAction);
      toastEl.appendChild(b);
    }
    toastEl.hidden = false;
    toastTimer = setTimeout(function () { toastEl.hidden = true; }, actionLabel ? 12000 : 3200);
  }

  /* ---------- Rendu ---------- */

  function matches(app) {
    if (!query) return true;
    var q = query.toLowerCase();
    return app.name.toLowerCase().indexOf(q) !== -1 || app.url.toLowerCase().indexOf(q) !== -1;
  }

  function render() {
    grid.innerHTML = '';
    var visible = apps.filter(matches);

    visible.forEach(function (app) {
      var tile = document.createElement('a');
      tile.className = 'tile';
      tile.href = app.url;
      tile.dataset.id = app.id;
      tile.setAttribute('role', 'listitem');
      if (app.mode !== 'browser') {
        tile.target = '_blank';
        tile.rel = 'noopener noreferrer';
      }

      var icon = document.createElement('span');
      icon.className = 'tile-icon';
      icon.style.background = tileBackground(app);
      if (isImage(app.icon)) {
        var img = document.createElement('img');
        img.src = app.icon;
        img.alt = '';
        img.loading = 'lazy';
        img.addEventListener('error', function () {
          icon.textContent = initials(app.name);
        });
        icon.appendChild(img);
      } else {
        icon.textContent = (app.icon && app.icon.trim()) || initials(app.name);
      }

      var label = document.createElement('span');
      label.className = 'tile-name';
      label.textContent = app.name;

      var remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'tile-remove';
      remove.textContent = '−';
      remove.setAttribute('aria-label', 'Supprimer ' + app.name);
      remove.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        removeApp(app.id);
      });

      tile.append(remove, icon, label);
      tile.addEventListener('click', function (event) {
        if (editing) {
          event.preventDefault();
          openAppSheet(app.id);
        }
      });

      grid.appendChild(tile);
    });

    empty.hidden = apps.length !== 0;
    noResult.hidden = !(apps.length > 0 && visible.length === 0);
    grid.hidden = visible.length === 0;
    $('#btn-edit').hidden = apps.length === 0;
  }

  function removeApp(id) {
    var app = apps.filter(function (a) { return a.id === id; })[0];
    if (!app) return;
    var index = apps.indexOf(app);
    apps.splice(index, 1);
    save();
    render();
    toast('« ' + app.name + ' » supprimée', 'Annuler', function () {
      apps.splice(index, 0, app);
      save();
      render();
      toastEl.hidden = true;
    });
  }

  /* ---------- Feuilles ---------- */

  function openSheet(sheet) {
    backdrop.hidden = false;
    sheet.hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function closeSheets() {
    backdrop.hidden = true;
    sheetApp.hidden = true;
    sheetSettings.hidden = true;
    document.body.style.overflow = '';
  }

  function buildSwatches() {
    var box = $('#swatches');
    box.innerHTML = '';
    var values = [null].concat(COLORS);
    values.forEach(function (color) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'swatch';
      b.setAttribute('role', 'radio');
      b.dataset.color = color || '';
      b.title = color ? color : 'Automatique';
      b.setAttribute('aria-label', color ? 'Couleur ' + color : 'Couleur automatique');
      b.style.background = color
        ? color
        : 'conic-gradient(#ef4444, #f59e0b, #10b981, #0ea5e9, #8b5cf6, #ef4444)';
      b.addEventListener('click', function () {
        currentColor = color;
        syncSwatches();
      });
      box.appendChild(b);
    });
  }

  function syncSwatches() {
    Array.prototype.forEach.call(document.querySelectorAll('.swatch'), function (b) {
      b.setAttribute('aria-checked', String((b.dataset.color || null) === currentColor));
    });
  }

  function openAppSheet(id) {
    currentId = id || null;
    var app = id ? apps.filter(function (a) { return a.id === id; })[0] : null;
    $('#sheet-app-title').textContent = app ? 'Modifier' : 'Nouvelle app';
    $('#f-url').value = app ? app.url : '';
    $('#f-name').value = app ? app.name : '';
    $('#f-icon').value = app ? (app.icon || '') : '';
    $('#f-mode').value = app && app.mode === 'browser' ? 'browser' : 'overlay';
    currentColor = app ? (app.color || null) : null;
    syncSwatches();
    btnDelete.hidden = !app;
    openSheet(sheetApp);
    if (!app) setTimeout(function () { $('#f-url').focus(); }, 250);
  }

  formApp.addEventListener('submit', function (event) {
    event.preventDefault();
    var url = normalizeUrl($('#f-url').value);
    if (!url) {
      $('#f-url').classList.add('touched');
      toast('Adresse invalide.');
      return;
    }
    var name = $('#f-name').value.trim() || nameFromUrl(url);
    var data = {
      name: name,
      url: url,
      icon: $('#f-icon').value.trim(),
      color: currentColor,
      mode: $('#f-mode').value
    };
    if (currentId) {
      apps.forEach(function (a) {
        if (a.id === currentId) {
          a.name = data.name;
          a.url = data.url;
          a.icon = data.icon;
          a.color = data.color;
          a.mode = data.mode;
        }
      });
    } else {
      data.id = uid();
      apps.push(data);
    }
    save();
    render();
    closeSheets();
  });

  // Nom pré-rempli à partir de l'URL tant que l'utilisateur n'a rien saisi.
  $('#f-url').addEventListener('change', function () {
    var nameField = $('#f-name');
    if (nameField.value.trim()) return;
    var url = normalizeUrl(this.value);
    if (url) nameField.value = nameFromUrl(url);
  });

  btnDelete.addEventListener('click', function () {
    if (currentId) removeApp(currentId);
    closeSheets();
  });

  function openSettings() {
    $('#f-json').value = JSON.stringify({ version: 1, apps: apps }, null, 2);
    $('#f-bulk').value = '';
    $('#version').textContent = 'v' + VERSION;
    openSheet(sheetSettings);
  }

  /* ---------- Ajout groupé / import ---------- */

  function bulkAdd(text) {
    var added = 0;
    String(text || '').split(/[\n,]+/).forEach(function (line) {
      line = line.trim();
      if (!line) return;
      var name = '';
      var rest = line;
      var pipe = line.indexOf('|');
      if (pipe !== -1) {
        name = line.slice(0, pipe).trim();
        rest = line.slice(pipe + 1).trim();
      }
      var url = normalizeUrl(rest);
      if (!url) return;
      apps.push({
        id: uid(),
        name: name || nameFromUrl(url),
        url: url,
        icon: '',
        color: null,
        mode: 'overlay'
      });
      added++;
    });
    if (!added) {
      toast('Aucune adresse reconnue.');
      return;
    }
    save();
    render();
    closeSheets();
    toast(added + (added > 1 ? ' applications ajoutées' : ' application ajoutée'));
  }

  function restoreJson(text) {
    try {
      var data = JSON.parse(text);
      var list = Array.isArray(data) ? data : data.apps;
      if (!Array.isArray(list)) throw new Error('format');
      apps = list.filter(function (a) { return a && a.url; }).map(function (a) {
        return {
          id: a.id || uid(),
          name: a.name || nameFromUrl(a.url),
          url: normalizeUrl(a.url),
          icon: a.icon || '',
          color: a.color || null,
          mode: a.mode === 'browser' ? 'browser' : 'overlay'
        };
      });
      save();
      render();
      closeSheets();
      toast('Liste restaurée.');
    } catch (e) {
      toast('JSON invalide.');
    }
  }

  function loadDefaults(force) {
    return fetch('./apps.json', { cache: force ? 'reload' : 'default' })
      .then(function (r) { return r.ok ? r.json() : { apps: [] }; })
      .then(function (data) {
        var list = (data && data.apps) || [];
        return list.filter(function (a) { return a && a.url; }).map(function (a) {
          return {
            id: a.id || uid(),
            name: a.name || nameFromUrl(a.url),
            url: normalizeUrl(a.url),
            icon: a.icon || '',
            color: a.color || null,
            mode: a.mode === 'browser' ? 'browser' : 'overlay'
          };
        });
      })
      .catch(function () { return []; });
  }

  /* ---------- Réorganisation par glisser-déposer (mode Modifier) ---------- */

  var drag = null;

  grid.addEventListener('pointerdown', function (event) {
    if (!editing || event.button > 0) return;
    var tile = event.target.closest('.tile');
    if (!tile || event.target.closest('.tile-remove')) return;
    drag = {
      tile: tile,
      id: tile.dataset.id,
      startX: event.clientX,
      startY: event.clientY,
      moved: false,
      ghost: null,
      pointerId: event.pointerId
    };
  });

  window.addEventListener('pointermove', function (event) {
    if (!drag || event.pointerId !== drag.pointerId) return;
    var dx = event.clientX - drag.startX;
    var dy = event.clientY - drag.startY;

    if (!drag.moved) {
      if (Math.abs(dx) + Math.abs(dy) < 10) return;
      drag.moved = true;
      var rect = drag.tile.getBoundingClientRect();
      var ghost = drag.tile.cloneNode(true);
      ghost.classList.add('drag-ghost');
      ghost.style.width = rect.width + 'px';
      ghost.style.left = rect.left + 'px';
      ghost.style.top = rect.top + 'px';
      document.body.appendChild(ghost);
      drag.ghost = ghost;
      drag.offsetX = rect.left;
      drag.offsetY = rect.top;
      drag.tile.classList.add('dragging');
    }

    event.preventDefault();
    drag.ghost.style.left = (drag.offsetX + dx) + 'px';
    drag.ghost.style.top = (drag.offsetY + dy) + 'px';

    // Cible = vignette dont le centre est le plus proche du pointeur.
    var tiles = Array.prototype.slice.call(grid.querySelectorAll('.tile'));
    var best = null;
    var bestDist = Infinity;
    tiles.forEach(function (t) {
      if (t === drag.tile) return;
      var r = t.getBoundingClientRect();
      var d = Math.hypot(event.clientX - (r.left + r.width / 2), event.clientY - (r.top + r.height / 2));
      if (d < bestDist) { bestDist = d; best = t; }
    });
    if (best && bestDist < 90) {
      var order = tiles.indexOf(best) < tiles.indexOf(drag.tile) ? 'beforebegin' : 'afterend';
      best.insertAdjacentElement(order, drag.tile);
    }
  }, { passive: false });

  function endDrag() {
    if (!drag) return;
    if (drag.moved) {
      if (drag.ghost) drag.ghost.remove();
      drag.tile.classList.remove('dragging');
      // L'ordre du DOM fait foi (la recherche est vide en mode Modifier).
      var ids = Array.prototype.map.call(grid.querySelectorAll('.tile'), function (t) { return t.dataset.id; });
      apps.sort(function (a, b) { return ids.indexOf(a.id) - ids.indexOf(b.id); });
      save();
    }
    drag = null;
  }

  window.addEventListener('pointerup', endDrag);
  window.addEventListener('pointercancel', endDrag);

  /* ---------- Événements globaux ---------- */

  $('#btn-edit').addEventListener('click', function () {
    editing = !editing;
    this.setAttribute('aria-pressed', String(editing));
    this.textContent = editing ? 'OK' : 'Modifier';
    document.body.classList.toggle('editing', editing);
    if (editing && query) {           // le glisser-déposer suppose la liste complète
      query = '';
      search.value = '';
      render();
    }
  });

  $('#btn-settings').addEventListener('click', openSettings);
  $('#fab').addEventListener('click', function () { openAppSheet(null); });
  backdrop.addEventListener('click', closeSheets);

  search.addEventListener('input', function () {
    query = this.value.trim();
    render();
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeSheets();
  });

  document.addEventListener('click', function (event) {
    var target = event.target.closest('[data-action]');
    if (!target) return;
    var action = target.dataset.action;

    if (action === 'close') closeSheets();
    if (action === 'add') openAppSheet(null);
    if (action === 'bulk') { closeSheets(); openSettings(); setTimeout(function () { $('#f-bulk').focus(); }, 250); }
    if (action === 'bulk-add') bulkAdd($('#f-bulk').value);
    if (action === 'restore-json') restoreJson($('#f-json').value);
    if (action === 'copy-json') {
      var text = $('#f-json').value;
      if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function () { toast('Sauvegarde copiée.'); },
          function () { toast('Copie refusée par le navigateur.'); });
      } else {
        $('#f-json').select();
        toast('Sélectionné : utilisez Copier.');
      }
    }
    if (action === 'reset') {
      loadDefaults(true).then(function (list) {
        apps = list;
        save();
        render();
        closeSheets();
        toast(list.length ? 'Liste réinitialisée.' : 'apps.json est vide.');
      });
    }
    if (action === 'update') checkForUpdate();
    if (action === 'dismiss-hint') {
      try { localStorage.setItem(HINT_KEY, '1'); } catch (e) {}
      $('#ios-hint').hidden = true;
    }
  });

  /* ---------- Bandeau « installer sur l'iPhone » ---------- */

  function maybeShowHint() {
    var standalone = window.matchMedia('(display-mode: standalone)').matches ||
      window.navigator.standalone === true;
    var dismissed = false;
    try { dismissed = localStorage.getItem(HINT_KEY) === '1'; } catch (e) {}
    var isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) ||
      (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    $('#ios-hint').hidden = standalone || dismissed || !isIOS;
  }

  /* ---------- Service worker ---------- */

  var refreshing = false;

  function checkForUpdate() {
    if (!('serviceWorker' in navigator)) return;
    navigator.serviceWorker.getRegistration().then(function (reg) {
      if (!reg) return;
      reg.update().then(function () {
        if (!reg.installing && !reg.waiting) toast('Déjà à jour.');
      });
    });
  }

  function watchWorker(reg) {
    function offerReload(worker) {
      if (!worker) return;
      worker.addEventListener('statechange', function () {
        if (worker.state === 'installed' && navigator.serviceWorker.controller) {
          toast('Mise à jour disponible', 'Recharger', function () {
            worker.postMessage({ type: 'SKIP_WAITING' });
          });
        }
      });
    }
    if (reg.waiting && navigator.serviceWorker.controller) {
      toast('Mise à jour disponible', 'Recharger', function () {
        reg.waiting.postMessage({ type: 'SKIP_WAITING' });
      });
    }
    offerReload(reg.installing);
    reg.addEventListener('updatefound', function () { offerReload(reg.installing); });
  }

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('./sw.js').then(watchWorker).catch(function () {});
    });
    navigator.serviceWorker.addEventListener('controllerchange', function () {
      if (refreshing) return;
      refreshing = true;
      window.location.reload();
    });
  }

  /* ---------- Démarrage ---------- */

  buildSwatches();
  syncSwatches();

  var stored = readStore();
  if (stored) {
    apps = stored;
    render();
  } else {
    render();
    loadDefaults(false).then(function (list) {
      apps = list;
      if (list.length) save();
      render();
    });
  }
  maybeShowHint();
})();
