(function () {
  const modal = document.getElementById('media-picker');
  if (!modal) return;

  const grid = modal.querySelector('[data-media-grid]');
  const fileInput = modal.querySelector('[data-media-upload]');
  const targetInput = { current: null, preview: null };
  let items = [];

  const closeModal = () => {
    modal.hidden = true;
    document.body.classList.remove('media-picker-open');
  };

  const renderGrid = () => {
    if (!grid) return;
    grid.innerHTML = '';
    items.forEach((item) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'media-picker__item';
      btn.innerHTML = `<img src="${item.url}" alt="${item.name}" loading="lazy"><span>${item.name}</span>`;
      btn.addEventListener('click', () => {
        if (targetInput.current) targetInput.current.value = item.path;
        if (targetInput.preview) {
          targetInput.preview.src = item.url;
          targetInput.preview.hidden = false;
        }
        const field = targetInput.current?.closest('[data-media-field]');
        field?.querySelector('[data-media-placeholder]')?.setAttribute('hidden', 'hidden');
        closeModal();
      });
      grid.appendChild(btn);
    });
  };

  const loadItems = async () => {
    const res = await fetch(window.ADMIN_MEDIA_LIST_URL, { credentials: 'same-origin' });
    const data = await res.json();
    items = Array.isArray(data.items) ? data.items : [];
    renderGrid();
  };

  document.querySelectorAll('[data-media-open]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const field = btn.closest('[data-media-field]');
      if (!field) return;
      targetInput.current = field.querySelector('[data-media-input]');
      targetInput.preview = field.querySelector('[data-media-preview]');
      modal.hidden = false;
      document.body.classList.add('media-picker-open');
      await loadItems();
    });
  });

  document.querySelectorAll('[data-media-clear]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const field = btn.closest('[data-media-field]');
      if (!field) return;
      const input = field.querySelector('[data-media-input]');
      const preview = field.querySelector('[data-media-preview]');
      const placeholder = field.querySelector('[data-media-placeholder]');
      if (input) input.value = '';
      if (preview) {
        preview.hidden = true;
        preview.removeAttribute('src');
      }
      placeholder?.removeAttribute('hidden');
    });
  });

  modal.querySelectorAll('[data-media-close]').forEach((el) => {
    el.addEventListener('click', closeModal);
  });

  if (fileInput) {
    fileInput.addEventListener('change', async () => {
      const file = fileInput.files && fileInput.files[0];
      if (!file) return;
      const fd = new FormData();
      fd.append('action', 'upload');
      fd.append('_csrf', window.ADMIN_CSRF || '');
      fd.append('file', file);
      const res = await fetch(window.ADMIN_MEDIA_UPLOAD_URL, {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
      });
      const data = await res.json();
      if (data.ok && data.path) {
        if (targetInput.current) targetInput.current.value = data.path;
        if (targetInput.preview) {
          targetInput.preview.src = data.url;
          targetInput.preview.hidden = false;
        }
        targetInput.current?.closest('[data-media-field]')?.querySelector('[data-media-placeholder]')?.setAttribute('hidden', 'hidden');
        await loadItems();
        closeModal();
      } else {
        alert(data.error || 'Upload impossible.');
      }
      fileInput.value = '';
    });
  }
})();
