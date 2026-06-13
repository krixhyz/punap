# PUNAP — Mobile App UI Design Plan

> **Application:** PUNAP — A Circular P2P Marketplace
> **Platform:** React Native (iOS + Android) / Flutter  
> **Purpose:** Allow users to Buy, Rent, and Swap everyday goods in a sustainable P2P economy.  
> **Document Author:** Generated from live web app analysis — June 2026

---

## 1. App Overview & Mission

PUNAP is a circular economy marketplace where users can give preloved items a second life through three transaction modes:

- **Buy / Sell** — Direct P2P purchase
- **Rent / Borrow** — Time-bound item lending with security deposit
- **Swap** — Item-for-item exchange with automated fair-value resolution

The mobile app must translate the full feature-set of the existing web portal into an intuitive, thumb-first mobile experience.

---

## 2. Design Language & Visual Identity

### 2.1 Brand Palette

| Token | Value | Usage |
|---|---|---|
| `primary` | `#1A6B3C` (Deep Forest Green) | CTAs, active states, brand accents |
| `primary-light` | `#E8F5EE` | Card backgrounds, pill highlights |
| `primary-dark` | `#124D2B` | Pressed states, header gradients |
| `surface` | `#F5F6F8` | App background |
| `card` | `#FFFFFF` | Card surfaces |
| `text-primary` | `#111827` | Headings, important values |
| `text-secondary` | `#6B7280` | Labels, meta-text |
| `text-muted` | `#9CA3AF` | Placeholders, timestamps |
| `accent-buy` | `#1A6B3C` | "Buy" mode badge |
| `accent-rent` | `#2563EB` | "Rent" mode badge |
| `accent-swap` | `#7C3AED` | "Swap" mode badge |
| `success` | `#10B981` | Completed, approved states |
| `warning` | `#F59E0B` | Pending, awaiting states |
| `danger` | `#EF4444` | Rejected, disputed states |
| `eco-gold` | `#F59E0B` | Eco Score indicator |

### 2.2 Typography

| Style | Font | Weight | Size |
|---|---|---|---|
| Display | `Outfit` | 700 (Bold) | 28–32sp |
| Heading | `Outfit` | 600 (SemiBold) | 20–22sp |
| Sub-heading | `Outfit` | 500 (Medium) | 16–18sp |
| Body | `Inter` | 400 (Regular) | 14–15sp |
| Caption / Label | `Inter` | 400 | 12sp |
| Micro | `Inter` | 400 | 10sp |

### 2.3 Elevation & Radius

- **Card radius:** `12dp`
- **Button radius:** `10dp`
- **Pill/Badge radius:** `999dp` (fully rounded)
- **Bottom sheet radius (top):** `24dp`
- **Card shadow:** `0 2px 12px rgba(0,0,0,0.07)`
- **Modal shadow:** `0 8px 32px rgba(0,0,0,0.18)`

### 2.4 Micro-animation Principles

- Tab bar item selection: scale `1.0 → 1.15` + color transition (150ms ease-out)
- Card press: scale `1.0 → 0.97` (100ms) + shadow reduction
- Bottom sheet open: slide-up from 30% with spring physics
- Listing type toggle (Buy/Rent/Swap): animated underline slide (200ms)
- Eco Score ring: animated stroke draw on first load (600ms)
- Skeleton loaders on all async data, not spinners

---

## 3. Navigation Architecture

### 3.1 Bottom Tab Bar (5 Tabs)

The primary navigation is a **persistent bottom tab bar** with large tap targets (48dp min).

```
┌────────────────────────────────────────┐
│  🏠 Home  │  🛍 Market │  ➕ List  │  📦 Activity │  👤 Profile │
└────────────────────────────────────────┘
```

| Tab | Icon | Label | Primary Screen |
|---|---|---|---|
| 1 | House icon | Home | Dashboard Overview |
| 2 | Grid/Store icon | Market | Marketplace Browse |
| 3 | **FAB `+`** | List | Create Listing |
| 4 | Box/Activity icon | Activity | Orders, Rentals, Swaps |
| 5 | Person icon | Profile | Profile & Wallet |

**Tab bar design rules:**
- Active tab: icon + label in `primary` green + filled icon variant
- Inactive tab: icon + label in `text-muted` grey + outlined icon variant
- Centre `+` tab is a **floating action pill** (not flat), elevated with green background
- Tab bar has a `1dp` top border in `#E5E7EB`
- Safe area padding respected on iOS (home indicator) and Android

### 3.2 Stack Navigation per Tab

Each tab manages its own independent navigation stack so back navigation is contextual.

---

## 4. Screen-by-Screen Specifications

---

### 4.1 Screen: Splash / Onboarding

**When:** First launch only

**Layout:**
- Full-screen deep green gradient (`#124D2B → #1A6B3C`)
- Centered PUNAP wordmark in white (Outfit Bold, 36sp)
- Tagline: _"Give it a second life."_ in white semi-transparent text
- Animated leaf/loop icon (SVG, 80dp, draws in with stroke animation)
- "Get Started" button (white bg, green text) — leads to Registration
- "Log In" text-link below button

---

### 4.2 Screen: Login

**Layout:**
- Light background (`#F5F6F8`)
- Top: PUNAP logo + "Welcome Back" heading
- Form card (white, rounded-16, shadow):
  - Email input with mail icon prefix
  - Password input with lock icon + eye toggle suffix
  - "Forgot Password?" text link right-aligned
- Primary CTA: "Sign In" (full-width, green, 52dp height)
- Divider: "— or —"
- Social login row (Google / Facebook icons)
- Footer: "New to PUNAP? Register"

---

### 4.3 Screen: Dashboard (Tab 1 — Home)

**Header:**
- `greeting text`: "Welcome back, **krish** 👋" (Outfit SemiBold 20sp)
- Sub-text: "Your marketplace at a glance"
- Top-right: Bell icon (notification count badge in red) + Avatar (tappable → Profile)

**Body — Scrollable vertical feed:**

**Block A — Stats Row (horizontal scroll, 4 cards):**
```
┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
│ 🏷 22    │ │ 📦 5     │ │ 🔄 0     │ │ ⚡ 324   │
│ Active   │ │ Total    │ │ Active   │ │ Eco      │
│ Listings │ │ Orders   │ │ Rentals  │ │ Score    │
└──────────┘ └──────────┘ └──────────┘ └──────────┘
```
- Cards: white bg, 12dp radius, green icon in small chip
- Eco Score card shows a small circular progress arc in gold

**Block B — Eco Score Spotlight Card:**
- Full-width card with gradient background (light green)
- Large circular progress ring (SVG) showing score `324` in centre
- Label: "Your Circular Economy Score"
- Sub-text: "Keep listing, renting, and swapping to earn more points!"

**Block C — Quick Actions Grid (2×2):**
```
┌──────────────┬──────────────┐
│  📋 New      │  🏪 View     │
│  Listing     │  Listings    │
├──────────────┼──────────────┤
│  💰 Open     │  🔔 Check    │
│  Wallet      │  Inbox       │
└──────────────┴──────────────┘
```
- Each action is a card with icon (40dp circle, light green bg) + label
- Tap navigates directly to that section

**Block D — Recent Notifications Preview:**
- Section header: "Recent Activity" + "View All →" right
- Up to 3 notification rows with tag chip (CTR, SWAP, OK, N) + message + timestamp
- If empty: empty-state illustration + "No recent activity"
- "View All" navigates to full Notifications screen

---

### 4.4 Screen: Marketplace (Tab 2 — Market)

**Header (sticky):**
- Search bar (full-width, rounded, magnifier icon prefix, `Search items...` placeholder)
- Below search: horizontally scrolling **Category Chip Row**
  - All | Electronics | Clothing | Books | Furniture | Cameras | Sports | Other
  - Active chip: green bg + white text
  - Inactive chip: white bg + grey border + grey text

**Filter Bar (below chips):**
- "Filter" button (left) with filter icon — opens bottom sheet
- Active filter count badge on button
- Sort dropdown (right): "Newest", "Price: Low-High", "Price: High-Low"

**Product Grid (2-column):**

Each product card:
```
┌───────────────────┐
│  [Product Image]  │  ← 1:1 ratio, rounded top corners
│   10 in stock ▸  │  ← Stock badge (top-right overlay)
├───────────────────┤
│ Drone             │  ← Title (SemiBold 14sp)
│ Cameras           │  ← Category (caption, muted)
│ Rs. 1,200/day ●  │  ← Primary price, coloured dot
│ [BUY][RENT][SWAP] │  ← Mode badges (pill chips)
│ sachet silwal ★  │  ← Seller name + rating
└───────────────────┘
```
- Mode badge colours: BUY=green, RENT=blue, SWAP=purple
- Heart/wishlist icon overlay (top-left of image)
- Tap card → Product Detail screen (push)

**Filter Bottom Sheet:**

Slides up from bottom (drag handle at top):
- **Transaction Type:** "For Sale" / "For Rent" / "For Swap" toggle chips
- **Category:** checkbox list (All, Electronics, Clothing, etc.)
- **Condition:** "Like New", "Good", "Fair", "Poor"
- **Price Range:** dual-handle range slider (Rs. 0 – Rs. 50,000)
- Footer: "Clear All" (ghost) + "Apply Filters" (primary CTA)

---

### 4.5 Screen: Product Detail

**Navigation:** Pushed from Marketplace, My Listings cards, or Wishlist

**Layout (full-screen):**

**Image Section (top 45% of screen):**
- Swipeable image gallery (full-width, no rounded corners at top)
- Page indicator dots at bottom of image area
- Back button (top-left, semi-transparent circle)
- Wishlist toggle (top-right, semi-transparent circle, heart icon)

**Content Section (scrollable, rounded-top card overlapping image):**

- **Listing Type Badge:** small pill (e.g., "Cameras")
- **Title:** large display heading (Outfit Bold 24sp)
- **Seller Row:** avatar + name + "VERIFIED" badge + star rating + "View Profile →"
- **Status Row:** Available count | Condition | Listed date

**Transaction Mode Selector:**
```
┌──────┬──────┬──────┐
│ BUY  │ RENT │ SWAP │  ← Segmented control, animated underline
└──────┴──────┴──────┘
```

**BUY tab content:**
- Price: large green amount (e.g., "Rs. 14,000")
- Quantity stepper (+/−)
- Description (expandable)
- Sticky bottom bar: "Add to Cart" (outline) + "Buy Now" (primary)

**RENT tab content:**
- Daily Rate: `Rs. 1,200/day`
- Security Deposit: `Rs. 3,000`
- Max Duration: `31 days`
- Available From: date text
- Date picker (inline calendar or tap-to-open bottom sheet calendar)
- Summary: "5 days × Rs. 1,200 + Rs. 3,000 deposit = Rs. 9,000"
- Sticky bottom bar: "Request Rental" (primary CTA)

**SWAP tab content:**
- "Your offer" section: grid of user's own listings to select as swap item
- Value comparison bar: "Their item (Est. Rs. 14,000) ↔ Your item (Est. Rs. X)"
- Note field (optional message)
- Sticky bottom bar: "Propose Swap" (primary CTA)

---

### 4.6 Screen: Create Listing (Tab 3 — Centre FAB)

**Header:** "New Listing" + X close button (dismisses to previous tab)

**Step Indicator:** 3-step horizontal progress bar at top
```
● ─────── ○ ─────── ○
Step 1      Step 2     Step 3
Details   Type & Price  Photos
```

**Step 1 — Item Details:**
- Title input (required)
- Description textarea
- Category dropdown (opens modal picker):
  - Electronics, Clothing, Books, Furniture, Cameras, Sports, Other
- Condition selector (segmented or radio):
  - Like New | Good | Fair | Poor
- "Next →" CTA (primary, full-width, bottom)

**Step 2 — Listing Type & Pricing:**
- Toggle section title: "How would you like to offer this?"
- **For Sale** switch:
  - When ON: shows "Selling Price" input + "Stock Quantity" input
- **For Rent** switch:
  - When ON: shows "Daily Rate" input + "Security Deposit" input + "Max Duration (days)" input
- **For Swap** switch:
  - When ON: shows "Estimated Value" input + optional "Swap preferences" note
- At least one option must be enabled (validated before proceeding)
- "← Back" ghost + "Next →" primary CTAs (row)

**Step 3 — Photos:**
- Photo upload grid (3×2, 6 slots max):
  - First slot: "+" icon + "Add Photo" label
  - Filled slots: thumbnail with X remove button overlay
- Tap slot: opens native image picker (camera OR gallery)
- Drag-to-reorder support (first photo = cover)
- "← Back" ghost + "Submit Listing" primary CTAs (row)

**On Submit:** Success bottom sheet with confetti micro-animation + "View Listing" CTA.

---

### 4.7 Screen: Activity Hub (Tab 4)

**Header:** "Activity" title

**Tabs (horizontal tab bar):**
```
[ My Orders ] [ Rentals ] [ Swaps ]
```

---

**My Orders Tab:**

Stats row: "Total Orders: 5" chip

Order card:
```
┌─────────────────────────────────┐
│ [Img] Drone                     │
│       sachet silwal             │
│       May 21, 2026 • PAID       │
│       Rs. 14,000.00             │
│  [Leave Review]  [Report Issue] │
└─────────────────────────────────┘
```
- Status chips colour-coded: PAID=green, PENDING=amber, REFUNDED=blue, DISPUTED=red
- "Leave Review" opens a bottom sheet with star rating + text input
- "Report Issue" opens a dispute form bottom sheet

---

**Rentals Tab:**

Sub-tabs:
```
[ Active ] [ Completed as Renter ] [ Owner Completed ]
```

Rental card:
```
┌─────────────────────────────────┐
│ [Img] DJI Controller            │
│       Owner: sachet silwal      │
│       May 20 → May 25, 2026     │
│       ⏱ 3 days remaining        │
│       [Return Item]             │
└─────────────────────────────────┘
```
- Active rentals show a countdown timer in amber
- Completed rentals show final status + review option

---

**Swaps Tab:**

Stats row: Completed (3) | Non-completed (2) | Total (5)

Sub-tabs:
```
[ Completed (3) ] [ Non-completed (2) ]
```

Swap card:
```
┌─────────────────────────────────┐
│  [Watch img] ↔ [Drone img]      │
│  Casio Watch ↔ Drone            │
│  With: sachet silwal            │
│  PAID • May 21, 2026            │
│  Dispatch Contact: ...          │
└─────────────────────────────────┘
```
- Swap arrows (↔) displayed between the two item thumbnails
- Status badge: COMPLETED=green, PENDING=amber, REJECTED=red, PAID=blue

---

### 4.8 Screen: Profile & Wallet (Tab 5)

**Top Profile Card:**
- Large avatar (80dp circle) — initials or uploaded photo
- Name: "krish" (Display Bold)
- Location: "Pokhara, Gandaki Province" (caption + pin icon)
- Badges row: "VERIFIED" chip + "★ VERIFIED SELLER" chip
- Stats row: Active Listings | Completed Deals | Member Since
- "Edit Profile" button (outline, small)

**Section — Wallet Summary Card:**
- Gradient background (light green)
- "My Wallet" heading
- Available Balance: large amount (e.g., `Rs. 2,357.00`) in green
- Pending Hold: `Rs. 0.00`
- Row: "Active Payouts: 0" | "Ledger Entries: 8"
- "Request Payout →" CTA (white pill button)

**Wallet Payout Form (expanding section or navigate to sub-screen):**
- Amount input
- Note (optional)
- "Submit Request" CTA

**Wallet Tabs:**
```
[ Active Payouts (0) ] [ Ledger (8) ] [ Payout History (2) ]
```

Ledger entry row:
```
+Rs. 2,400  │  Sale: DJI Drone          │  May 21
-Rs. 43     │  Swap Shipping fee        │  May 20
+Rs. 3,000  │  Security Deposit Release │  May 19
```
- Credits: green `+` prefix
- Debits: red `−` prefix

**Section — My Public Profile:**
- "Preview how others see you →" link
- Shows active listings grid (3-column thumbnail grid)
- Shows completed review count and average star rating

**Section — Navigation Links:**
```
🔔  Notifications
❤️  Wishlist
⚙️  Profile Settings
🚪  Logout
```
Each row: icon + label + chevron `›`

---

### 4.9 Screen: Wishlist

**Header:** "My Wishlist" + count badge

**Layout:** 2-column product grid (same card design as Marketplace)
- Remove from wishlist: swipe-left gesture or X icon on card
- Empty state: heart illustration + "Save items you love" + "Browse Marketplace" CTA

---

### 4.10 Screen: Notifications

**Header:** "Notifications" + "Mark All Read" (text button, right)

**Notification types and their chip colours:**
| Type | Chip | Example |
|---|---|---|
| Counter Offer | `CTR` (amber) | "Owner made counter offer of Rs. 14,000 for Drone" |
| Swap Request | `SWAP` (purple) | "New swap request for Casio Watch from sachet silwal" |
| Rental Approved | `OK` (green) | "Your rental request has been approved by the owner" |
| Swap Complete | `N` (grey) | "Your swap has been completed. Funds transferred." |
| System | `N` (grey) | General system messages |

Notification row:
```
┌────────────────────────────────────────┐
│ [CTR]  Owner made a counter offer...   │
│        3 weeks ago            [View →] │
└────────────────────────────────────────┘
```
- Unread rows have a subtle left green border accent
- Tapping "View" deep-links into relevant swap/rental/order screen

---

### 4.11 Screen: Profile Settings

**Sections:**

**Personal Information:**
- Full Name (editable input)
- Email (read-only + "Change Email" link)
- Phone Number
- Location (city/province text)
- Bio / About (textarea)

**Account Security:**
- "Change Password" row → navigates to change-password flow
- "Linked Accounts" (Google/Facebook)

**Seller Settings:**
- "Become a Verified Seller" prompt (if not verified)
- Average Rating display
- Total Deals counter

**Preferences:**
- Push Notifications toggle
- Email Notifications toggle
- Dark Mode toggle (future)

**Footer:**
- "Save Changes" primary CTA (sticky bottom)

---

### 4.12 Screen: Public Seller Profile

**Accessed via:** Product detail → seller name tap

**Layout:**
- Seller avatar (large, 80dp)
- Name + location + verification badges
- Stats: Active Listings | Completed Deals | Member Since | Avg Rating
- Active Listings grid (2-column)
- Reviews section (star summary + individual review cards)

---

## 5. Key UX Patterns & Interaction Guidelines

### 5.1 Bottom Sheets
Used for: Filters, Review forms, Dispute forms, Swap proposal picker, Date pickers, Confirmation dialogs

**Rules:**
- Always has a drag handle (grey pill, 40×4dp, centred)
- Background dims to 40% black overlay
- Dismissable by dragging down or tapping backdrop
- Keyboard-aware (slides up with keyboard)

### 5.2 Empty States
Every list screen must handle empty states:
- Illustration (SVG, on-brand colour)
- Concise message ("No listings yet")
- CTA button to primary action

### 5.3 Loading States
- Use **skeleton screens** (animated shimmer) for all data lists
- Avoid full-page spinners
- Use small circular indicators only for button loading states (after tap)

### 5.4 Error States
- Network error: toast message at bottom (red, auto-dismiss 4s)
- Form validation: inline red border + error label below field
- Full-page error: error illustration + "Try Again" button

### 5.5 Pull-to-Refresh
Every list screen supports pull-to-refresh (green spinner on pull).

### 5.6 Infinite Scroll / Pagination
Marketplace product grid paginates — "load more" trigger at bottom of list (auto-fetch when 3 cards from bottom).

---

## 6. Screen Map / Navigation Flow

```
                    App Launch
                        │
               ┌────────┴────────┐
           First Time         Returning
               │                 │
          Onboarding           Login
               │                 │
               └────────┬────────┘
                        │
                   Bottom Tab Bar
          ┌─────────────┼─────────────┐
         Home        Market      Activity    Profile
          │             │              │         │
       Dashboard   Marketplace    Orders/     Profile+
       Overview       Browse      Rentals/    Wallet
                        │         Swaps
                   Product Detail
                   ┌────┤
                  Buy  Rent  Swap
                        │
                   Create Listing
                   (Tab Centre FAB)
                   Step 1 → Step 2 → Step 3
```

---

## 7. Platform-Specific Considerations

### iOS
- Use SF Symbols where matching (supplement with custom icons)
- Respect safe area insets top (notch/island) and bottom (home indicator)
- Navigation bar: native look or custom matching brand
- Haptic feedback on: CTAs, toggle switches, cart additions, swap proposals

### Android
- Material elevation shadows
- Respect system navigation (gesture nav / 3-button)
- Status bar: transparent, dark icons on light background
- Ripple effect on all tappable items

---

## 8. Accessibility Requirements

- All tappable elements: minimum 44×44dp touch target
- Colour contrast: WCAG AA minimum (4.5:1 for body text)
- Screen reader labels on all icons (no icon-only buttons without label or aria)
- Dynamic font size support (scale up to 150% without layout breakage)
- Focus indicators for keyboard/switch access users

---

## 9. Future Screens (Phase 2)

| Screen | Purpose |
|---|---|
| **Chat / Messaging** | In-app P2P messaging between buyer and seller |
| **Map View** | Browse nearby listings on a map |
| **Eco Impact Report** | Gamified view of CO₂ saved, items saved from landfill |
| **Referral & Rewards** | Earn Eco Score by referring friends |
| **Admin Panel (separate app)** | Manage users, listings, disputes, payouts |
| **Barcode Scanner** | Scan product barcode to auto-fill listing details |

---

## 10. Summary of Screens

| # | Screen | Tab | Route |
|---|---|---|---|
| 1 | Splash / Onboarding | — | `/splash` |
| 2 | Login | — | `/login` |
| 3 | Register | — | `/register` |
| 4 | Dashboard Overview | Home | `/home` |
| 5 | Marketplace Browse | Market | `/marketplace` |
| 6 | Product Detail | Market | `/product/:id` |
| 7 | Create Listing Step 1 | + FAB | `/list/details` |
| 8 | Create Listing Step 2 | + FAB | `/list/type` |
| 9 | Create Listing Step 3 | + FAB | `/list/photos` |
| 10 | My Orders | Activity | `/activity/orders` |
| 11 | Rentals | Activity | `/activity/rentals` |
| 12 | Swaps | Activity | `/activity/swaps` |
| 13 | Profile & Wallet | Profile | `/profile` |
| 14 | Wallet Ledger | Profile | `/profile/wallet` |
| 15 | Wishlist | Profile | `/profile/wishlist` |
| 16 | Notifications | Profile | `/profile/notifications` |
| 17 | Profile Settings | Profile | `/profile/settings` |
| 18 | Public Seller Profile | Pushed | `/user/:id` |

---

## 11. Role System Overview

PUNAP has three user roles, each with a distinct app experience:

| Role | Badge Colour | App Experience |
|---|---|---|
| **User** | — | Full marketplace access (Sections 4.1–4.12) |
| **Admin** | `ADMIN` (dark green pill) | Separate admin panel app / admin mode |
| **Super Admin** | `SUPER ADMIN` (deep purple pill) | Full admin panel + exclusive super-admin screens |

> [!IMPORTANT]
> The Admin and Super Admin experiences are designed as a **dedicated Admin App** (separate from the consumer marketplace app), or as a **role-gated Admin Mode** accessible after login when the user's role is detected. Regular users never see admin navigation.

---

## 12. Admin Panel — Mobile App Design

The Admin Panel is a standalone mobile view (or gated mode) with a **dark sidebar drawer** navigation pattern, replacing the consumer app's bottom tab bar.

### 12.1 Admin Navigation Pattern

Instead of a bottom tab bar, admin roles use a **slide-in left drawer** accessed via a hamburger icon (top-left) on all admin screens.

**Admin Drawer Header:**
```
┌────────────────────────────────────┐
│  [Avatar]  Prabesh GRG             │
│            grgprabesh88@gmail.com  │
│            [ADMIN] badge           │
└────────────────────────────────────┘
```

**Drawer design:**
- Background: `#111827` (dark charcoal)
- Text: white / `#D1D5DB`
- Active item: left green accent bar + `#1A6B3C` tint background
- Section headers in uppercase caption (`#6B7280`)

---

### 12.2 Admin Role — Navigation & Screens

**Drawer Sections:**

```
── MAIN ────────────────────────────
  🏠  Overview (Dashboard)
  👥  User Management
  🏷️  Categories
  🛡️  Content Moderation

── OPERATIONS ──────────────────────
  💳  Transactions
  💰  Wallet Payouts
  ⚖️  Disputes
  📊  Reports

── OTHER ───────────────────────────
  ⚙️  Profile Settings
  🚪  Logout
```

---

### 12.3 Screen: Admin Dashboard (Overview)

**Purpose:** Operational queue workload view — shows pending tasks requiring admin action.

**Header bar:**
- Hamburger menu (top-left)
- Title: "Admin Dashboard"
- Bell icon (top-right, notification count)
- `[ADMIN]` role badge visible next to username in top bar

**Body — Scrollable:**

**Block A — Queue Stats Row (horizontal scroll, 4 cards):**
```
┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ ⏳ N         │ │ 🚩 N         │ │ ⚖️ N          │ │ 📋 N         │
│ Pending      │ │ Flagged      │ │ Active       │ │ Reports      │
│ Verifications│ │ Listings     │ │ Disputes     │ │ to Review    │
└──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘
```
- Cards use amber/warning tones when counts are > 0 to signal urgency
- Tap card → navigates directly to that section

**Block B — Queue Breakdown Chart:**
- Horizontal bar chart showing workload split across categories
- Labels: "Verifications", "Moderation", "Disputes", "Reports"
- Bar colour: green filled proportion vs grey total

**Block C — Pending Verifications List (inline preview, up to 3):**
- Each row: avatar + username + email + location + "Verify" (green) / "Suspend" (red) buttons
- "View All →" link at bottom → navigates to User Management with verification filter

**Block D — Priority Disputes Widget:**
- Up to 2 active dispute rows
- Each row: dispute ID + short description + "Open" chip + "View →" link
- "View All Disputes →" link at bottom

---

### 12.4 Screen: Admin User Management

**Header:** "User Management" title + search bar

**Access restriction banner** (shown only for Admin role):
```
┌─────────────────────────────────────────────────┐
│ ℹ️  Limited Access                              │
│  You can manage regular users only.             │
│  Cannot manage Admins or Super Admins.          │
│  Cannot access sensitive payment details.       │
└─────────────────────────────────────────────────┘
```
- Banner: light amber bg, left amber border, info icon

**Filter chips (horizontal scroll):**
```
[ All ] [ Active ] [ Suspended ] [ Banned ] [ Pending Verification ]
```

**User list (card per user):**
```
┌─────────────────────────────────────────────┐
│ [Avatar]  John Doe                          │
│           john@example.com                  │
│           Active • Joined 3 months ago      │
│           [USER] badge                      │
│  [View]  [Suspend]  [Delete]  [Reset Pass]  │
└─────────────────────────────────────────────┘
```
- Role badge: static `[USER]` — cannot be changed by Admin
- Actions: View (→ user detail), Suspend, Delete, Reset Password
- Status change: tap status chip → shows options (Active / Suspended / Banned) in bottom sheet

**Create User button (FAB, bottom-right):**
- Opens bottom sheet form:
  - Name, Email, Password fields
  - Role: locked to `User` only (Admin cannot create Admins)
  - "Create Account" CTA

---

### 12.5 Screen: Admin User Detail

**Header:** Back arrow + "User Profile"

**Top card:**
- Large avatar (80dp) + name + email + location
- Status badge + role badge
- Member since date
- Verified seller badge (if applicable)

**Stats row:** Active Listings | Completed Deals | Total Orders | Eco Score

**Action buttons row:**
```
[Verify Seller]  [Suspend]  [Ban]  [Reset Password]  [Delete Account]
```
- Each action shows a confirmation bottom sheet before executing
- "Verify Seller" only shown if user is unverified and has submitted a request

**Listing section:** scrollable grid of user's active listings

---

### 12.6 Screen: Admin Categories

**Header:** "Categories" + "New Category" button (top-right, green)

**Category tree list:**
Each parent category row (expandable):
```
┌────────────────────────────────────────┐
│ ▶  Electronics            [Edit] [Del] │
│    └─ Smartphones          [Edit] [Del] │
│    └─ Drones               [Edit] [Del] │
│    └─ Controllers          [Edit] [Del] │
└────────────────────────────────────────┘
```
- Tap `▶` to expand/collapse subcategories
- "Edit" → inline edit mode (name field appears in-place)
- "Del" → confirmation bottom sheet → delete
- "Add Subcategory" link under each parent

**New Category bottom sheet:**
- Name input
- Parent category picker (optional — if none, creates parent category)
- "Save" primary CTA

---

### 12.7 Screen: Admin Content Moderation

**Header:** "Content Moderation"

**Filter tabs:**
```
[ Pending ] [ Flagged ] [ Approved ] [ Rejected ]
```

**Listing moderation card:**
```
┌─────────────────────────────────────────────┐
│ [Img]  DJI Drone Pro                        │
│        by sachet silwal                     │
│        Category: Cameras • Rs. 45,000       │
│        [PENDING] • Submitted 2 days ago     │
│  [View Listing]  [Approve]  [Reject]        │
└─────────────────────────────────────────────┘
```
- "View Listing" → pushes full product detail screen (read-only admin view)
- "Approve" → green confirmation toast
- "Reject" → bottom sheet asking for rejection reason (free text) → "Confirm Rejection"

---

### 12.8 Screen: Admin Transactions

**Header:** "Transactions"

> [!NOTE]
> For the Admin role, **financial summary cards are hidden**. Only the transaction log table is visible to protect sensitive data.

**Filter row:** Date range picker | Type dropdown (All / Buy / Rent / Swap) | Status dropdown

**Transaction list:**
```
┌───────────────────────────────────────────────────┐
│ TXN-0042  •  Buy                                  │
│ Drone ← sachet silwal → krish                     │
│ Rs. 14,000  •  COMPLETED  •  May 21, 2026         │
│                                       [View →]    │
└───────────────────────────────────────────────────┘
```

---

### 12.9 Screen: Admin Wallet Payouts

**Header:** "Wallet Payouts"

**Filter tabs:**
```
[ Pending ] [ Approved ] [ Rejected ] [ All ]
```

**Payout request card:**
```
┌────────────────────────────────────────────────┐
│ [Avatar]  krish                                │
│           Rs. 2,000.00 requested               │
│           Note: "Monthly withdrawal"           │
│           Requested 2 days ago • PENDING       │
│  [Approve Payout]        [Reject]              │
└────────────────────────────────────────────────┘
```
- "Approve Payout" → green confirmation bottom sheet → updates status
- "Reject" → bottom sheet with reason field

---

### 12.10 Screen: Admin Disputes (User Reports)

**Header:** "User Reports & Disputes"

**Stats row:** Open | In Review | Resolved

**Filter tabs:**
```
[ Open ] [ In Review ] [ Resolved ]
```

**Dispute card:**
```
┌────────────────────────────────────────────────┐
│ RPT-0017  •  Order Dispute                     │
│ Reporter: krish  •  Against: sachet silwal     │
│ "Item not as described — drone missing parts"  │
│ Submitted May 20, 2026  •  [OPEN]              │
│  [Mark In Review]  [Resolve]  [Dismiss]        │
└────────────────────────────────────────────────┘
```
- "Mark In Review" changes status, notifies both parties
- "Resolve" → bottom sheet with resolution note + "Close Dispute" CTA
- "Dismiss" → confirmation bottom sheet

---

### 12.11 Screen: Admin Reports Overview

**Header:** "Reports Overview"

**Metric cards row:**
```
┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│ Open        │ │ In Review   │ │ Resolved    │
│   N         │ │   N         │ │   N         │
└─────────────┘ └─────────────┘ └─────────────┘
```

**Report list:** Chronological list of dispute/report activity with filters by date and status. Each row links to the individual dispute detail.

---

## 13. Super Admin Panel — Mobile App Design

The Super Admin has all Admin capabilities **plus** exclusive screens for platform-wide analytics, financial overview, and system configuration.

### 13.1 Super Admin Navigation

Same drawer pattern as Admin, but with additional items and **no access restriction banner**.

**Drawer Sections:**

```
── MAIN ────────────────────────────
  🏠  Overview (Dashboard)
  👥  User Management  ← FULL access (incl. Admins)
  🏷️  Categories
  🛡️  Content Moderation

── OPERATIONS ──────────────────────
  💳  Transactions  ← WITH financial summary
  💰  Wallet Payouts
  📈  Analytics     ← EXCLUSIVE to Super Admin
  ⚙️  System Config  ← EXCLUSIVE to Super Admin

── OTHER ───────────────────────────
  👤  Profile Settings
  🚪  Logout
```

> [!WARNING]
> Direct navigation to `/admin/analytics` or `/admin/system-config` by an Admin role returns a **403 Access Denied** screen — enforce this on mobile too.

---

### 13.2 Screen: Super Admin Dashboard (Overview)

**Purpose:** High-level platform performance view — analytics-focused, not queue-focused.

**Header bar:**
- Hamburger menu (top-left)
- Title: "Platform Overview"
- `[SUPER ADMIN]` badge in deep purple

**Block A — KPI Cards Row (horizontal scroll, 4 cards):**
```
┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ 👥 Total     │ │ 🏷 Total     │ │ 💰 Monthly   │ │ ⚖️ Open      │
│   Users      │ │   Listings   │ │   Revenue    │ │   Disputes   │
│   N          │ │   N          │ │   Rs. X      │ │   N          │
└──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘
```
- Revenue card uses gold/amber accent to stand out
- Disputes card turns red if count > 0

**Block B — Platform Revenue Trend Chart:**
- Line chart: last 6 months of revenue
- X-axis: month labels; Y-axis: Rs. amounts
- Interactive: tap a point to see exact value tooltip

**Block C — Transaction Mix Chart:**
- Pie / Donut chart with 3 segments:
  - Purchase (green)
  - Rental (blue)
  - Swap (purple)
- Legend below with percentages

**Block D — User Growth Chart:**
- Bar chart: new user registrations per month (last 6 months)
- Green bars

**Block E — Pending Moderation & Recent Disputes Widgets:**
- Quick-glance cards (same as Admin overview but not the primary focus)

---

### 13.3 Screen: Super Admin User Management

**Same as Admin User Management PLUS:**

**No access restriction banner** — full directory visible including Admin and Super Admin accounts.

**User card differences:**
```
┌─────────────────────────────────────────────────┐
│ [Avatar]  Prabesh GRG                           │
│           grgprabesh88@gmail.com                │
│           Active • Joined 1 year ago            │
│           [ADMIN] ← editable dropdown           │
│  [View]  [Change Role ▾]  [Suspend]  [Delete]   │
└─────────────────────────────────────────────────┘
```

**"Change Role" dropdown (bottom sheet):**
```
○  User
○  Admin
●  Super Admin    ← current selection
```

**Create User / Admin form (expanded at top of page):**
- Name, Email, Password inputs
- Role selector: `User` | `Admin` | `Super Admin` ← all three options available
- "Create Account" primary CTA

---

### 13.4 Screen: Super Admin Transactions

**Same as Admin Transactions PLUS financial summary cards at top:**

**Financial KPI Row:**
```
┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐
│ 💳 Payments     │ │ ✅ Successful   │ │ 🛒 Completed    │
│    Total        │ │    Revenue      │ │    Buy Orders   │
│   Rs. X         │ │   Rs. X         │ │   N             │
└─────────────────┘ └─────────────────┘ └─────────────────┘
```
These cards are hidden for the Admin role; only visible to Super Admin.

---

### 13.5 Screen: Super Admin Analytics (EXCLUSIVE)

**Header:** "Platform Analytics" + "Export CSV" button (top-right)

**Date range selector:**
```
[ Last 7 Days ] [ Last Month ] [ Last 6 Months ] [ All Time ]
```

**Charts (vertically stacked, scrollable):**

1. **Revenue Over Time** — multi-line chart (Buy / Rent / Swap revenue as separate lines)
2. **Transaction Volume** — bar chart (daily/weekly count of transactions)
3. **User Growth** — area chart (cumulative users over time)
4. **Top Listed Categories** — horizontal bar chart (Electronics, Cameras, etc.)
5. **Eco Score Distribution** — histogram (user eco score ranges)

**"Export CSV" button:**
- Tap → bottom sheet: "Select Export Range" + "Confirm Export"
- On confirm: downloads CSV report to device

> [!NOTE]
> This entire screen is **403 Access Denied** for the Admin role.

---

### 13.6 Screen: Super Admin System Config (EXCLUSIVE)

**Header:** "System Configuration"

**Sections (vertically stacked, collapsible accordion cards):**

**Section 1 — Security Policies:**
- Password strength requirements (toggle: enforce special chars, min length stepper)
- Session timeout (number input in minutes)
- Max failed login attempts (number input)
- "Save Security Settings" CTA (bottom of section)

**Section 2 — Notification Settings:**
- Email notifications toggle (platform-wide)
- Push notification toggle
- Notification frequency selector (Instant / Batched Hourly / Batched Daily)

**Section 3 — Payment Gateways:**
- Platform fee % (number input with % suffix)
- Escrow release delay (number input in days)
- Security deposit policy (toggle: refundable / non-refundable)
- Minimum payout threshold (Rs. input)
- "Save Payment Settings" CTA

**Section 4 — Sustainability Guidelines:**
- Eco Score multipliers per transaction type (Buy / Rent / Swap weights)
- Eco Score cap limit (max score number)
- "Save Eco Settings" CTA

> [!NOTE]
> This entire screen is **403 Access Denied** for the Admin role.

---

## 14. Role Comparison Matrix

| Feature / Screen | User | Admin | Super Admin |
|---|:---:|:---:|:---:|
| Marketplace Browse | ✅ | — | — |
| Create Listing | ✅ | — | — |
| Buy / Rent / Swap | ✅ | — | — |
| Wallet & Payouts | ✅ | — | — |
| Wishlist | ✅ | — | — |
| Admin Dashboard (Queue View) | — | ✅ | — |
| Super Admin Dashboard (Analytics View) | — | — | ✅ |
| User Management (Regular Users only) | — | ✅ | ✅ |
| User Management (Admins + Super Admins) | — | ❌ | ✅ |
| Change User Roles | — | ❌ | ✅ |
| Create Admin Accounts | — | ❌ | ✅ |
| Categories Management | — | ✅ | ✅ |
| Content Moderation | — | ✅ | ✅ |
| Transactions (List only) | — | ✅ | ✅ |
| Transactions (Financial Summary) | — | ❌ | ✅ |
| Wallet Payouts (Approve/Reject) | — | ✅ | ✅ |
| Disputes / Reports | — | ✅ | ✅ |
| **Analytics Page** | — | ❌ (403) | ✅ |
| **System Config** | — | ❌ (403) | ✅ |

---

## 15. Updated Screen Summary (All Roles)

| # | Screen | Role | Route |
|---|---|---|---|
| 1 | Splash / Onboarding | User | `/splash` |
| 2 | Login | All | `/login` |
| 3 | Register | User | `/register` |
| 4 | Dashboard Overview | User | `/home` |
| 5 | Marketplace Browse | User | `/marketplace` |
| 6 | Product Detail | User | `/product/:id` |
| 7 | Create Listing (3 steps) | User | `/list/*` |
| 8 | My Orders | User | `/activity/orders` |
| 9 | Rentals | User | `/activity/rentals` |
| 10 | Swaps | User | `/activity/swaps` |
| 11 | Profile & Wallet | User | `/profile` |
| 12 | Wishlist | User | `/profile/wishlist` |
| 13 | Notifications | User | `/profile/notifications` |
| 14 | Profile Settings | User | `/profile/settings` |
| 15 | Public Seller Profile | User | `/user/:id` |
| 16 | Admin Dashboard | Admin | `/admin/dashboard` |
| 17 | Admin User Management | Admin | `/admin/users` |
| 18 | Admin User Detail | Admin | `/admin/users/:id` |
| 19 | Admin Categories | Admin | `/admin/categories` |
| 20 | Admin Content Moderation | Admin | `/admin/moderation` |
| 21 | Admin Transactions | Admin | `/admin/transactions` |
| 22 | Admin Wallet Payouts | Admin | `/admin/payouts` |
| 23 | Admin Disputes | Admin | `/admin/disputes` |
| 24 | Admin Reports | Admin | `/admin/reports` |
| 25 | Super Admin Dashboard | Super Admin | `/admin/dashboard` |
| 26 | Super Admin Full User Mgmt | Super Admin | `/admin/users` |
| 27 | Super Admin Transactions + Financials | Super Admin | `/admin/transactions` |
| 28 | **Analytics** | Super Admin only | `/admin/analytics` |
| 29 | **System Config** | Super Admin only | `/admin/system-config` |

---

*End of PUNAP Mobile App UI Design Plan*
