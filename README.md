# Timbuktu Farming — Corporate Premium UI (Vanilla)

This project is a **production-oriented** corporate premium website (agriculture + logistics) built with **HTML/CSS/Vanilla JS** and focused on:

- **UX & conversion**: clear CTAs, progressive disclosure on mobile, cart drawer
- **Accessibility (WCAG-minded)**: skip link, focus visible, accessible dialogs, `prefers-reduced-motion`
- **Performance**: lightweight hero slider (loads 1 image at a time), lazy-loading on non-critical images, reduced DOM work

## Project structure

- `index.php`: page markup (sections, dialogs, cart drawer)
- `style/index.css`: design tokens + components + sections
- `script.js`: navigation (scrollspy + mobile menu) + hero background slider + signup dialog
- `services.js`: Services scroll reveal + premium hover + mobile expand
- `shop.js`: product quantity, product modal, cart drawer, toast (`aria-live`)
- `assets/logo/`: brand assets (SVG)
- `image/` + `logo_slide_img/`: content images

## Design system (CSS variables)

Core tokens live in `:root` inside `style/index.css`.

- **Base**: navy/charcoal/offwhite for a “Corporate Logistics Premium” look
- **Accents**:
  - `--color-brand-500` (green) for primary CTAs
  - `--color-accent-500` (blue) for tech/logistics accents
  - `--color-warm-500` optional highlight

## Our Services (UX pro)

- **Desktop**: scroll reveal (IntersectionObserver) + premium hover highlight (pointer-driven gradient)
- **Mobile**: content is condensed and can be expanded via **progressive disclosure** using a button with `aria-expanded`

## E-commerce UX (products + cart)

- **Product cards**: quantity stepper (+/−), Add to cart, Quick view
- **Product modal**: accessible dialog, live total, stock clamp
- **Cart drawer**: side panel with editable quantities, live total
- **Toast**: `role="status"` + `aria-live="polite"` confirmation messages

## Accessibility notes

- Skip link: `.skip-link` → `#main`
- Focus visible: global `:focus-visible` ring
- Dialogs:
  - Signup: `#signup-dialog` (focus trap + ESC + click outside)
  - Product modal: `#product-modal` (focus trap + ESC)
  - Cart drawer: `#cart` (focus trap + ESC)
- Reduced motion: `prefers-reduced-motion` disables heavy transitions where appropriate

## Logo assets (SVG)

Located in `assets/logo/`:

- `logo-lockup.svg`: icon + wordmark (header)
- `logo-icon.svg`: icon-only (dialogs, small surfaces)
- `logo-white.svg`: white lockup (footer)
- `logo-black.svg`: dark lockup (light backgrounds)
- `favicon.svg`: favicon

### Export PNG (transparent)

SVG is the source of truth. To export PNGs:

- **Inkscape**: File → Export PNG → choose size (e.g., 512×512), enable transparent background
- **CLI** (if installed): `inkscape assets/logo/logo-icon.svg --export-type=png --export-width=512 --export-filename=assets/logo/logo-icon-512.png`

## Production checklist

- Replace placeholder product data (price/stock/images) with real values
- Configure checkout endpoint / payment flow
- Replace OG image with a dedicated share image if needed
- Compress hero images (WebP/AVIF) and consider `srcset` for responsive images

