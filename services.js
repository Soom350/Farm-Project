// Our Services: scroll reveal + premium hover highlight (Vanilla JS)
document.addEventListener('DOMContentLoaded', () => {
    const revealItems = document.querySelectorAll('[data-reveal]');
    if (!revealItems.length) return;

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Reveal (scroll)
    if (prefersReducedMotion) {
        revealItems.forEach((el) => el.classList.add('is-revealed'));
    } else {
        const observer = new IntersectionObserver(
            (entries, obs) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add('is-revealed');
                    obs.unobserve(entry.target);
                });
            },
            {
                threshold: 0.15,
                rootMargin: '0px 0px -10% 0px',
            }
        );

        revealItems.forEach((el) => observer.observe(el));
        
    }

    // Hover highlight (pointer-driven gradient position)
    const cards = document.querySelectorAll('.service-card');
    if (!cards.length) return;

    // Pointer-driven hover highlight (desktop)
    if (!prefersReducedMotion) {
        cards.forEach((card) => {
        let rect = null;
        let raf = 0;

        function setVars(clientX, clientY) {
            if (!rect) rect = card.getBoundingClientRect();
            const x = ((clientX - rect.left) / rect.width) * 100;
            const y = ((clientY - rect.top) / rect.height) * 100;
            card.style.setProperty('--mx', `${Math.min(100, Math.max(0, x))}%`);
            card.style.setProperty('--my', `${Math.min(100, Math.max(0, y))}%`);
        }

        function onEnter() {
            rect = card.getBoundingClientRect();
        }

        function onMove(e) {
            if (raf) cancelAnimationFrame(raf);
            raf = requestAnimationFrame(() => setVars(e.clientX, e.clientY));
        }

        function onLeave() {
            rect = null;
            card.style.setProperty('--mx', '50%');
            card.style.setProperty('--my', '25%');
        }

        card.addEventListener('pointerenter', onEnter);
        card.addEventListener('pointermove', onMove);
        card.addEventListener('pointerleave', onLeave);
        });
    }

    // Mobile progressive disclosure (tap to expand)
    const mql = window.matchMedia('(max-width: 768px)');
    const toggles = document.querySelectorAll('.service-card__toggle');

    function resetExpanded() {
        document.querySelectorAll('.service-card.is-expanded').forEach((c) => c.classList.remove('is-expanded'));
        toggles.forEach((btn) => {
            btn.setAttribute('aria-expanded', 'false');
            btn.textContent = 'View details';
        });
    }

    function onToggleClick(e) {
        const btn = e.currentTarget;
        const card = btn.closest('.service-card');
        if (!card) return;

        // Only active on mobile breakpoint
        if (!mql.matches) return;

        // Close other cards for clarity on mobile
        document.querySelectorAll('.service-card.is-expanded').forEach((c) => {
            if (c === card) return;
            c.classList.remove('is-expanded');
            const otherBtn = c.querySelector('.service-card__toggle');
            if (otherBtn) {
                otherBtn.setAttribute('aria-expanded', 'false');
                otherBtn.textContent = 'View details';
            }
        });

        const isExpanded = card.classList.toggle('is-expanded');
        btn.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        btn.textContent = isExpanded ? 'Hide details' : 'View details';
    }

    toggles.forEach((btn) => btn.addEventListener('click', onToggleClick));
    mql.addEventListener('change', () => {
        if (!mql.matches) resetExpanded();
    });
});





// Shop UX: quantity, product modal, cart drawer, toast (Vanilla JS)
document.addEventListener('DOMContentLoaded', () => {
    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  
    function formatMoney(amount) {
      try {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
      } catch {
        return `$${amount.toFixed(2)}`;
      }
    }
  
    function clamp(n, min, max) {
      return Math.max(min, Math.min(max, n));
    }
  
    // ------- Toast -------
    const toast = $('#toast');
    let toastTimer = 0;
    function showToast(message) {
      if (!toast) return;
      toast.textContent = message;
      toast.hidden = false;
      toast.classList.add('is-visible');
      clearTimeout(toastTimer);
      toastTimer = window.setTimeout(() => {
        toast.classList.remove('is-visible');
        toast.hidden = true;
      }, prefersReducedMotion ? 1200 : 2200);
    }
  
    // ------- Backdrop -------
    const backdrop = $('[data-backdrop]');
    function setBackdrop(open) {
      if (!backdrop) return;
      backdrop.hidden = !open;
      backdrop.classList.toggle('is-visible', open);
    }
    function isCartOpen() {
      return !!(cartDrawer && !cartDrawer.hidden && cartDrawer.classList.contains('is-open'));
    }
    function isModalOpen() {
      return !!(modal && !modal.hidden && modal.classList.contains('is-open'));
    }
    function syncBackdrop() {
      setBackdrop(isCartOpen() || isModalOpen());
    }
  
    // ------- Focus trap -------
    function getFocusable(container) {
      return $$(
        'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
        container
      ).filter((el) => !el.hasAttribute('disabled') && !el.getAttribute('aria-hidden'));
    }
  
    function trapFocus(container, onClose) {
      const focusables = getFocusable(container);
      const first = focusables[0] || container;
      const last = focusables[focusables.length - 1] || container;
      function onKeyDown(e) {
        if (e.key === 'Escape') {
          e.preventDefault();
          onClose?.();
          return;
        }
        if (e.key !== 'Tab') return;
        if (focusables.length === 0) return;
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
      container.addEventListener('keydown', onKeyDown);
      first.focus();
      return () => container.removeEventListener('keydown', onKeyDown);
    }
  
    // ------- Products data -------
    const productCards = $$('[data-product-id]');
    const products = new Map();
  
    productCards.forEach((card) => {
      const id = card.getAttribute('data-product-id');
      if (!id) return;
      products.set(id, {
        id,
        name: card.getAttribute('data-name') || 'Product',
        price: Number(card.getAttribute('data-price') || '0'),
        unit: card.getAttribute('data-unit') || '',
        stock: Number(card.getAttribute('data-stock') || '0'),
        image: card.getAttribute('data-image') || '',
      });
    });
  
    // ------- Cart state -------
    const cart = new Map(); // id -> qty
  
    const cartDrawer = $('#cart');
    const cartItemsEl = $('[data-cart-items]');
    const cartEmptyEl = $('[data-cart-empty]');
    const cartTotalEl = $('[data-cart-total]');
    const cartCountEl = $('[data-cart-count]');
    const cartOpenBtn = $('[data-cart-open]');
    const cartCloseBtn = $('[data-cart-close]');
    const checkoutBtn = $('[data-checkout]');
  
    let releaseTrap = null;
    let lastFocused = null;
  
    function getCartCount() {
      let count = 0;
      cart.forEach((qty) => (count += qty));
      return count;
    }
  
    function getCartTotal() {
      let total = 0;
      cart.forEach((qty, id) => {
        const p = products.get(id);
        if (!p) return;
        total += p.price * qty;
      });
      return total;
    }
  
    function renderCart() {
      const count = getCartCount();
      if (cartCountEl) cartCountEl.textContent = String(count);
  
      const hasItems = count > 0;
      if (cartEmptyEl) cartEmptyEl.hidden = hasItems;
      if (cartItemsEl) cartItemsEl.innerHTML = '';
  
      if (cartItemsEl) {
        cart.forEach((qty, id) => {
          const p = products.get(id);
          if (!p) return;
          const li = document.createElement('li');
          li.className = 'cart-item';
          li.innerHTML = `
            <div class="cart-item__main">
              <img class="cart-item__img" src="${p.image}" alt="" aria-hidden="true" width="64" height="64" loading="lazy">
              <div class="cart-item__info">
                <div class="cart-item__name">${p.name}</div>
                <div class="cart-item__meta">${formatMoney(p.price)}${p.unit ? ` <span class="muted">/ ${p.unit}</span>` : ''}</div>
              </div>
            </div>
            <div class="cart-item__controls" role="group" aria-label="Quantity for ${p.name}">
              <button type="button" class="qty-btn" data-cart-minus data-id="${p.id}" aria-label="Decrease quantity">−</button>
              <input class="qty-input" type="number" min="1" value="${qty}" inputmode="numeric" aria-label="Quantity">
              <button type="button" class="qty-btn" data-cart-plus data-id="${p.id}" aria-label="Increase quantity">+</button>
              <button type="button" class="icon-btn cart-item__remove" data-cart-remove data-id="${p.id}" aria-label="Remove ${p.name}"><span aria-hidden="true">×</span></button>
            </div>
          `;
          cartItemsEl.appendChild(li);
        });
      }
  
      if (cartTotalEl) cartTotalEl.textContent = formatMoney(getCartTotal());
    }
  
    function openCart() {
      if (!cartDrawer) return;
      lastFocused = document.activeElement;
      cartDrawer.hidden = false;
      cartDrawer.classList.add('is-open');
      renderCart();
      releaseTrap?.();
      releaseTrap = trapFocus(cartDrawer, closeCart);
      syncBackdrop();
    }
  
    function closeCart() {
      if (!cartDrawer) return;
      cartDrawer.classList.remove('is-open');
      cartDrawer.hidden = true;
      releaseTrap?.();
      releaseTrap = null;
      if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
      syncBackdrop();
    }
  
    cartOpenBtn?.addEventListener('click', openCart);
    cartCloseBtn?.addEventListener('click', closeCart);
    checkoutBtn?.addEventListener('click', () => {
      if (getCartCount() === 0) {
        showToast('Your cart is empty.');
        return;
      }
      showToast('Checkout is not configured yet.');
    });
  
    backdrop?.addEventListener('click', () => {
      closeCart();
      closeModal();
      syncBackdrop();
    });
  
    // ------- Quantity controls (cards) -------
    function getCardQty(card) {
      const input = $('.qty-input', card);
      const n = Number(input?.value || '1');
      return Number.isFinite(n) ? n : 1;
    }
  
    function setCardQty(card, qty, max) {
      const input = $('.qty-input', card);
      if (!input) return;
      input.value = String(clamp(qty, 1, max));
    }
  
    // ------- Modal -------
    const modal = $('#product-modal');
    const modalImg = $('#product-modal-image');
    const modalTitle = $('#product-modal-title');
    const modalPrice = $('#product-modal-price');
    const modalStock = $('#product-modal-stock');
    const modalQty = $('#product-modal-qty');
    const modalTotal = $('#product-modal-total');
    const modalClose = $('[data-modal-close]');
    const modalAdd = $('[data-modal-add]');
    let modalProductId = null;
  
    function updateModalTotal() {
      if (!modalProductId) return;
      const p = products.get(modalProductId);
      if (!p) return;
      const max = p.stock > 0 ? p.stock : 1;
      const qty = clamp(Number(modalQty?.value || '1') || 1, 1, max);
      if (modalQty) modalQty.value = String(qty);
      if (modalTotal) modalTotal.textContent = formatMoney(p.price * qty);
    }
  
    function openModal(productId, initialQty = 1) {
      if (!modal) return;
      closeCart();
      const p = products.get(productId);
      if (!p) return;
      modalProductId = productId;
      lastFocused = document.activeElement;
  
      if (modalImg) {
        modalImg.src = p.image;
        modalImg.alt = p.name;
      }
      if (modalTitle) modalTitle.textContent = p.name;
      if (modalPrice) modalPrice.textContent = `${formatMoney(p.price)}${p.unit ? ` / ${p.unit}` : ''}`;
      if (modalStock) modalStock.textContent = p.stock > 0 ? `${p.stock} in stock` : 'Out of stock';
      if (modalAdd) modalAdd.disabled = p.stock <= 0;
  
      const max = p.stock > 0 ? p.stock : 1;
      if (modalQty) modalQty.value = String(clamp(initialQty, 1, max));
      updateModalTotal();
  
      modal.hidden = false;
      modal.classList.add('is-open');
      releaseTrap?.();
      releaseTrap = trapFocus(modal, closeModal);
      syncBackdrop();
    }
  
    function closeModal() {
      if (!modal) return;
      modal.classList.remove('is-open');
      modal.hidden = true;
      modalProductId = null;
      releaseTrap?.();
      releaseTrap = null;
      if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
      syncBackdrop();
    }
  
    modalClose?.addEventListener('click', closeModal);
    // Click outside panel closes the modal
    modal?.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });
  
    $('#product-modal-qty')?.addEventListener('input', updateModalTotal);
    $('[data-modal-qty-minus]')?.addEventListener('click', () => {
      if (!modalQty) return;
      modalQty.value = String((Number(modalQty.value || '1') || 1) - 1);
      updateModalTotal();
    });
    $('[data-modal-qty-plus]')?.addEventListener('click', () => {
      if (!modalQty) return;
      modalQty.value = String((Number(modalQty.value || '1') || 1) + 1);
      updateModalTotal();
    });
  
    function addToCart(productId, qty) {
      const p = products.get(productId);
      if (!p) return;
      if (p.stock <= 0) {
        showToast('Out of stock.');
        return;
      }
      const existing = cart.get(productId) || 0;
      const next = clamp(existing + qty, 0, p.stock);
      if (next === existing) {
        showToast(`Only ${p.stock} available for ${p.name}.`);
        return;
      }
      cart.set(productId, next);
      renderCart();
      showToast(`${p.name} added to cart.`);
    }
  
    modalAdd?.addEventListener('click', () => {
      if (!modalProductId) return;
      const p = products.get(modalProductId);
      if (!p) return;
      const qty = clamp(Number(modalQty?.value || '1') || 1, 1, p.stock || 1);
      addToCart(modalProductId, qty);
      closeModal();
      openCart();
    });
  
    // ------- Delegated events (cards + cart controls) -------
    document.addEventListener('click', (e) => {
      const target = e.target;
      if (!(target instanceof Element)) return;
  
      // Product card quick view
      const quick = target.closest('[data-quick-view]');
      if (quick) {
        const card = quick.closest('[data-product-id]');
        const id = card?.getAttribute('data-product-id');
        if (!id) return;
        openModal(id, getCardQty(card));
        return;
      }
  
      // Card qty
      const minus = target.closest('[data-qty-minus]');
      const plus = target.closest('[data-qty-plus]');
      if (minus || plus) {
        const card = target.closest('[data-product-id]');
        if (!card) return;
        const id = card.getAttribute('data-product-id');
        const p = id ? products.get(id) : null;
        const max = p?.stock || 9999;
        const current = getCardQty(card);
        setCardQty(card, current + (plus ? 1 : -1), max);
        return;
      }
  
      // Add to cart
      const addBtn = target.closest('[data-add-to-cart]');
      if (addBtn) {
        const card = addBtn.closest('[data-product-id]');
        const id = card?.getAttribute('data-product-id');
        if (!id || !card) return;
        const p = products.get(id);
        if (!p) return;
        const qty = clamp(getCardQty(card), 1, p.stock || 1);
        addToCart(id, qty);
        openCart();
        return;
      }
  
      // Cart controls
      const cartMinus = target.closest('[data-cart-minus]');
      const cartPlus = target.closest('[data-cart-plus]');
      const cartRemove = target.closest('[data-cart-remove]');
      if (cartMinus || cartPlus || cartRemove) {
        const id = (cartMinus || cartPlus || cartRemove)?.getAttribute('data-id');
        if (!id) return;
        const p = products.get(id);
        const existing = cart.get(id) || 0;
        if (cartRemove) {
          cart.delete(id);
          renderCart();
          showToast('Removed from cart.');
          return;
        }
        if (!p) return;
        const next = clamp(existing + (cartPlus ? 1 : -1), 0, p.stock);
        if (next <= 0) cart.delete(id);
        else cart.set(id, next);
        renderCart();
        return;
      }
    });
  
    // Quantity input typing support (cards + cart)
    document.addEventListener('change', (e) => {
      const t = e.target;
      if (!(t instanceof HTMLInputElement)) return;
      if (!t.classList.contains('qty-input')) return;
  
      // Product card qty input
      const card = t.closest('[data-product-id]');
      if (card) {
        const id = card.getAttribute('data-product-id');
        const p = id ? products.get(id) : null;
        const max = p?.stock && p.stock > 0 ? p.stock : 9999;
        const next = clamp(Number(t.value || '1') || 1, 1, max);
        t.value = String(next);
        return;
      }
  
      // Cart drawer qty input (typed)
      const cartItem = t.closest('.cart-item');
      const controls = t.closest('[data-cart-items]') || t.closest('.cart-items') || t.closest('.cart-drawer');
      if (!cartItem || !controls) return;
  
      // Find ID from sibling buttons in same control row
      const id = cartItem.querySelector('[data-cart-plus]')?.getAttribute('data-id') ||
        cartItem.querySelector('[data-cart-minus]')?.getAttribute('data-id') ||
        cartItem.querySelector('[data-cart-remove]')?.getAttribute('data-id');
      if (!id) return;
      const p = products.get(id);
      if (!p) return;
  
      const next = clamp(Number(t.value || '1') || 1, 1, p.stock > 0 ? p.stock : 1);
      t.value = String(next);
      cart.set(id, next);
      renderCart();
    });
  
    // Initial render
    renderCart();
  });
  
  