(() => {
  const cfg = window.__DCOMC_EDITOR__;
  if (!cfg) return;

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const elTree = document.getElementById('tree');
  const elSearch = document.getElementById('treeSearch');
  const elPath = document.getElementById('currentPath');
  const elStatus = document.getElementById('status');
  const elDirty = document.getElementById('dirtyBadge');

  const btnSave = document.getElementById('btnSave');
  const btnNewFile = document.getElementById('btnNewFile');
  const btnNewFolder = document.getElementById('btnNewFolder');
  const btnRename = document.getElementById('btnRename');
  const btnDelete = document.getElementById('btnDelete');

  const setStatus = (t) => { if (elStatus) elStatus.textContent = t; };
  const setDirty = (d) => {
    if (!elDirty) return;
    elDirty.classList.toggle('hidden', !d);
    btnSave.disabled = !d || !state.currentPath;
  };

  const state = {
    expanded: new Set(),
    children: new Map(), // path -> items[]
    selected: null, // {path,type,name}
    currentPath: '',
    editor: null,
    model: null,
    dirty: false,
    filter: '',
  };

  async function apiGet(url, params) {
    const u = new URL(url, window.location.origin);
    Object.entries(params || {}).forEach(([k, v]) => u.searchParams.set(k, v ?? ''));
    const res = await fetch(u.toString(), { headers: { 'Accept': 'application/json' } });
    if (!res.ok) throw new Error((await res.json().catch(() => null))?.error || `HTTP ${res.status}`);
    return await res.json();
  }

  async function apiPost(url, body) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
      },
      body: JSON.stringify(body || {}),
    });
    if (!res.ok) throw new Error((await res.json().catch(() => null))?.error || `HTTP ${res.status}`);
    return await res.json();
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[c]));
  }

  function render() {
    if (!elTree) return;
    const roots = Array.from(document.querySelectorAll('.rootBtn')).map(b => b.getAttribute('data-root')).filter(Boolean);
    const q = (state.filter || '').toLowerCase().trim();

    const isMatch = (name) => !q || name.toLowerCase().includes(q);

    const lines = [];

    const renderNode = (path, name, type, depth) => {
      const isDir = type === 'dir';
      const exp = state.expanded.has(path);
      const sel = state.selected?.path === path;
      const pad = '&nbsp;'.repeat(depth * 4);
      const icon = isDir ? (exp ? '📂' : '📁') : '📄';
      const btn = `<button class="w-full text-left px-2 py-1 rounded ${sel ? 'bg-blue-100' : 'hover:bg-gray-100'}" data-path="${escapeHtml(path)}" data-type="${type}" title="${escapeHtml(path)}">${pad}${icon} ${escapeHtml(name)}</button>`;
      lines.push(btn);

      if (isDir && exp) {
        const kids = state.children.get(path) || [];
        for (const k of kids) {
          if (q && !isMatch(k.name) && k.type !== 'dir') continue;
          renderNode(k.path, k.name, k.type, depth + 1);
        }
      }
    };

    for (const r of roots) {
      renderNode(r, r, 'dir', 0);
    }

    elTree.innerHTML = `<div class="space-y-0.5">${lines.join('')}</div>`;
  }

  async function ensureChildren(path) {
    if (state.children.has(path)) return;
    setStatus(`Loading ${path}...`);
    const data = await apiGet(cfg.api.tree, { path });
    state.children.set(path, data.items || []);
    setStatus('Ready.');
  }

  async function toggleDir(path) {
    if (state.expanded.has(path)) {
      state.expanded.delete(path);
      render();
      return;
    }
    await ensureChildren(path);
    state.expanded.add(path);
    render();
  }

  function loadMonaco() {
    return new Promise((resolve) => {
      const loaderUrl = cfg.monaco?.loader;
      if (!loaderUrl) return resolve(null);

      const s = document.createElement('script');
      s.src = loaderUrl;
      s.onload = () => resolve(window.require || null);
      s.onerror = () => resolve(null);
      document.head.appendChild(s);
    });
  }

  function monacoLanguageToModel(lang) {
    if (!lang) return 'plaintext';
    return lang;
  }

  async function initEditor() {
    const req = await loadMonaco();
    if (!req) {
      document.getElementById('admin-editor-loading')?.classList.add('hidden');
      document.getElementById('monacoMissing')?.classList.remove('hidden');
      setStatus('Monaco missing.');
      return;
    }

    req.config({ paths: { vs: cfg.monaco.vsPath } });
    req(['vs/editor/editor.main'], () => {
      const monaco = window.monaco;
      state.model = monaco.editor.createModel('', 'plaintext');
      state.editor = monaco.editor.create(document.getElementById('editor'), {
        model: state.model,
        theme: 'vs',
        automaticLayout: true,
        fontSize: 13,
        minimap: { enabled: true },
        wordWrap: 'on',
        scrollBeyondLastLine: false,
      });

      state.model.onDidChangeContent(() => {
        state.dirty = true;
        setDirty(true);
      });

      setStatus('Editor ready.');
      document.getElementById('admin-editor-loading')?.classList.add('hidden');
    });
  }

  async function openFile(path) {
    if (!state.editor || !state.model) {
      setStatus('Editor not ready yet.');
      return;
    }
    if (state.dirty) {
      const ok = confirm('You have unsaved changes. Continue and discard them?');
      if (!ok) return;
    }

    setStatus(`Opening ${path}...`);
    const data = await apiGet(cfg.api.file, { path });

    const monaco = window.monaco;
    const lang = monacoLanguageToModel(data.language);
    monaco.editor.setModelLanguage(state.model, lang);
    state.model.setValue(data.content ?? '');

    state.currentPath = data.path;
    state.dirty = false;
    elPath.textContent = data.path;
    setDirty(false);
    btnRename.disabled = false;
    btnDelete.disabled = false;
    setStatus('Ready.');
  }

  async function saveFile() {
    if (!state.currentPath || !state.model) return;
    setStatus('Saving...');
    const content = state.model.getValue();
    await apiPost(cfg.api.write, { path: state.currentPath, content });
    state.dirty = false;
    setDirty(false);
    setStatus('Saved.');
    setTimeout(() => setStatus('Ready.'), 800);
  }

  async function createItem(type) {
    const base = state.selected?.type === 'dir' ? state.selected.path : (state.selected?.path ? state.selected.path.split('/').slice(0, -1).join('/') : '');
    const promptPath = prompt(type === 'dir' ? 'New folder path (example: app/NewFolder)' : 'New file path (example: app/NewFile.php)', base ? `${base}/` : '');
    if (!promptPath) return;
    setStatus('Creating...');
    await apiPost(cfg.api.create, { path: promptPath, type });
    // Refresh parent
    const parent = promptPath.split('/').slice(0, -1).join('/');
    state.children.delete(parent);
    await ensureChildren(parent || promptPath.split('/')[0]);
    state.expanded.add(parent || promptPath.split('/')[0]);
    render();
    setStatus('Created.');
    setTimeout(() => setStatus('Ready.'), 800);
  }

  async function renameSelected() {
    if (!state.selected) return;
    const to = prompt('Rename to (full path)', state.selected.path);
    if (!to || to === state.selected.path) return;
    setStatus('Renaming...');
    const r = await apiPost(cfg.api.rename, { from: state.selected.path, to });
    // Refresh parents
    const fromParent = r.from.split('/').slice(0, -1).join('/');
    const toParent = r.to.split('/').slice(0, -1).join('/');
    state.children.delete(fromParent);
    state.children.delete(toParent);
    render();
    if (state.currentPath === r.from) {
      state.currentPath = r.to;
      elPath.textContent = r.to;
    }
    setStatus('Renamed.');
    setTimeout(() => setStatus('Ready.'), 800);
  }

  async function deleteSelected() {
    if (!state.selected) return;
    const ok = confirm(`Delete ${state.selected.path}?`);
    if (!ok) return;
    setStatus('Deleting...');
    await apiPost(cfg.api.del, { path: state.selected.path });
    const parent = state.selected.path.split('/').slice(0, -1).join('/');
    state.children.delete(parent);
    state.selected = null;
    btnRename.disabled = true;
    btnDelete.disabled = true;
    render();
    setStatus('Deleted.');
    setTimeout(() => setStatus('Ready.'), 800);
  }

  function onTreeClick(e) {
    const btn = e.target.closest('button[data-path]');
    if (!btn) return;
    const path = btn.getAttribute('data-path');
    const type = btn.getAttribute('data-type');
    const name = btn.textContent?.trim() || path;
    state.selected = { path, type, name };
    btnRename.disabled = false;
    btnDelete.disabled = false;
    render();

    if (type === 'dir') {
      toggleDir(path).catch(err => { setStatus(err.message || 'Error'); });
    } else {
      openFile(path).catch(err => { setStatus(err.message || 'Error'); });
    }
  }

  function initRoots() {
    const rootBtns = document.querySelectorAll('.rootBtn');
    for (const b of rootBtns) {
      b.addEventListener('click', () => {
        const root = b.getAttribute('data-root');
        if (!root) return;
        state.selected = { path: root, type: 'dir', name: root };
        toggleDir(root).catch(err => setStatus(err.message || 'Error'));
      });
    }
  }

  function init() {
    elTree?.addEventListener('click', onTreeClick);
    elSearch?.addEventListener('input', () => {
      state.filter = elSearch.value || '';
      render();
    });

    btnSave?.addEventListener('click', () => saveFile().catch(err => setStatus(err.message || 'Error')));
    btnNewFile?.addEventListener('click', () => createItem('file').catch(err => setStatus(err.message || 'Error')));
    btnNewFolder?.addEventListener('click', () => createItem('dir').catch(err => setStatus(err.message || 'Error')));
    btnRename?.addEventListener('click', () => renameSelected().catch(err => setStatus(err.message || 'Error')));
    btnDelete?.addEventListener('click', () => deleteSelected().catch(err => setStatus(err.message || 'Error')));

    initRoots();
    render();
    initEditor();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

