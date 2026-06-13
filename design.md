# Punap — Design System v2.0
## Creative North Star: The Curated Brutalist

This system replaces default web aesthetics with a high-end editorial presence. Sharp 0px corners, tonal layering over borders, gradient CTAs, and Space Grotesk typography project architectural confidence. Every component is designed to be immediately navigable, scannable on mobile, and reactive without feeling flashy.

---

## 1. Colors

| Token | Hex | Usage |
|---|---|---|
| `primary` | `#006a38` | CTAs, active states, brand moments |
| `primary_container` | `#09864a` | Gradient endpoint on primary buttons |
| `background` | `#f9f9f9` | Page canvas |
| `surface_container_low` | `#f3f3f3` | Section backgrounds, input fields |
| `surface_container_lowest` | `#ffffff` | Cards, elevated containers, modals |
| `on_primary` | `#ffffff` | Text on green |
| `outline_variant` | `#bdcabd` | Ghost borders (15% opacity only) |
| `on_surface` | `#1a1c1c` | Primary text |
| `on_surface_variant` | `#444746` | Secondary text, labels |
| `error` | `#ba1a1a` | Destructive actions only |

### Surface Layering Rule
Depth is achieved through tonal stacking, never shadows or borders:
- `background (#f9f9f9)` → canvas
- `surface_container_low (#f3f3f3)` → section blocks, feed backgrounds
- `surface_container_lowest (#ffffff)` → cards, forms, elevated panels

**The No-Line Rule:** Never use 1px solid borders to separate sections. Use background color shifts only.

**Ghost Border Fallback:** When accessibility requires a container boundary, use `outline: 1px solid rgba(189,202,189,0.15)`. Never 100% opaque borders on interior elements.

---

## 2. Typography

```
Headlines / Labels / Buttons / Tags: Space Grotesk
Body / Input text / Descriptions:    Manrope
```

Google Fonts import (add to app.blade.php):
```html
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Manrope:wght@400;500&display=swap" rel="stylesheet">
```

Tailwind config:
```js
fontFamily: {
  'space': ['"Space Grotesk"', 'sans-serif'],
  'manrope': ['Manrope', 'sans-serif'],
}
```

### Type Scale

| Role | Font | Size | Weight | Notes |
|---|---|---|---|---|
| Hero / Display | Space Grotesk | 3.5rem | 700 | Dashboard welcome headline |
| Page heading | Space Grotesk | 1.75rem | 700 | Section titles |
| Sub-heading | Space Grotesk | 1.1rem | 500 | Panel headers |
| Body | Manrope | 1rem | 400 | Descriptions, paragraphs |
| Body small | Manrope | 0.875rem | 400 | Metadata, timestamps |
| Label / Tag | Space Grotesk | 0.6875rem | 700 | ALL CAPS, tracking: 0.05em |
| Field label | Space Grotesk | 0.6875rem | 700 | ALL CAPS, tracking: 0.06em |
| Nav link | Space Grotesk | 0.75rem | 500 | UPPERCASE, tracking: 0.04em |

---

## 3. Spacing & Layout

- Base unit: `0.5rem` (8px)
- Section padding: `spacing-12` (3rem) vertical, `spacing-16` (4rem) horizontal
- Card internal padding: `spacing-4` (1rem)
- Gap between cards in grid: `spacing-3` (0.75rem) on mobile, `spacing-4` (1rem) on desktop
- Max content width: `1200px`, centered

### Grid System
- Mobile: 1 column
- Tablet (md: 768px): 2 columns for cards, side-by-side for dashboard panels
- Desktop (lg: 1024px): 4 columns for product grid, 2 columns for dashboard panels

---

## 4. Border Radius

**0px on everything. No exceptions.**

This is the brutalist identity. No `rounded-*`, no `rounded-lg`, no `rounded-full` on any element including buttons, inputs, cards, chips, modals, nav items, and image containers.

---

## 5. Elevation & Shadows

Single shadow token only:
```css
box-shadow: 0 20px 40px rgba(26, 28, 28, 0.06);
```
Tailwind: `shadow-[0_20px_40px_rgba(26,28,28,0.06)]`

Use only on floating elements: cards on hover, modals, FABs. Never on flat section containers.

---

## 6. Components

### 6.1 Navigation Bar

**Structure:** Logo left → nav links → CTA right  
**Behaviour:** Sticky top, glassmorphism on scroll

```
bg: rgba(255,255,255,0.72) + backdrop-blur-[24px]
border-bottom: none (tonal separation from page content)
height: 56px
padding: 0 spacing-16
```

**Logo:**
```
font: Space Grotesk 700
size: 0.9375rem
color: primary (#006a38)
letter-spacing: 0.06em
text-transform: uppercase
```

**Nav links (Wishlist, Cart, Notifications, Profile):**
```
font: Space Grotesk 500, 0.75rem, uppercase, tracking: 0.04em
color: on_surface_variant (#444746)
padding: 8px 12px
border: none
background: none
hover → color: primary (#006a38)
active page → color: primary, border-bottom: 2px solid primary
```

**Primary CTA (Logout):**
```
Same as Primary Button spec below
padding: 9px 18px
```

**Mobile nav (< 768px):**
- Hamburger icon replaces nav links
- Drawer slides in from right: bg white, full height, links stacked vertically
- Each link: 48px touch target, border-bottom: 1px solid rgba(189,202,189,0.2)

**Active state indicator:** 2px bottom border in `primary` on the current page link. Left-aligned text. Never underline + color simultaneously — pick one signal.

---

### 6.2 Buttons

**Border radius: 0px on all buttons.**

#### Primary
```
background: linear-gradient(135deg, #006a38, #09864a)
color: white
padding: 12px 24px
font: Space Grotesk 700, 0.8125rem, uppercase, tracking: 0.04em
border: none
hover: brightness(1.08), cursor: pointer
active: brightness(0.95)
disabled: opacity 40%, cursor: not-allowed
```
Tailwind: `bg-gradient-to-br from-[#006a38] to-[#09864a] text-white px-6 py-3 font-space font-bold text-sm uppercase tracking-wider border-0`

#### Secondary (Ghost)
```
background: transparent
border: 2px solid #006a38
color: primary (#006a38)
padding: 10px 22px
font: Space Grotesk 700, 0.8125rem, uppercase, tracking: 0.04em
hover: background: rgba(0,106,56,0.06)
```
Tailwind: `bg-transparent border-2 border-[#006a38] text-[#006a38] px-[22px] py-[10px] font-space font-bold text-sm uppercase tracking-wider`

#### Tertiary
```
background: #d9e8d9
color: #1a3a1a
padding: 10px 22px
font: Space Grotesk 500, 0.8125rem
border: none
hover: background: #c5dbc5
```

#### Danger
```
background: transparent
border: 2px solid #ba1a1a
color: #ba1a1a
Same sizing as secondary
hover: background: rgba(186,26,26,0.06)
```

#### Full-width Button
Add `w-full` class. Used for primary CTAs inside forms and product detail panels.

---

### 6.3 Chips / Tags (BUY · RENT · SWAP)

These are interactive controls, not decorative badges.

```
font: Space Grotesk 700, 0.6875rem, ALL CAPS, letter-spacing: 0.05em
border-radius: 0px
padding: 6px 12px
border: none
cursor: pointer
```

**States:**
- Default: `background: #e2e2e2, color: #1a1c1c`
- Hover: `background: #bdcabd`
- Active/selected: `background: #006a38, color: white`
- Disabled: `opacity: 0.4, cursor: not-allowed`

Used in: product cards, product detail page, filter bar, add listing form.

---

### 6.4 Input Fields

**Border-radius: 0px. Bottom-border emphasis only.**

#### Text Input / Textarea
```
background: #f3f3f3
border: none
border-bottom: 2px solid #888
padding: 10px 12px
font: Manrope 400, 0.875rem
color: on_surface (#1a1c1c)
width: 100%

focus → border-bottom: 2px solid #006a38, outline: none, ring: none
error → border-bottom: 2px solid #ba1a1a
```
Tailwind: `bg-[#f3f3f3] border-0 border-b-2 border-gray-400 px-3 py-2.5 font-manrope text-sm focus:border-[#006a38] focus:outline-none focus:ring-0 w-full`

#### Select / Dropdown
Same as text input. Add custom chevron via `appearance-none` + SVG background icon.

#### Field Label
```
font: Space Grotesk 700, 0.6875rem, ALL CAPS, tracking: 0.06em
color: on_surface_variant (#444746)
display: block
margin-bottom: 6px
```
Tailwind: `font-space text-[11px] font-bold uppercase tracking-widest text-[#444746] block mb-1.5`

#### Quantity Input (Product Detail)
- Single quantity input for the entire page — not one per action
- Positioned above all action buttons
- Min value: 1, Max: available stock (shown inline as `of {n} available`)
- Number input with +/− steppers: `<input type="number" min="1">` wrapped in increment/decrement buttons
- Stepper buttons: 36px × 36px, border: 2px solid #888, background: white, font: Space Grotesk 700

---

### 6.5 Product Cards

```
background: #ffffff
border-radius: 0px
shadow: none (default) → shadow-[0_20px_40px_rgba(26,28,28,0.06)] (hover)
outline: 1px solid transparent (default) → outline: 1px solid rgba(189,202,189,0.5) (hover)
transition: outline-color 150ms, box-shadow 150ms
```

**Card anatomy (top to bottom):**
1. Image container: `aspect-ratio: 4/3`, `background: #f3f3f3`, `overflow: hidden`
2. Chip row: BUY / RENT / SWAP chips, `padding: 10px 12px 0`
3. Title: Space Grotesk 700, 0.875rem, `padding: 6px 12px 0`
4. Price: Space Grotesk 500, 0.875rem, `color: primary`, `padding: 4px 12px 10px`
5. Optional: "Add to wishlist" icon — top-right of image, appears on hover only

**Wishlist icon:** 24px heart SVG, `color: white` with `background: primary` (not red), positioned `absolute top-2 right-2`. No red in the system.

**Card grid container:** Always `background: #f3f3f3`, never white — tonal layering makes cards "pop".

---

### 6.6 Product Detail Page

**Layout:** 2-column on desktop (image left 45%, detail right 55%), 1-column stacked on mobile.

**Left column — Image:**
- Main image: full-width, `aspect-ratio: 1/1`, `background: #f3f3f3`
- Thumbnail strip (if multiple images): row below, 60px × 60px each, `gap: 8px`, active thumb has `outline: 2px solid primary`
- "Back to gallery" link: Space Grotesk 500, uppercase, `color: on_surface_variant`, hover → `color: primary`, `padding: 12px 0`, no underline

**Right column — Detail panel:**

```
LISTING          ← section eyebrow: Space Grotesk 700, 11px, uppercase, color: on_surface_variant
Product Title    ← Space Grotesk 700, 1.75rem, color: on_surface
Category: X      ← Manrope 400, 0.875rem, color: on_surface_variant
[BUY] [RENT] [SWAP]   ← chip row (interactive — clicking selects the transaction mode)
Rs. 23.00        ← Space Grotesk 700, 1.5rem, color: primary
```

**Metadata table:**
- `background: #f3f3f3`, no borders, no dividers
- Two columns: label left (Space Grotesk 700, 11px, uppercase, color: on_surface_variant), value right (Manrope 400, 0.875rem)
- Row padding: `12px 16px`
- Rows: Available Quantity · Owner (link to profile) · Seller Rating · Listed

**Description:**
- Eyebrow label: `DESCRIPTION`, same as section eyebrow
- Body: Manrope 400, 1rem, `color: on_surface`, `margin-top: 8px`

**Transaction area (CRITICAL — single unified flow):**

The listing type chips (BUY / RENT / SWAP) determine which action is available. Do not show all three action buttons simultaneously — show one primary CTA based on the selected chip:

```
[Chip row: BUY | RENT | SWAP]

QUANTITY label
[− ] [ 1 ] [+]    ← stepper input, of N available

→ If BUY selected:
  [BUY NOW]          ← full-width primary button
  [ADD TO CART]      ← full-width secondary button

→ If RENT selected:
  [REQUEST RENTAL]   ← full-width primary button

→ If SWAP selected:
  [PROPOSE SWAP]     ← full-width primary button
```

- Quantity stepper is shared across all modes
- No duplicate quantity inputs
- Mode switching is instant (Alpine.js / Livewire / vanilla JS — no page reload)
- Selected chip has `background: primary, color: white`

**Wishlist toggle:** Ghost icon button (heart SVG, 20px) top-right of the detail panel. Active state: `fill: primary, color: primary`. Not a floating red circle.

---

### 6.7 Dashboard Page

**Hero banner:**
```
background: #f3f3f3
padding: 48px spacing-16
border-radius: 0px
```
- Eyebrow: `WORKSPACE`, Space Grotesk 700, 11px, uppercase, `color: on_surface_variant`
- Headline: Space Grotesk 700, 3.5rem, `color: on_surface`
- Subline: Manrope 400, 1rem, `color: on_surface_variant`, max-width: 480px
- CTA row: [ADD LISTING primary] [MY LISTINGS secondary] [MY PURCHASES secondary] [NOTIFICATIONS secondary]
- On mobile: CTA row wraps, each button full-width stacked

**Stats strip:**
```
background: #f3f3f3
display: grid
grid-template-columns: repeat(3, 1fr) on mobile, repeat(6, 1fr) on desktop
border-top: 1px solid rgba(189,202,189,0.3)  ← only separator in the system for stat rows
```
Each stat cell:
- `padding: 16px 20px`
- `border-right: 1px solid rgba(189,202,189,0.3)` (last cell: none)
- Label: Space Grotesk 700, 10px, uppercase, tracking-widest, `color: #888`
- Value: Space Grotesk 700, 1.375rem, `color: primary`

On mobile (< 768px): 3-up grid, wraps to 2 rows. Remove right border on every 3rd cell.

**Pipeline panels (Seller Pipeline / Buyer Pipeline):**
```
background: #ffffff
shadow: 0 20px 40px rgba(26,28,28,0.06)
padding: 0
```
Panel header:
- `padding: 14px 20px`
- `background: #f3f3f3`
- Title: Space Grotesk 700, 0.875rem
- "OPEN" chip: same chip component, `background: primary, color: white`, `font-size: 10px`

Panel rows:
- `padding: 10px 20px`
- Label: Manrope 400, 0.875rem, `color: on_surface_variant`
- Value: Manrope 500, 0.875rem, `color: on_surface`, right-aligned
- Separation: `border-bottom: 1px solid rgba(189,202,189,0.2)` (only use inside data tables/pipeline rows)
- Hover row: `background: #f9f9f9`

Two panels side-by-side on desktop (lg), stacked on mobile.

**Unread Notifications panel:**
- Same card structure as pipeline panels
- Empty state: Manrope 400 italic, `color: on_surface_variant`, centered, `padding: 32px`
- Unread chip: `background: #e2e2e2, color: on_surface`

**Listing Health panel:**
- Same card structure
- CTA row at bottom: [REVIEW LISTINGS primary] [OPEN INBOX secondary], `padding: 16px 20px`

---

### 6.8 Marketplace / Feed Page

**Search & filter block:**
```
background: #ffffff
padding: spacing-8 spacing-16
shadow: 0 20px 40px rgba(26,28,28,0.06)
margin-bottom: spacing-8
```

Layout: 2-column grid on desktop (search full-width left, filters stacked right), 1-column on mobile.

Filter inputs follow the Input Fields spec. All `border-radius: 0px`.

Result count: Space Grotesk 700, 0.75rem, uppercase, `color: on_surface_variant`, right-aligned.

[APPLY] = primary button. [RESET] = tertiary button. Both `border-radius: 0px`.

**Feed grid:**
```
background: #f3f3f3
padding: spacing-8 spacing-16
display: grid
gap: spacing-4
grid-cols: 1 (mobile) → 2 (sm) → 3 (md) → 4 (lg)
```

**Empty state:**
```
background: #f3f3f3
padding: 80px 0
text-align: left (not centered — brutalist axis)
Headline: Space Grotesk 700, 1.1rem
Subtext: Manrope 400, 0.875rem, color: on_surface_variant
[CLEAR FILTERS] tertiary button below subtext
```

---

### 6.9 Add Listing Form

**Container:**
```
background: #ffffff
max-width: 720px
padding: spacing-12 spacing-16
shadow: 0 20px 40px rgba(26,28,28,0.06)
```

**Form layout:** Single column. Fields stacked with `gap: spacing-6` (1.5rem) between each.

**Field order:**
1. Title (text input)
2. Description (textarea, min-height: 120px)
3. Category (select)
4. Listing Type — chip group (BUY / RENT / SWAP), multiple selectable, replaces checkboxes entirely
5. Quantity (number input, min: 1)
6. Price (number input, shown if BUY or RENT selected)
7. Product Images upload zone

**Listing Type — chip group:**
Replace `<input type="checkbox">` with:
```html
<div class="flex gap-2">
  <label class="chip-toggle">
    <input type="checkbox" name="type[]" value="sell" class="sr-only">
    <span class="chip">BUY</span>
  </label>
  ...
</div>
```
JS: toggle `.active` class on the `<span>` when checkbox changes.

**Image upload zone:**
```
background: #f3f3f3
border: 2px dashed #bdcabd    ← only exception to no-border rule (upload affordance)
padding: 32px
text-align: center (exception — affordance clarity)
hover: border-color: primary
```
Label: Space Grotesk 500, 0.875rem, `color: on_surface_variant`
Sublabel: Manrope 400, 0.75rem, `color: #888`

**Submit button:** Full-width primary, `margin-top: spacing-8`.

---

## 7. Reactivity Rules

### Breakpoints (Tailwind)
| Name | Min-width | Use |
|---|---|---|
| `sm` | 640px | 2-col card grid |
| `md` | 768px | Side-by-side panels, 3-col grid |
| `lg` | 1024px | Full desktop layout, 4-col grid |
| `xl` | 1280px | Max-width container, generous margins |

### Touch Targets
Every interactive element must be at least **44px × 44px** on mobile. Apply `min-h-[44px] min-w-[44px]` to buttons, chips, and nav links.

### Hover vs Active States
- Hover states apply only on devices that support hover (`@media (hover: hover)`)
- On touch devices, use `:active` state instead with `scale(0.97)` transform
- Never rely on hover to reveal critical functionality

### Transition Defaults
```css
transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease, box-shadow 200ms ease;
```
Never animate layout properties (width, height, padding). Only colors, opacity, and shadow.

### Loading States
- Buttons in loading state: replace label with a 16px spinner (CSS border animation, `color: currentColor`), `disabled: true`, `opacity: 0.7`
- Never use skeleton loaders — use tonal placeholder blocks (`background: #f3f3f3`) that match the content shape

### Form Validation
- Inline error below the field: Manrope 400, 0.75rem, `color: #ba1a1a`
- Field border-bottom changes to `2px solid #ba1a1a` on error
- Validation triggers on blur (not on every keystroke)

---

## 8. Navigation Patterns

### Breadcrumb
- Used on: product detail, add listing, nested settings
- `font: Space Grotesk 500, 0.75rem, uppercase`
- Separator: `/` in `color: #888`, `padding: 0 6px`
- Current page: `color: on_surface`, not a link
- Previous pages: `color: on_surface_variant`, hover → `color: primary`

### Back Link
- "← BACK TO GALLERY" style
- `font: Space Grotesk 700, 0.75rem, uppercase, tracking: 0.06em`
- `color: on_surface_variant`, hover → `color: primary`
- Arrow: `←` character, `margin-right: 6px`
- No underline ever

### Page-level CTA hierarchy
Each page must have at most one primary button visible in the main content area. Secondary and tertiary actions support it. Never two primary buttons side by side.

Exception: Dashboard hero row — multiple secondary CTAs alongside one primary is acceptable because they represent separate flows.

### Empty States
Every list/grid must have a defined empty state. Use tonal messaging (no icons, no illustrations):
- Headline: Space Grotesk 700, 1rem
- Body: Manrope 400, 0.875rem, `color: on_surface_variant`
- Optional CTA: tertiary button

---

## 9. Do's and Don'ts

### Do
- Use 0px border-radius on everything
- Use tonal background shifts for section separation
- Left-align everything (brutalist vertical axis)
- Use Space Grotesk for all labels, buttons, and UI text
- Use Manrope for all body and input text
- Make chips interactive controls with clear active states
- Show one primary CTA per page section
- Use a single quantity input on product detail — never duplicate it per action
- Make the nav sticky with glassmorphism on scroll
- Show hover states only on hover-capable devices

### Don't
- Don't use rounded corners anywhere
- Don't use 1px dividers for section separation — use background color blocks
- Don't use standard shadows — only the 6% opacity ambient shadow token
- Don't center-align layout content (exception: upload zones, empty states)
- Don't show all three action buttons (Buy Now / Request Rental / Propose Swap) simultaneously — gate by selected chip
- Don't use red for the wishlist heart — use primary green
- Don't place two quantity inputs on the same form
- Don't use `border-radius` on input fields, buttons, or chips — ever
- Don't use color alone to convey state — always pair with text or shape change
