/** Resize debounce: fewer layout passes on rotation / dynamic viewport (mobile) */
function debounce(fn, ms) {
    let t = 0;
    return function debounced() {
        window.clearTimeout(t);
        t = window.setTimeout(fn, ms);
    };
}

// Navigation: active link highlight (scrollspy) + sticky header state
document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('header');
    const nav = document.querySelector('.header-right-content');
    if (!nav) return;

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Sticky header visual state
    function updateHeaderState() {
        if (!header) return;
        header.classList.toggle('is-scrolled', window.scrollY > 6);
    }
    updateHeaderState();
    window.addEventListener('scroll', updateHeaderState, { passive: true });

    // Scrollspy: highlight current section
    const links = Array.from(nav.querySelectorAll('a[href^="#"]'))
        .filter((a) => a.getAttribute('href') && a.getAttribute('href') !== '#');

    const idToLink = new Map();
    const sections = [];

    links.forEach((link) => {
        const href = link.getAttribute('href');
        if (!href || href === '#') return;
        const id = href.slice(1);
        const section = document.getElementById(id);
        if (!section) return;
        idToLink.set(id, link);
        sections.push(section);
    });

    function setActive(id) {
        links.forEach((l) => l.classList.remove('is-active'));
        const active = idToLink.get(id);
        if (active) active.classList.add('is-active');
    }

    if (!sections.length) return;

    // Scrollspy (robust): IntersectionObserver can fail for tall sections when using
    // a non-zero threshold + a narrow rootMargin band. Use scroll position instead.
    const sectionItems = sections.map((el) => ({ id: el.id, el }));
    const headerOffset = (header?.offsetHeight || 0) + 24; // sticky header + small gap

    let ticking = false;
    function updateActiveFromScroll() {
        ticking = false;
        const y = window.scrollY || 0;

        // Near top: mark Home active
        if (y < 10) {
            setActive('top');
            return;
        }

        const marker = y + headerOffset;
        let currentId = sectionItems[0]?.id || 'top';

        for (const item of sectionItems) {
            const top = item.el.getBoundingClientRect().top + y;
            if (top <= marker) currentId = item.id;
            else break; // sections are in DOM order
        }

        setActive(currentId);
    }

    function onScrollSpy() {
        if (ticking) return;
        ticking = true;
        window.requestAnimationFrame(updateActiveFromScroll);
    }

    window.addEventListener('scroll', onScrollSpy, { passive: true });
    // Initial state
    updateActiveFromScroll();

    // Mobile nav toggle
    const navToggle = nav.querySelector('.nav-toggle');
    const navList = nav.querySelector('#site-nav');

    function closeNav() {
        if (!header) return;
        header.classList.remove('is-nav-open');
        navToggle?.setAttribute('aria-expanded', 'false');
    }

    navToggle?.addEventListener('click', () => {
        if (!header) return;
        const isOpen = header.classList.toggle('is-nav-open');
        navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        if (isOpen) {
            // Focus first link for keyboard users
            const firstLink = navList?.querySelector('a, button');
            firstLink?.focus();
        }
    });

    navList?.addEventListener('click', (e) => {
        const t = e.target;
        if (!(t instanceof Element)) return;
        if (t.closest('[data-cart-open]')) return;
        if (t.closest('a[href^="#"]') || t.closest('button')) closeNav();
    });

    window.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        closeNav();
    });
});

// Accessible signup dialog: open/close + focus trap + ESC
document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('signup-dialog');
    const openBtn = document.querySelector('[data-signup-open]');
    const closeBtn = document.querySelector('[data-signup-close]');
    if (!dialog || !openBtn || !closeBtn) return;

    let lastFocused = null;
    let removeTrap = null;

    const focusableSelector =
        'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

    function trapFocus(container) {
        const focusables = Array.from(container.querySelectorAll(focusableSelector));
        const first = focusables[0] || container;
        const last = focusables[focusables.length - 1] || container;

        function onKeyDown(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                close();
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

    function open() {
        lastFocused = document.activeElement;
        dialog.hidden = false;
        removeTrap?.();
        removeTrap = trapFocus(dialog);
    }

    function close() {
        dialog.hidden = true;
        removeTrap?.();
        removeTrap = null;
        if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
    }

    openBtn.addEventListener('click', open);
    closeBtn.addEventListener('click', close);

    // Click on overlay closes (outside panel)
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) close();
    });
});

// Accessible login dialog: open/close + focus trap + ESC
document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('login-dialog');
    const openBtn = document.querySelector('[data-login-open]');
    const closeBtn = document.querySelector('[data-login-close]');
    if (!dialog || !openBtn || !closeBtn) return;

    let lastFocused = null;
    let removeTrap = null;

    const focusableSelector =
        'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

    function trapFocus(container) {
        const focusables = Array.from(container.querySelectorAll(focusableSelector));
        const first = focusables[0] || container;
        const last = focusables[focusables.length - 1] || container;

        function onKeyDown(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                close();
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

    function open() {
        lastFocused = document.activeElement;
        dialog.hidden = false;
        removeTrap?.();
        removeTrap = trapFocus(dialog);
    }

    function close() {
        dialog.hidden = true;
        removeTrap?.();
        removeTrap = null;
        if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
    }

    openBtn.addEventListener('click', (e) => {
        // Link uses href="#": prevent jump to top
        e.preventDefault();
        open();
    });
    closeBtn.addEventListener('click', close);

    // Click on overlay closes (outside panel)
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) close();
    });

    // Le submit doit aller au backend (auth).
});

// Carousel produits (flèches gauche/droite) — ignoré si grille responsive (.products-carousel--grid)
document.addEventListener('DOMContentLoaded', function () {
    const carousel = document.querySelector('.products-carousel');
    if (carousel?.classList.contains('products-carousel--grid')) return;

    const track = document.querySelector('.products-track:not(.products-track--grid)');
    const viewport = document.querySelector('.products-viewport');
    const btnLeft = document.querySelector('.products-arrow--left');
    const btnRight = document.querySelector('.products-arrow--right');

    if (!track || !viewport || !btnLeft || !btnRight) return;

    let index = 0;

    function getStep() {
        const firstCard = track.querySelector('.product');
        if (!firstCard) return 0;

        const gap = parseFloat(getComputedStyle(track).gap || '0') || 0;
        const cardWidth = firstCard.getBoundingClientRect().width;
        return cardWidth + gap;
    }

    function getMaxIndex() {
        const step = getStep();
        const cards = track.querySelectorAll('.product');
        if (!step || cards.length === 0) return 0;

        const visible = Math.max(1, Math.floor(viewport.getBoundingClientRect().width / step));
        return Math.max(0, cards.length - visible);
    }

    function update() {
        const step = getStep();
        const maxIndex = getMaxIndex();

        index = Math.min(Math.max(0, index), maxIndex);
        track.style.transform = `translateX(-${index * step}px)`;

        btnLeft.disabled = index === 0;
        btnRight.disabled = index === maxIndex;

        // Si tout tient à l'écran, on masque les flèches
        const shouldHide = maxIndex === 0;
        btnLeft.style.display = shouldHide ? 'none' : '';
        btnRight.style.display = shouldHide ? 'none' : '';
    }

    btnLeft.addEventListener('click', () => {
        index -= 1;
        update();
    });

    btnRight.addEventListener('click', () => {
        index += 1;
        update();
    });

    window.addEventListener('resize', debounce(update, 150));
    update();
});

// Carousel blogs (flèches gauche/droite)
document.addEventListener('DOMContentLoaded', function () {
    const track = document.querySelector('.blogs-track');
    const viewport = document.querySelector('.blogs-viewport');
    const btnLeft = document.querySelector('.blogs-arrow--left');
    const btnRight = document.querySelector('.blogs-arrow--right');

    if (!track || !viewport || !btnLeft || !btnRight) return;

    let index = 0;

    function getStep() {
        const firstCard = track.querySelector('.blog-card');
        if (!firstCard) return 0;

        const gap = parseFloat(getComputedStyle(track).gap || '0') || 0;
        const cardWidth = firstCard.getBoundingClientRect().width;
        return cardWidth + gap;
    }

    function getMaxIndex() {
        const step = getStep();
        const cards = track.querySelectorAll('.blog-card');
        if (!step || cards.length === 0) return 0;

        const visible = Math.max(1, Math.floor(viewport.getBoundingClientRect().width / step));
        return Math.max(0, cards.length - visible);
    }

    function update() {
        const step = getStep();
        const maxIndex = getMaxIndex();

        index = Math.min(Math.max(0, index), maxIndex);
        track.style.transform = `translateX(-${index * step}px)`;

        btnLeft.disabled = index === 0;
        btnRight.disabled = index === maxIndex;

        const shouldHide = maxIndex === 0;
        btnLeft.style.display = shouldHide ? 'none' : '';
        btnRight.style.display = shouldHide ? 'none' : '';
    }

    btnLeft.addEventListener('click', () => {
        index -= 1;
        update();
    });

    btnRight.addEventListener('click', () => {
        index += 1;
        update();
    });

    window.addEventListener('resize', debounce(update, 150));
    update();
});










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
            btn.textContent = 'Voir le detail';
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
                otherBtn.textContent = 'Voir le detail';
            }
        });

        const isExpanded = card.classList.toggle('is-expanded');
        btn.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        btn.textContent = isExpanded ? 'Masquer le detail' : 'Voir le detail';
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

    function isPurchasable(p) {
      if (!p) return false;
      return !['out_of_stock', 'discontinued'].includes(p.availability || 'in_stock');
    }

    function getMaxQty(p) {
      if (!p) return 1;
      // Si stock connu (in_stock), on le respecte; sinon quantité "ouverte" (backorder / preorder).
      if ((p.availability || 'in_stock') === 'in_stock' && p.stock > 0) return p.stock;
      return 9999;
    }

    function availabilityLabel(p) {
      const a = p?.availability || 'in_stock';
      if (a === 'in_stock') return p.stock > 0 ? `${p.stock} en stock` : 'En stock';
      if (a === 'backorder') return 'Sur commande';
      if (a === 'preorder') return 'Precommande';
      if (a === 'out_of_stock') return 'Rupture de stock';
      if (a === 'discontinued') return 'Arrete';
      return a;
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
        description: card.getAttribute('data-description') || 'Produit agricole de qualite.',
        sku: card.getAttribute('data-sku') || '',
        price: Number(card.getAttribute('data-price') || '0'),
        unit: card.getAttribute('data-unit') || '',
        stock: Number(card.getAttribute('data-stock') || '0'),
        availability: card.getAttribute('data-availability') || 'in_stock',
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
            <div class="cart-item__controls" role="group" aria-label="Quantite pour ${p.name}">
              <button type="button" class="qty-btn" data-cart-minus data-id="${p.id}" aria-label="Diminuer la quantite">−</button>
              <input class="qty-input" type="number" min="1" value="${qty}" inputmode="numeric" aria-label="Quantite">
              <button type="button" class="qty-btn" data-cart-plus data-id="${p.id}" aria-label="Augmenter la quantite">+</button>
              <button type="button" class="icon-btn cart-item__remove" data-cart-remove data-id="${p.id}" aria-label="Retirer ${p.name}"><span aria-hidden="true">×</span></button>
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
    checkoutBtn?.addEventListener('click', async () => {
      if (getCartCount() === 0) {
        showToast('Votre panier est vide.');
        return;
      }

      // Sync le panier JS vers la session PHP, puis redirige vers le checkout.
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const items = Array.from(cart.entries()).map(([id, qty]) => ({ id, qty }));

      try {
        const res = await fetch('cart_sync.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrf,
          },
          body: JSON.stringify({ items }),
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const json = await res.json();
        if (!json?.ok) throw new Error(json?.error || 'Sync failed');

        window.location.href = 'checkout.php';
      } catch (e) {
        showToast('Impossible de demarrer le paiement. Reessayez.');
      }
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
    const modalDesc = $('#product-modal-desc');
    const modalSku = $('#product-modal-sku');
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
      const max = getMaxQty(p);
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
      if (modalSku) {
        modalSku.textContent = p.sku ? `SKU: ${p.sku}` : '';
        modalSku.hidden = !p.sku;
      }
      if (modalDesc) modalDesc.textContent = p.description || 'Produit agricole de qualite.';
      if (modalPrice) modalPrice.textContent = `${formatMoney(p.price)}${p.unit ? ` / ${p.unit}` : ''}`;
      if (modalStock) modalStock.textContent = availabilityLabel(p);
      if (modalAdd) modalAdd.disabled = !isPurchasable(p);
  
      const max = getMaxQty(p);
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
  
    // Modal click handling (robust): close button, add button, and click-outside.
    // Using delegation avoids issues if elements are re-rendered or if querySelector
    // would otherwise bind to the wrong node.
    modal?.addEventListener('click', (e) => {
      const t = e.target;
      if (!(t instanceof Element)) return;

      // Close button
      if (t.closest('[data-modal-close]')) {
        e.preventDefault();
        closeModal();
        return;
      }

      // Add to cart (from modal)
      if (t.closest('[data-modal-add]')) {
        e.preventDefault();
        if (!modalProductId) return;
        const p = products.get(modalProductId);
        if (!p) return;
        const qty = clamp(Number(modalQty?.value || '1') || 1, 1, getMaxQty(p));
        addToCart(modalProductId, qty);
        closeModal();
        openCart();
        return;
      }

      // Click outside panel closes the modal
      if (t === modal) closeModal();
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
      if (!isPurchasable(p)) {
        showToast('Indisponible.');
        return;
      }
      const existing = cart.get(productId) || 0;
      const max = getMaxQty(p);
      const next = clamp(existing + qty, 0, max);
      if (next === existing) {
        if ((p.availability || 'in_stock') === 'in_stock') {
          showToast(`Seulement ${max} disponibles pour ${p.name}.`);
        } else {
          showToast(`${p.name} ajoute au panier.`);
        }
        return;
      }
      cart.set(productId, next);
      renderCart();
      showToast(`${p.name} ajoute au panier.`);
    }
  
    // (Modal add/close handlers are handled above via delegation)
  
    // ------- Delegated events (cards + cart controls) -------
    document.addEventListener('click', (e) => {
      const target = e.target;
      if (!(target instanceof Element)) return;
  
      // Product card quick view
      const quick = target.closest('[data-quick-view]');
      if (quick) {
        e.preventDefault();
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
        const max = p ? getMaxQty(p) : 9999;
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
        const qty = clamp(getCardQty(card), 1, getMaxQty(p));
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
          showToast('Produit retire du panier.');
          return;
        }
        if (!p) return;
        const next = clamp(existing + (cartPlus ? 1 : -1), 0, getMaxQty(p));
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
        const max = p ? getMaxQty(p) : 9999;
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
  
      const next = clamp(Number(t.value || '1') || 1, 1, getMaxQty(p));
      t.value = String(next);
      cart.set(id, next);
      renderCart();
    });
  
    // Initial render
    renderCart();
  });
  