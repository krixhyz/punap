# PUNAP — Full-Stack Rebuild Migration Plan

> **Repo:** `~/Desktop/PPPPP/FYP/` — the existing repo is rewritten in place  
> **Structure:** New code lives in `apps/` and `packages/` added to this repo. Existing Laravel files stay at root as the reference implementation and are deleted only after the rewrite is complete.  
> **Reference:** `docs/migration_plan.md` (this file)  
> **Rule:** Each session must leave the repo in a runnable, committable state. No half-finished modules.

---

## Stack Reference

| Layer | Package | Version |
|---|---|---|
| Runtime | Node.js LTS | 22.x |
| Backend framework | `@nestjs/core` | 11.1.26 |
| ORM | `prisma` / `@prisma/client` | 7.8.0 |
| Database | PostgreSQL | 16+ |
| Monorepo | `turbo` | 2.9.18 |
| Language | `typescript` | 6.0.3 |
| Web framework | `react` + `react-dom` | 19.2.7 |
| Web build | `vite` | 8.0.16 |
| Server state | `@tanstack/react-query` | 5.101.0 |
| Client state | `zustand` | 5.0.14 |
| Mobile SDK | `expo` | 56.0.11 |
| Mobile RN | `react-native` | 0.86.0 |
| Mobile styling | `nativewind` | 4.2.5 |
| WebSockets | `socket.io` + `@nestjs/websockets` | 4.8.3 / 11.1.26 |
| Auth | `@nestjs/jwt` + `@nestjs/passport` | 11.0.2 / 11.0.5 |
| Validation | `class-validator` + `class-transformer` | 0.15.1 / 0.5.1 |

---

## Session Index

| # | Title | Phase | Status |
|---|---|---|---|
| 01 | Monorepo Scaffold | Backend | `Done` |
| 02 | Prisma Schema + NestJS Bootstrap | Backend | `Done` |
| 03 | Auth Module | Backend | `Not Started` |
| 04 | Products + Categories + File Storage | Backend | `Not Started` |
| 05 | Orders Module | Backend | `Not Started` |
| 06 | Rental Module | Backend | `Not Started` |
| 07 | Payments Module (Khalti + eSewa) | Backend | `Not Started` |
| 08 | Swap Module | Backend | `Not Started` |
| 09 | Wallet + Ledger + Payouts | Backend | `Not Started` |
| 10 | Reviews + Disputes + Eco Score | Backend | `Not Started` |
| 11 | Notifications (Socket.IO Gateway) | Backend | `Not Started` |
| 12 | Search + Discovery + Location | Backend | `Not Started` |
| 13 | Admin Module | Backend | `Not Started` |
| 14 | React Web Scaffold | Web | `Not Started` |
| 15 | Web — Auth Pages | Web | `Not Started` |
| 16 | Web — Discovery + Product Detail | Web | `Not Started` |
| 17 | Web — Orders + Rentals | Web | `Not Started` |
| 18 | Web — Swap Flow | Web | `Not Started` |
| 19 | Web — Profile + Wallet + Reviews | Web | `Not Started` |
| 20 | Web — Disputes + Admin Panel | Web | `Not Started` |
| 21 | Expo Mobile Scaffold + Auth | Mobile | `Not Started` |
| 22 | Mobile — Discovery + Product Detail | Mobile | `Not Started` |
| 23 | Mobile — Orders + Rentals | Mobile | `Not Started` |
| 24 | Mobile — Swaps + Notifications | Mobile | `Not Started` |
| 25 | Mobile — Profile + Wallet + Polish | Mobile | `Not Started` |

---

## Dependency Graph

```
01 → 02 → 03 → 04 → 05 ─┐
               ↓         ├→ 07 → 08 → 09 → 10 → 11 → 12 → 13
               06 ───────┘
13 → 14 → 15 → 16 → 17 → 18 → 19 → 20
13 → 21 → 22 → 23 → 24 → 25
```

---

---

## Session 01 — Monorepo Scaffold

> **Status:** `Done`  
> **Depends on:** nothing  
> **Repo location:** `~/Desktop/PPPPP/FYP/` (existing repo, adding monorepo tooling)

### What this session builds

Turborepo monorepo tooling added to the existing FYP repo. New `apps/` and
`packages/` directories created alongside the existing Laravel files. The
Laravel code is left completely untouched — it is the reference implementation
for the rewrite. Nothing runs yet except `turbo build` passing on empty stubs.

### Directory layout after this session

```
FYP/                          ← existing repo root (unchanged Laravel files here)
├── app/                      ← existing Laravel (untouched)
├── database/                 ← existing Laravel (untouched)
├── routes/                   ← existing Laravel (untouched)
├── resources/                ← existing Laravel (untouched)
├── apps/                     ← NEW
│   ├── api/                  ← NestJS backend (built in sessions 02–13)
│   ├── web/                  ← React frontend (built in sessions 14–20)
│   └── mobile/               ← Expo app (built in sessions 21–25)
├── packages/                 ← NEW
│   ├── types/                ← shared Prisma-generated types
│   └── utils/                ← shared money/date helpers
├── turbo.json                ← NEW
├── package.json              ← UPDATED to add workspaces + turbo
└── docker-compose.yml        ← NEW (Postgres for new API)
```

### Tasks

- [ ] Update root `package.json`
  - [ ] Add `"workspaces": ["apps/*", "packages/*"]`
  - [ ] Add `turbo@2.9.18` as dev dependency
  - [ ] Keep all existing Laravel-related scripts untouched
- [ ] Create `turbo.json` with `build`, `dev`, `lint`, `test` pipelines
- [ ] Scaffold `apps/api/`
  - [ ] `package.json` (name: `@punap/api`)
  - [ ] `tsconfig.json` extending root
  - [ ] `src/main.ts` stub
- [ ] Scaffold `apps/web/`
  - [ ] `package.json` (name: `@punap/web`)
  - [ ] Vite config stub
  - [ ] `tsconfig.json`
- [ ] Scaffold `apps/mobile/`
  - [ ] `package.json` (name: `@punap/mobile`)
  - [ ] `app.json` for Expo
  - [ ] `tsconfig.json`
- [ ] Scaffold `packages/types/`
  - [ ] `package.json` (name: `@punap/types`)
  - [ ] `src/index.ts` empty export
  - [ ] `tsconfig.json`
- [ ] Scaffold `packages/utils/`
  - [ ] `package.json` (name: `@punap/utils`)
  - [ ] `src/money.ts` — `toPaisa`, `toMoney` (round 2dp), `formatNPR`
  - [ ] `src/dates.ts` — `rentalOverlaps(a, b, c, d): boolean`
  - [ ] `src/index.ts` re-export
- [ ] Root `tsconfig.json` for monorepo (do not overwrite or conflict with existing Laravel config)
- [ ] Root `.eslintrc.js` + `.prettierrc` scoped to `apps/` and `packages/` only
- [ ] Update `.env.example` — append new env vars below existing Laravel vars (do not remove any)
- [ ] `docker-compose.yml` — Postgres 16 for the new API (separate from any existing DB config)
  - [ ] `POSTGRES_DB=punap_new`, port 5433 (avoid clashing with any existing Postgres on 5432)
- [ ] Update `.gitignore` — add `.turbo`, `apps/*/dist`, `packages/*/dist`

### Done condition

- [ ] `npm install` from repo root completes with no errors
- [ ] `turbo build` exits 0 (stubs only)
- [ ] `docker compose up -d` starts Postgres and `docker compose ps` shows it healthy
- [ ] Existing Laravel `php artisan` commands still work (composer deps untouched)
- [ ] `packages/utils` unit test passes: `toPaisa(10.5) === 1050`, `rentalOverlaps` correct

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 01 — Monorepo Scaffold.
The working directory is ~/Desktop/PPPPP/FYP/ — this is an existing Laravel repo.
Add monorepo tooling (Turborepo) to it. Create apps/ and packages/ directories
alongside the existing Laravel files. Do NOT touch or delete any existing Laravel
files (app/, database/, routes/, resources/, composer.json, etc.).
Use exact package versions from the Stack Reference table in the plan.
Use port 5433 for the new Postgres docker container to avoid conflicts.
```

---

## Session 02 — Prisma Schema + NestJS Bootstrap

> **Status:** `Not Started`  
> **Depends on:** Session 01  
> **Repo location:** `apps/api/`

### What this session builds

The complete Prisma schema (all 30+ models from the improved design),
first migration against local Postgres, a seed script for lookup data,
and a fully configured NestJS application shell that starts without errors
and connects to the database.

### Tasks

- [ ] Install NestJS in `apps/api/`
  - [ ] `@nestjs/core`, `@nestjs/common`, `@nestjs/platform-express` at 11.1.26
  - [ ] `@nestjs/config` for env var management
  - [ ] `reflect-metadata`, `rxjs`
- [ ] Install Prisma
  - [ ] `prisma` + `@prisma/client` at 7.8.0
  - [ ] `npx prisma init` inside `apps/api/`
- [ ] Write `prisma/schema.prisma` — full improved schema including:
  - [ ] `User`, `RefreshToken`, `EmailVerificationToken`, `PasswordResetToken`
  - [ ] `Province`, `City`
  - [ ] `Category` (self-relation for parent/child, `ecoPoints` field)
  - [ ] `Product` with inline rental config fields, `transactionTypes String[]`, `deletedAt`
  - [ ] `Order`, `RentalBooking`, `RentalDeposit`
  - [ ] `SwapRequest`, `SwapNegotiationEvent`, `SwapOrderConfirmation`, `Swap`
  - [ ] `Payment` (polymorphic `sourceType`/`sourceId`, `pidx`, clean amount fields)
  - [ ] `Wallet`, `WalletLedgerEntry`, `PayoutRequest`
  - [ ] `Review`, `Dispute`
  - [ ] `Notification`, `UserEcoScore`, `Wishlist`, `RecentlyViewed`, `PlatformSetting`
  - [ ] All indexes from the schema plan (overlap check, listing query, payment source)
- [ ] Run `npx prisma migrate dev --name init`
- [ ] Write `prisma/seed.ts`
  - [ ] 77 provinces and cities for Nepal (at minimum Bagmati, Gandaki, Lumbini provinces with major cities)
  - [ ] Default categories: Electronics, Clothing, Furniture, Books, Sports, Vehicles, Tools, Other — each with `ecoPoints` value
  - [ ] Platform wallet row (`walletType: PLATFORM, userId: null`)
  - [ ] Default `PlatformSetting` rows: `commission_percent: 3.0`, `swap_fee_enabled: false`
  - [ ] One super-admin user (credentials from `.env`)
- [ ] Run `npx prisma db seed` and verify rows
- [ ] NestJS app bootstrap
  - [ ] `AppModule` with `ConfigModule.forRoot({ isGlobal: true })`
  - [ ] `PrismaModule` (global) with `PrismaService` extending `PrismaClient`
    - [ ] `onModuleInit()` calls `$connect()`
    - [ ] `enableShutdownHooks(app)` on app ref
  - [ ] `main.ts`: `ValidationPipe` (global, `whitelist: true`, `transform: true`), CORS, port from env
  - [ ] Health check endpoint `GET /health` returns `{ status: 'ok', db: 'connected' }`
- [ ] Export Prisma-generated types from `packages/types/src/index.ts`

### Done condition

- [ ] `npx prisma migrate status` shows all migrations applied
- [ ] `npx prisma db seed` completes without errors
- [ ] `npx prisma studio` opens and all tables are visible with seed data
- [ ] `npm run dev` in `apps/api/` starts on port 3001
- [ ] `curl http://localhost:3001/health` returns `{ "status": "ok", "db": "connected" }`

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 02 — Prisma Schema + NestJS Bootstrap.
Working directory: ~/Desktop/PPPPP/FYP/
New code goes in apps/api/ — do not touch existing Laravel files at root.
The improved Prisma schema is fully described in the migration plan above.
Key schema decisions: rental config inlined into Product, rental_requests + rented_rentals
merged into RentalBooking, payments use sourceType/sourceId polymorphic, swap_requests
drops counter_amount/counter_message/countered_at (use SwapNegotiationEvent instead),
disputes have respondentId + favoredUserId instead of enum.
Postgres is running on port 5433 via docker-compose from Session 01.
DATABASE_URL in apps/api/.env should use port 5433.
The existing Laravel database on 5432 must NOT be touched.
```

---

## Session 03 — Auth Module

> **Status:** `Not Started`  
> **Depends on:** Session 02  
> **Repo location:** `apps/api/src/auth/`

### What this session builds

Complete authentication: registration, login, token refresh, logout,
email verification, and password reset. JWT access tokens (15 min TTL)
+ refresh tokens stored hashed in the DB (7 day TTL). All auth guards
and role decorators used by every subsequent session.

### Tasks

- [ ] Install auth packages
  - [ ] `@nestjs/jwt@11.0.2`, `@nestjs/passport@11.0.5`, `passport`, `passport-jwt`
  - [ ] `bcrypt` + `@types/bcrypt`
  - [ ] `nodemailer` + `@types/nodemailer` (or `@nestjs-modules/mailer` with Handlebars)
  - [ ] `crypto` (built-in Node, no install needed)
- [ ] `AuthModule` structure
  - [ ] `AuthController` — 7 endpoints (see below)
  - [ ] `AuthService` — business logic
  - [ ] `JwtStrategy` (Passport) — validates access token, attaches user to request
  - [ ] `JwtAuthGuard` — extends `AuthGuard('jwt')`, exported for all modules
  - [ ] `OptionalJwtAuthGuard` — same but doesn't throw on missing token
  - [ ] `RolesGuard` — reads `@Roles(...)` metadata, checks `req.user.role`
  - [ ] `@Roles(...)` decorator
  - [ ] `@CurrentUser()` param decorator
  - [ ] `MailService` — `sendVerificationEmail()`, `sendPasswordResetEmail()` via Nodemailer
- [ ] Endpoints
  - [ ] `POST /auth/register` — hash password, create user, create+send email verification token
  - [ ] `POST /auth/login` — verify credentials, check `accountStatus` (reject suspended/banned), issue access + refresh token pair; set refresh token as HttpOnly cookie
  - [ ] `POST /auth/refresh` — read HttpOnly cookie, verify token hash against DB, rotate (revoke old, issue new), return new access token
  - [ ] `POST /auth/logout` — revoke refresh token in DB, clear cookie
  - [ ] `POST /auth/verify-email` — body: `{ token }`, mark `emailVerifiedAt`, delete token row
  - [ ] `POST /auth/resend-verification` — rate-limited, regenerate + resend
  - [ ] `POST /auth/forgot-password` — generate reset token, send email (always returns 200 regardless of email existence)
  - [ ] `POST /auth/reset-password` — body: `{ token, newPassword }`, hash + update, revoke all refresh tokens for user
- [ ] DTOs for all endpoints with `class-validator` decorators
- [ ] Token helpers in `AuthService`
  - [ ] `issueTokenPair(userId)` — creates access JWT + stores hashed refresh token
  - [ ] `revokeRefreshToken(tokenHash)`
  - [ ] `revokeAllRefreshTokens(userId)` — used after password reset
- [ ] Guard and decorator barrel exports from `auth/index.ts`
- [ ] Unit tests for `AuthService` (mock PrismaService)
  - [ ] Register: duplicate email throws
  - [ ] Login: wrong password throws, suspended account throws
  - [ ] Refresh: revoked token throws, expired token throws

### Done condition

- [ ] `POST /auth/register` creates a user row, sends email (check Nodemailer console transport in dev)
- [ ] `POST /auth/login` returns `{ accessToken }` and sets `refresh_token` cookie
- [ ] `POST /auth/refresh` with valid cookie returns new `accessToken`
- [ ] `POST /auth/logout` clears the cookie and marks token revoked in DB
- [ ] A route decorated with `@UseGuards(JwtAuthGuard)` returns 401 without token and 200 with valid token
- [ ] A route decorated with `@Roles('ADMIN')` returns 403 for a USER-role token
- [ ] `npm test` in `apps/api/` passes auth unit tests

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 03 — Auth Module.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/api/src/auth/.
Reference the existing Laravel auth logic in FYP/app/Http/Controllers/Auth/ for
business rules (account status check, password reset always returns 200, etc.).
Use HttpOnly cookies for refresh tokens (web) — mobile clients will handle the
refresh token in the response body via a separate endpoint flag or just read the
Set-Cookie header.
Database and NestJS shell are ready from Session 02.
```

---

## Session 04 — Products + Categories + File Storage

> **Status:** `Not Started`  
> **Depends on:** Session 03  
> **Repo location:** `apps/api/src/products/`, `apps/api/src/categories/`, `apps/api/src/storage/`

### What this session builds

Category CRUD (tree structure), full product CRUD with image upload,
product approval flow, and a `ProductDeletionGuard`. The storage service
abstracts Multer — writes to local disk in dev, S3-compatible in production
via the same interface.

### Tasks

- [ ] Install file handling packages
  - [ ] `@nestjs/platform-express` (already in), `multer`, `@types/multer`
  - [ ] `@aws-sdk/client-s3` + `@aws-sdk/lib-storage` (for production S3 path)
  - [ ] `uuid` for generating storage keys
- [ ] `StorageModule` (global)
  - [ ] `StorageService` interface: `upload(file): Promise<string>`, `uploadMany(files): Promise<string[]>`, `delete(url): Promise<void>`
  - [ ] `LocalStorageStrategy` — saves to `public/uploads/`, returns relative URL
  - [ ] `S3StorageStrategy` — uploads to configured bucket, returns CDN URL
  - [ ] Strategy selected by `STORAGE_DRIVER` env var (`local` | `s3`)
  - [ ] `MulterConfigService` — limits: 5MB per file, max 8 files, image MIME types only
- [ ] `CategoriesModule`
  - [ ] `GET /categories` — full tree (parent + children)
  - [ ] `GET /categories/:id`
  - [ ] `GET /categories/:parentId/subcategories`
  - [ ] `POST /categories` — admin only
  - [ ] `PATCH /categories/:id` — admin only
  - [ ] `DELETE /categories/:id` — admin only, guard: no products linked
  - [ ] `ecoPoints` field on category (used by EcoScoreService later)
- [ ] `ProductsModule`
  - [ ] `GET /products` — paginated list with filters:
    - [ ] `category`, `transactionType` (BUY/RENT/SWAP), `condition`, `minPrice`, `maxPrice`, `provinceId`, `cityId`, `keyword` (title ILIKE), `sellerId`
    - [ ] Exclude `deletedAt IS NOT NULL` and `approvalStatus != APPROVED` for public queries
    - [ ] Cursor-based or offset pagination (decide: offset for simplicity)
  - [ ] `GET /products/:id` — include seller info, category, review summary
  - [ ] `POST /products` — auth required, multipart/form-data (images via `StorageService`)
  - [ ] `PATCH /products/:id` — owner only (guard), can update images (delete old, upload new)
  - [ ] `DELETE /products/:id` — owner only, soft delete (`deletedAt = now()`)
    - [ ] `ProductDeletionGuard`: block if active `RentalBooking` or unpaid `Order` exists
  - [ ] `PATCH /products/:id/approve` — admin only, sets `approvalStatus = APPROVED`
  - [ ] `PATCH /products/:id/reject` — admin only, sets `approvalStatus = REJECTED`
  - [ ] `GET /products/my` — current user's listings (all statuses)
- [ ] `ProductOwnerGuard` — verifies `req.user.id === product.sellerId`
- [ ] Response DTOs (serialize only safe fields — exclude internal flags)

### Done condition

- [ ] `POST /categories` (as admin) creates a category
- [ ] `GET /categories` returns tree with children nested
- [ ] `POST /products` with `multipart/form-data` (3 images + fields) creates product, images saved to `public/uploads/`
- [ ] `GET /products?transactionType=RENT&minPrice=100` returns only matching products
- [ ] `DELETE /products/:id` soft-deletes (sets `deletedAt`), product disappears from `GET /products` but row remains in DB
- [ ] `PATCH /products/:id/approve` as non-admin returns 403

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 04 — Products + Categories + File Storage.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/api/src/.
Reference app/Http/Controllers/User/ProductController.php for existing business rules.
Key schema note: Product.transactionTypes is a String[] (Postgres text array),
rental config fields (rentFare, rentDeposit, rentType, availableFrom, availableDuration)
are columns on Product directly — there is no separate rentals config table.
Auth guards from Session 03 are available.
```

---

## Session 05 — Orders Module

> **Status:** `Not Started`  
> **Depends on:** Session 04  
> **Repo location:** `apps/api/src/orders/`

### What this session builds

The complete buy/sell flow: cart-free direct checkout, `CheckoutPricingService`
(3% buyer fee), inventory reservation with TTL expiry, order lifecycle
(PENDING → PAID → COMPLETED / CANCELLED), and seller wallet credit
after payment (the credit call will be wired in Session 09 but the hook
point is established here).

### Tasks

- [ ] `CheckoutPricingService` (port from Laravel, see `FYP/app/Services/CheckoutPricingService.php`)
  - [ ] `calculatePurchase(subtotal)` — 3% fee, returns `{ subtotal, serviceFee, totalAmount, sellerAmount, platformAmount, feePercentage }`
  - [ ] Fee percentage read from `PlatformSetting` table (`commission_percent` key), fallback to 3.0
- [ ] `InventoryReservationService`
  - [ ] `reserve(productId, quantity, ttlMinutes)` — decrements available quantity, sets `reservedUntil`
  - [ ] `release(orderId)` — restores quantity when reservation expires or order cancelled
  - [ ] Cron job (or `@nestjs/schedule` interval): release expired reservations every 5 min
- [ ] `OrdersModule`
  - [ ] `POST /orders` — body: `{ productId, quantity }`
    - [ ] Validate product exists, is APPROVED, has `BUY` in transactionTypes, has stock
    - [ ] Calculate pricing via `CheckoutPricingService`
    - [ ] Create `Order` (status: PENDING), reserve inventory
    - [ ] Return order with `totalAmount` (frontend redirects to payment)
  - [ ] `GET /orders` — buyer's orders (paginated)
  - [ ] `GET /orders/selling` — orders for current user's products
  - [ ] `GET /orders/:id` — detail (buyer or product owner only)
  - [ ] `POST /orders/:id/cancel` — buyer or seller can cancel PENDING order, releases inventory
  - [ ] `PATCH /orders/:id/complete` — seller marks as completed (post physical handoff)
- [ ] `OrderOwnerGuard` — allows buyer or product owner
- [ ] Response DTO includes product snapshot (title, image) so UI doesn't need a second request

### Done condition

- [ ] `POST /orders` creates an order with correct `totalAmount` (subtotal × 1.03 rounded to 2dp)
- [ ] Creating an order reduces `Product.quantity` by the ordered amount
- [ ] `POST /orders/:id/cancel` restores quantity
- [ ] `GET /orders` returns paginated list scoped to the calling user
- [ ] `GET /orders/selling` returns orders scoped to the calling user's products

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 05 — Orders Module.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/api/src/orders/.
Reference app/Http/Controllers/User/OrderController.php and
app/Services/CheckoutPricingService.php.
Important: there is no cart table. Checkout is direct (productId + quantity → order).
The payment initiation step is NOT in this session — it comes in Session 07.
This session only creates the order and reserves inventory.
```

---

## Session 06 — Rental Module

> **Status:** `Not Started`  
> **Depends on:** Session 04  
> **Repo location:** `apps/api/src/rentals/`

### What this session builds

The full rental lifecycle from booking request through active rental to
return confirmation. The overlap check query, deposit tracking, and the
return evidence flow. Payment initiation hook point established for Session 07.

### Tasks

- [ ] `RentalsModule`
  - [ ] `POST /rentals/book` — body: `{ productId, startDate, endDate, message? }`
    - [ ] Validate product has `RENT` in transactionTypes
    - [ ] Overlap check: query `RentalBooking` where `productId = ?` AND status IN `[PENDING_PAYMENT, ACTIVE]` AND `startDate < req.endDate AND endDate > req.startDate`
    - [ ] Calculate pricing: `rentFare × days × rentType multiplier` + deposit + 3% fee (via `CheckoutPricingService.calculateRent`)
    - [ ] Create `RentalBooking` (status: PENDING_PAYMENT), reserve stock (`stockReserved = true`, `reservedUntil = now + 30min`)
    - [ ] Return booking with pricing breakdown
  - [ ] `GET /rentals` — renter's bookings
  - [ ] `GET /rentals/lending` — owner's bookings (products the user listed for rent)
  - [ ] `GET /rentals/:id` — detail (renter or owner only)
  - [ ] `POST /rentals/:id/cancel` — cancels PENDING_PAYMENT booking, releases stock
  - [ ] `POST /rentals/:id/request-return` — renter initiates return, sets `returnRequestedAt`, uploads evidence photos
    - [ ] Only valid when status = ACTIVE
    - [ ] Accept multipart with evidence photos via `StorageService`
  - [ ] `POST /rentals/:id/confirm-return` — owner confirms receipt, sets `returnedAt`, status → COMPLETED
    - [ ] Triggers `RentalDeposit` refund logic (see below)
  - [ ] `POST /rentals/:id/dispute` — transitions to DISPUTED (wired to DisputeService in Session 10)
- [ ] `RentalDepositService` (port from `FYP/app/Services/RentalDepositRefundService.php`)
  - [ ] `hold(rentalBookingId, amount, paymentId)` — creates `RentalDeposit` in HELD status
  - [ ] `initiateRefund(rentalDepositId)` — calls gateway refund API (stub for now, fully wired in Session 07)
  - [ ] `markRefunded(rentalDepositId, reference)`
  - [ ] `forfeit(rentalDepositId, reason)`
- [ ] Cron job: expire PENDING_PAYMENT bookings older than 30 min (release stock)
- [ ] `RentalParticipantGuard` — allows renter or owner

### Done condition

- [ ] `POST /rentals/book` with overlapping dates on an already-active rental returns 409
- [ ] `POST /rentals/book` with non-overlapping dates creates booking
- [ ] Booking with `reservedUntil` in the past is cleaned up by the cron job (test with a manually aged row)
- [ ] `POST /rentals/:id/request-return` with a JPEG file saves the photo URL in `evidencePhotos`
- [ ] `POST /rentals/:id/confirm-return` transitions status to COMPLETED

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 06 — Rental Module.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/api/src/rentals/.
Reference app/Http/Controllers/User/RentalController.php and
app/Services/RentalDepositRefundService.php.
Key schema note: there is no separate `rentals` (config) table.
Rental config (rentFare, rentDeposit, rentType, availableFrom) lives on Product.
The table for bookings is `RentalBooking` (merged from old rental_requests + rented_rentals).
The overlap check index is on (productId, startDate, endDate, status).
```

---

## Session 07 — Payments Module

> **Status:** `Not Started`  
> **Depends on:** Sessions 05 and 06  
> **Repo location:** `apps/api/src/payments/`

### What this session builds

Khalti and eSewa payment services, payment initiation endpoints for all
three transaction types (order/rental/swap), callback handlers, and the
`PaymentResolutionService` that dispatches post-payment actions. Idempotency
guards prevent double-processing on duplicate callbacks.

### Tasks

- [ ] Install `axios` (for gateway HTTP calls)
- [ ] `KhaltiService` (port from `FYP/app/Services/KhaltiService.php`)
  - [ ] `initiatePayment(payload)` — POST to Khalti initiate URL, returns `{ pidx, paymentUrl }`
  - [ ] `lookupPayment(pidx)` — POST to Khalti lookup URL, returns status
  - [ ] `refundPayment(payload)` — POST to Khalti refund URL
  - [ ] `toPaisa(amount)` helper
  - [ ] All calls use `Authorization: Key <KHALTI_SECRET_KEY>` from env
- [ ] `EsewaService` (port from `FYP/app/Services/EsewaService.php`)
  - [ ] `getFormFields(payload)` — returns signed form fields for POST redirect
  - [ ] `verifyCallback(body)` — HMAC-SHA256 signature verification
  - [ ] All eSewa-specific fields (`product_code`, `service_charge`, etc.) stay INSIDE this service, never on the `Payment` model
- [ ] `PaymentResolutionService`
  - [ ] `resolve(paymentId)` — looks up `payment.sourceType`, dispatches to `OrderService.onPaymentComplete()`, `RentalService.onPaymentComplete()`, or `SwapService.onPaymentComplete()`
  - [ ] Idempotency: check `payment.status === COMPLETE` before re-processing
- [ ] `PaymentsModule`
  - [ ] `POST /payments/initiate/order/:orderId` — initiates payment for an Order
    - [ ] Creates `Payment` row (status: PENDING, sourceType: ORDER, sourceId: orderId)
    - [ ] Calls Khalti or eSewa based on `gateway` query param
    - [ ] Returns `{ paymentUrl }` (frontend redirects)
  - [ ] `POST /payments/initiate/rental/:rentalBookingId` — same for RentalBooking
  - [ ] `POST /payments/initiate/swap/:swapRequestId` — same for SwapRequest (cash-diff only)
  - [ ] `GET /payments/callback/khalti` — **public route** (no JWT)
    - [ ] Read `pidx` from query, call `KhaltiService.lookupPayment(pidx)`
    - [ ] If complete: update `Payment.status = COMPLETE`, call `PaymentResolutionService.resolve()`
    - [ ] Redirect to frontend success/failure URL
  - [ ] `POST /payments/callback/esewa` — **public route** (no JWT)
    - [ ] Verify HMAC signature
    - [ ] Same resolution flow as Khalti
  - [ ] `GET /payments/:id` — payment detail for the transaction participant
- [ ] `OrderService.onPaymentComplete(orderId)` stub (wired here, logic: set Order.status = PAID, reduce inventory permanently)
- [ ] `RentalService.onPaymentComplete(rentalBookingId)` stub: set status = ACTIVE, call `RentalDepositService.hold()`
- [ ] `SwapService.onPaymentComplete(swapRequestId)` stub: set status = PAID (full fund release in Session 08)

### Done condition

- [ ] `POST /payments/initiate/order/:orderId` returns a Khalti `paymentUrl` (test against Khalti sandbox)
- [ ] Simulating a Khalti callback with a valid test `pidx` updates `Payment.status` to COMPLETE and `Order.status` to PAID
- [ ] Duplicate callback with the same `pidx` does NOT create a second ledger entry (idempotency check)
- [ ] eSewa signature verification rejects a tampered payload

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 07 — Payments Module.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/api/src/payments/.
Reference app/Services/KhaltiService.php and app/Services/EsewaService.php.
Key design: Payment model has sourceType (ORDER/RENTAL/SWAP) + sourceId instead of
a direct order_id FK. The pidx column on Payment is indexed for fast Khalti callback lookup.
eSewa gateway-specific fields (product_code, service_charge, etc.) go in request_payload
JSON — NOT as columns on the Payment table.
Khalti sandbox credentials should come from .env (KHALTI_SECRET_KEY, KHALTI_INITIATE_URL, etc.).
```

---

## Session 08 — Swap Module

> **Status:** `Not Started`  
> **Depends on:** Session 07  
> **Repo location:** `apps/api/src/swaps/`

### What this session builds

The full swap negotiation FSM, dual-confirmation flow, and fund release
on both-confirmed. Every state transition writes a `SwapNegotiationEvent`
row. The `SwapOrderService` and `WalletLedgerService.releaseSwapFunds()`
are fully implemented here.

### Tasks

- [ ] `SwapRequestsModule`
  - [ ] `POST /swaps` — propose swap
    - [ ] Body: `{ productId, offeredProductId, message?, offeredAmount?, askedAmount?, moneyDirection? }`
    - [ ] Validate: both products exist and APPROVED; requester owns `offeredProduct`; cannot swap with own product
    - [ ] Create `SwapRequest` (status: PENDING) + write `SwapNegotiationEvent` (type: INITIAL_OFFER)
  - [ ] `POST /swaps/:id/counter` — counter-offer (owner or requester)
    - [ ] Body: `{ offeredAmount?, askedAmount?, moneyDirection?, message? }`
    - [ ] Status must be PENDING or COUNTERED
    - [ ] Update `SwapRequest` (status: COUNTERED, update amount fields)
    - [ ] Write `SwapNegotiationEvent` (type: COUNTER_OFFER)
    - [ ] **Do NOT touch** `counter_amount`/`counter_message`/`countered_at` — those columns don't exist in the new schema
  - [ ] `POST /swaps/:id/accept` — owner accepts
    - [ ] Status must be PENDING or COUNTERED
    - [ ] If `moneyDirection === NONE`: status → CONFIRMATION_PENDING, create `SwapOrderConfirmation` row
    - [ ] If cash involved: status → AWAITING_PAYMENT (payment initiated separately)
    - [ ] Write `SwapNegotiationEvent` (type: ACCEPT)
  - [ ] `POST /swaps/:id/reject` — owner rejects, write event
  - [ ] `POST /swaps/:id/cancel` — requester cancels, write event
  - [ ] `POST /swaps/:id/confirm-received` — renter OR requester confirms physical receipt
    - [ ] Update `SwapOrderConfirmation.ownerConfirmedAt` or `.requesterConfirmedAt`
    - [ ] If both confirmed → call `SwapOrderService.complete(swapRequestId)`
  - [ ] `GET /swaps` — all swaps for current user (as requester or owner), filterable by status
  - [ ] `GET /swaps/:id` — detail with negotiation history
  - [ ] `GET /swaps/:id/events` — full `SwapNegotiationEvent` log
- [ ] `SwapOrderService` (port from `FYP/app/Services/SwapOrderService.php`)
  - [ ] `complete(swapRequestId)` — Prisma `$transaction`:
    - [ ] Upsert immutable `Swap` record (status: COMPLETED)
    - [ ] Update `SwapRequest.status = COMPLETED`
    - [ ] Update `SwapOrderConfirmation.finalCompletedAt`
    - [ ] Call `WalletLedgerService.releaseSwapFunds()` (stub — wired in Session 09)
    - [ ] Update both product statuses to SWAPPED
- [ ] `SwapParticipantGuard` — owner or requester only

### Done condition

- [ ] Full happy path: propose → counter → accept (no cash) → both confirm → status becomes COMPLETED, both products status SWAPPED
- [ ] `GET /swaps/:id/events` returns all negotiation steps in order
- [ ] Attempting to counter an ACCEPTED swap returns 409
- [ ] Cannot propose a swap with a product you don't own

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 08 — Swap Module.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/api/src/swaps/.
Reference app/Services/SwapOrderService.php and app/Services/WalletLedgerService.php
(releaseSwapFunds method).
Critical schema note: swap_requests does NOT have counter_amount, counter_message,
or countered_at columns. All counter-offer history is in SwapNegotiationEvent.
To get the "current offer state", read the latest SwapNegotiationEvent for the swap.
WalletLedgerService.releaseSwapFunds() can be a stub that just logs for now —
it is fully implemented in Session 09.
```

---

## Session 09 — Wallet + Ledger + Payouts

> **Status:** `Not Started`  
> **Depends on:** Session 08  
> **Repo location:** `apps/api/src/wallet/`

### What this session builds

The double-entry ledger, `WalletLedgerService` fully ported, all wallet
endpoints, and payout request lifecycle. Also wires the stub credit calls
left in Sessions 05–08: order sale credit, rental sale credit, swap fund
release, and platform fee capture all flow through the ledger here.

### Tasks

- [ ] Install `@nestjs/schedule` for cleanup crons
- [ ] `WalletLedgerService` (full port from `FYP/app/Services/WalletLedgerService.php`)
  - [ ] `getOrCreateUserWallet(userId)`
  - [ ] `getOrCreatePlatformWallet()`
  - [ ] `creditSaleIfMissing(userId, amount, entryType, referenceType, referenceId, metadata?)` — idempotent credit
  - [ ] `creditPlatformFeeIfMissing(amount, entryType, referenceType, referenceId, metadata?)` — idempotent
  - [ ] `requestPayout(user, amount, note?)` — validates balance, holds amount, creates `PayoutRequest`
  - [ ] `approvePayout(payoutRequest, adminId, note?)`
  - [ ] `rejectPayout(payoutRequest, adminId, reason)` — releases held amount back to available
  - [ ] `markPayoutPaid(payoutRequest, adminId, reference, note?)`
  - [ ] `releaseSwapFunds(swapRequest)` — full implementation (replaces stub from Session 08)
  - [ ] All DB writes inside `prisma.$transaction()` with serializable isolation or `SELECT ... FOR UPDATE` via raw query
  - [ ] `toMoney(amount)` helper (2dp rounding)
- [ ] Wire post-payment credits
  - [ ] `OrderService.onPaymentComplete` → call `creditSaleIfMissing` for seller + `creditPlatformFeeIfMissing`
  - [ ] `RentalService.onPaymentComplete` → same pattern for rental payment
- [ ] `WalletModule`
  - [ ] `GET /wallet` — current user's wallet (balance, pending payout balance)
  - [ ] `GET /wallet/ledger` — paginated ledger entries (newest first)
  - [ ] `POST /wallet/payout` — body: `{ amount, note? }`, creates payout request
  - [ ] `GET /wallet/payouts` — user's payout request history
- [ ] Admin payout endpoints (in `WalletModule`, guard: ADMIN role)
  - [ ] `GET /admin/payouts` — all pending payout requests
  - [ ] `POST /admin/payouts/:id/approve`
  - [ ] `POST /admin/payouts/:id/reject` — body: `{ reason }`
  - [ ] `POST /admin/payouts/:id/mark-paid` — body: `{ payoutReference, note? }`

### Done condition

- [ ] After a completed order payment, seller's `wallet.availableBalance` increases by `sellerAmount`
- [ ] Platform wallet `availableBalance` increases by `platformAmount`
- [ ] `creditSaleIfMissing` called twice with the same `referenceId` only credits once (idempotency)
- [ ] `POST /wallet/payout` with amount > available balance returns 400
- [ ] Full payout lifecycle: request → admin approve → mark paid; ledger entries written at each step
- [ ] `releaseSwapFunds` credits the correct recipient based on `moneyDirection`

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 09 — Wallet + Ledger + Payouts.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/api/src/wallet/.
Reference app/Services/WalletLedgerService.php in full — port it exactly.
The key invariant: every wallet balance change has a corresponding WalletLedgerEntry.
Never update wallet balance without writing a ledger row in the same transaction.
Use Prisma's $transaction() for all multi-step writes.
The releaseSwapFunds stub from Session 08 SwapOrderService.complete() should now
call the real WalletLedgerService.releaseSwapFunds().
```

---

## Session 10 — Reviews + Disputes + Eco Score

> **Status:** `Not Started`  
> **Depends on:** Session 09  
> **Repo location:** `apps/api/src/reviews/`, `apps/api/src/disputes/`, `apps/api/src/eco-score/`

### What this session builds

Reviews (one per reviewer per transaction, transaction-bound), the dispute
lifecycle, and the eco score system. All three are relatively small modules
that are cleanly isolated from payments.

### Tasks

- [ ] `ReviewsModule`
  - [ ] `POST /reviews` — body: `{ subjectId, productId, transactionType, orderId?, rentalBookingId?, swapId?, rating, body? }`
    - [ ] Validate: transaction exists and is COMPLETED; reviewer was a participant; hasn't reviewed this transaction yet (unique constraint will catch it, but give a 409 not 500)
    - [ ] Create `Review` with `productId` denormalized
  - [ ] `GET /reviews/product/:productId` — all reviews for a product (paginated)
  - [ ] `GET /reviews/user/:userId` — reviews received by a user
  - [ ] `GET /reviews/my` — reviews the calling user has written
- [ ] `DisputesModule`
  - [ ] `POST /disputes` — body: `{ respondentId, transactionType, orderId?, rentalBookingId?, swapId?, subject, description, evidencePhotos[]? }`
    - [ ] Validate participation
    - [ ] Transition transaction status to DISPUTED
  - [ ] `GET /disputes/my` — disputes the user opened
  - [ ] `GET /disputes/:id` — detail (participants + admin)
  - [ ] `PATCH /disputes/:id/resolve` — admin only; body: `{ resolution, favoredUserId?, rentalClaimAmount? }`
    - [ ] Sets `status = RESOLVED`, `resolvedAt`, `resolvedBy`, `favoredUserId`
  - [ ] `PATCH /disputes/:id/dismiss` — admin only
- [ ] `EcoScoreService` (port from `FYP/app/Services/EcoScoreService.php`)
  - [ ] `calculateEcoScore(product, condition, transactionType)` — same formula: `baseEcoPoints × conditionMultiplier × transactionMultiplier`
  - [ ] `recordEcoImpact(product, transactionType, userId, transactionId?)` — idempotent upsert, syncs `User.totalEcoScore` + `User.ecoLevel`
  - [ ] Called automatically from `OrderService.onPaymentComplete`, `RentalService.onPaymentComplete`, `SwapOrderService.complete`
  - [ ] `GET /eco-score/levels` — returns the tier thresholds (None / Bronze / Silver / Gold / Platinum)
  - [ ] `GET /eco-score/my` — current user's score + level + history

### Done condition

- [ ] Two review attempts on the same completed order return 201 then 409
- [ ] `GET /reviews/product/:productId` returns reviews with author name and rating
- [ ] After a completed order, `EcoScoreService.recordEcoImpact` is called and `User.totalEcoScore` increases
- [ ] `POST /disputes` transitions the related `Order` / `RentalBooking` to DISPUTED status
- [ ] Admin `PATCH /disputes/:id/resolve` sets `favoredUserId` (not a string enum)

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 10 — Reviews + Disputes + Eco Score.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/api/src/reviews/, disputes/, eco-score/.
Reference app/Services/EcoScoreService.php for the scoring formula.
Key schema note: Review has a productId column (denormalized) for product-level queries.
Disputes have respondentId (explicit FK to User) and favoredUserId (nullable FK to User)
instead of the old ambiguous enum. The old rental_request_id FK does not exist —
use rentalBookingId instead.
```

---

## Session 11 — Notifications Module

> **Status:** `Not Started`  
> **Depends on:** Session 10  
> **Repo location:** `apps/api/src/notifications/`

### What this session builds

The Socket.IO WebSocket gateway, `WsJwtGuard`, per-user rooms, a
`NotificationService` that persists to DB and emits simultaneously,
and all the event wires into the modules built in Sessions 03–10.

### Tasks

- [ ] Install
  - [ ] `@nestjs/websockets@11.1.26`, `@nestjs/platform-socket.io@11.1.26`, `socket.io@4.8.3`
- [ ] `NotificationsModule`
  - [ ] `NotificationGateway` (extends `@WebSocketGateway`)
    - [ ] `handleConnection(client)` — read JWT from `client.handshake.auth.token`, verify, join client to `user:{userId}` room
    - [ ] `handleDisconnect(client)` — cleanup
    - [ ] `WsJwtGuard` — validates JWT on WebSocket handshake
  - [ ] `NotificationService`
    - [ ] `send(userId, type, title, body, data?)` — persists `Notification` row + emits to `user:{userId}` room
    - [ ] `markRead(notificationId, userId)`
    - [ ] `markAllRead(userId)`
  - [ ] REST endpoints (for notification inbox on page load)
    - [ ] `GET /notifications` — unread + recent (paginated, newest first)
    - [ ] `GET /notifications/count` — unread count
    - [ ] `PATCH /notifications/:id/read`
    - [ ] `PATCH /notifications/read-all`
- [ ] Wire `NotificationService` into other modules
  - [ ] Auth: `user.registered` on registration, `password.changed` on reset
  - [ ] Orders: `order.paid` to seller, `order.completed` to buyer
  - [ ] Rentals: `rental.active` to renter+owner, `rental.return_requested`, `rental.completed`
  - [ ] Swaps: `swap.counter_offer` to the other party, `swap.accepted`, `swap.completed`
  - [ ] Disputes: `dispute.opened` to respondent + admins, `dispute.resolved` to both parties
  - [ ] Payments: `payment.failed` to payer
  - [ ] Payouts: `payout.approved`, `payout.rejected`, `payout.paid`
- [ ] Configure `socket.io` adapter in `main.ts`
- [ ] Socket.IO CORS: allow web app origin from env

### Done condition

- [ ] Client connects via `io('http://localhost:3001', { auth: { token: '<jwt>' } })` and joins their room
- [ ] Completing an order emits a real-time event to the seller's socket AND persists a `Notification` row
- [ ] Connecting with an invalid JWT gets disconnected immediately
- [ ] `GET /notifications/count` returns correct unread count, decrements after `PATCH /notifications/read-all`

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 11 — Notifications Module.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/api/src/notifications/.
This replaces Pusher from the existing Laravel app. The event channel/event-name
mapping should mirror the existing Pusher events in resources/js/echo.js so
the frontend migration is straightforward.
All notification sends must be fire-and-forget (don't await the socket emit).
The socket.io server should share the same port as the HTTP server (NestJS default).
```

---

## Session 12 — Search + Discovery + Location

> **Status:** `Not Started`  
> **Depends on:** Session 04  
> **Repo location:** `apps/api/src/search/`, `apps/api/src/location/`, `apps/api/src/wishlist/`

### What this session builds

The multi-criteria search endpoint, wishlist CRUD, recently-viewed tracking,
and the location lookup endpoints (provinces/cities). Relatively small
session — these are mostly read-heavy endpoints.

### Tasks

- [ ] `SearchModule`
  - [ ] `GET /search` — query params: `q` (keyword), `category`, `transactionType`, `condition`, `minPrice`, `maxPrice`, `provinceId`, `cityId`, `sortBy` (price_asc / price_desc / newest / eco_score), `page`, `limit`
    - [ ] Builds a Prisma `findMany` with conditional `where` clauses
    - [ ] `q` → `title: { contains: q, mode: 'insensitive' }` (Postgres ILIKE)
    - [ ] Excludes soft-deleted and unapproved products
    - [ ] Returns products with seller info and review summary (avg rating + count)
  - [ ] `GET /search/suggestions` — typeahead: `q` param, returns top 8 matching product titles
- [ ] `WishlistModule`
  - [ ] `POST /wishlist/:productId` — toggle (add if not exists, remove if exists), returns `{ wishlisted: bool }`
  - [ ] `GET /wishlist` — current user's wishlist (paginated product list)
- [ ] `RecentlyViewed` (no dedicated module, add to `ProductsModule`)
  - [ ] `GET /products/:id` already exists — add side-effect: upsert `RecentlyViewed` row if authenticated
  - [ ] `GET /recently-viewed` — last 20 products viewed by current user
- [ ] `LocationModule`
  - [ ] `GET /location/provinces` — all provinces
  - [ ] `GET /location/cities/:provinceId` — cities in a province
  - [ ] Both endpoints are public (no JWT required)

### Done condition

- [ ] `GET /search?q=bike&transactionType=RENT&minPrice=50` returns only RENT-type products matching "bike" with price ≥ 50
- [ ] `GET /search/suggestions?q=bicy` returns up to 8 title suggestions
- [ ] `POST /wishlist/:productId` twice on the same product adds then removes it
- [ ] Viewing a product while authenticated creates a `RecentlyViewed` row; `GET /recently-viewed` returns it

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 12 — Search + Discovery + Location.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/api/src/search/, location/, wishlist/.
The search logic should mirror the query scopes in app/Models/Product.php
(conditional where clauses). Product.transactionTypes is a Postgres text[] column
— use Prisma's array contains filter: `transactionTypes: { has: 'RENT' }`.
Location data was seeded in Session 02.
```

---

## Session 13 — Admin Module

> **Status:** `Not Started`  
> **Depends on:** Sessions 09, 10, 11, 12  
> **Repo location:** `apps/api/src/admin/`

### What this session builds

Admin-gated wrappers for every domain: dashboard stats, user management,
product moderation, dispute resolution controls, and platform settings.
Thin controllers — all business logic already lives in the domain services.

### Tasks

- [ ] `AdminModule` — all routes require `@Roles('ADMIN')` or `@Roles('SUPER_ADMIN')`
  - [ ] Dashboard
    - [ ] `GET /admin/stats` — total users, products, active rentals, open disputes, pending payouts, platform wallet balance, total revenue (monthly)
  - [ ] Users
    - [ ] `GET /admin/users` — paginated, filterable by role/status
    - [ ] `GET /admin/users/:id` — detail with eco score, wallet balance
    - [ ] `PATCH /admin/users/:id/suspend` — body: `{ reason }`
    - [ ] `PATCH /admin/users/:id/ban` — body: `{ reason }`
    - [ ] `PATCH /admin/users/:id/activate` — restore suspended/banned account
    - [ ] `PATCH /admin/users/:id/approve-profile` — set profileStatus = APPROVED
  - [ ] Products
    - [ ] `GET /admin/products` — paginated, filter by approvalStatus
    - [ ] `PATCH /admin/products/:id/approve` (already exists, re-export here for clarity)
    - [ ] `PATCH /admin/products/:id/reject` — body: `{ reason }`
  - [ ] Disputes (wire existing DisputeService)
    - [ ] `GET /admin/disputes` — all open + in-review disputes
    - [ ] `PATCH /admin/disputes/:id/take` — admin claims the dispute (status → IN_REVIEW)
    - [ ] `PATCH /admin/disputes/:id/resolve` — (already in DisputesModule, accessible here too)
  - [ ] Payouts (wire existing WalletLedgerService)
    - [ ] `GET /admin/payouts` — pending requests (already in WalletModule, re-route here)
    - [ ] `POST /admin/payouts/:id/approve`
    - [ ] `POST /admin/payouts/:id/reject`
    - [ ] `POST /admin/payouts/:id/mark-paid`
  - [ ] Platform Settings
    - [ ] `GET /admin/settings` — all key-value settings
    - [ ] `PATCH /admin/settings/:key` — body: `{ value }` — update any setting (super admin only)
  - [ ] Super Admin only
    - [ ] `POST /admin/users/create-admin` — create a new admin account
    - [ ] `DELETE /admin/users/:id/revoke-admin` — demote admin to user

### Done condition

- [ ] `GET /admin/stats` returns a JSON object with all metric fields
- [ ] `PATCH /admin/users/:id/suspend` changes the user's accountStatus; that user's next login returns 403
- [ ] A USER-role JWT on any `/admin/` route returns 403
- [ ] `PATCH /admin/settings/commission_percent` with `{ value: 5.0 }` updates the DB row; next `CheckoutPricingService` call uses 5%

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 13 — Admin Module.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/api/src/admin/.
Reference app/Http/Controllers/Admin/AdminController.php.
This session is mostly thin controller wiring — the domain services are all built.
Key: SUPER_ADMIN can manage admins; ADMIN cannot. Use the RolesGuard from Session 03.
After this session the entire NestJS API is feature-complete.
Run a full smoke-test of all modules before marking done.
```

---

---

## Session 14 — React Web Scaffold

> **Status:** `Not Started`  
> **Depends on:** Session 13  
> **Repo location:** `apps/web/`

### What this session builds

The complete React 19 + Vite 8 web app skeleton: routing, auth state,
API client with token management, Socket.IO client, and all shared UI
primitives. No feature pages yet — just the shell that every subsequent
web session builds into.

### Tasks

- [ ] Install web dependencies
  - [ ] `react@19.2.7`, `react-dom@19.2.7`, `vite@8.0.16`
  - [ ] `react-router-dom` v7 (latest)
  - [ ] `@tanstack/react-query@5.101.0`, `@tanstack/react-query-devtools`
  - [ ] `zustand@5.0.14`
  - [ ] `axios`
  - [ ] `socket.io-client@4.8.3`
  - [ ] `tailwindcss` + `@tailwindcss/vite` plugin (v4 — integrates with Vite 8)
  - [ ] `lucide-react` (icons)
  - [ ] `react-hot-toast` (notifications)
  - [ ] `date-fns` (date formatting)
  - [ ] `@punap/types` and `@punap/utils` from monorepo
- [ ] Project structure
  ```
  apps/web/src/
  ├── api/          ← TanStack Query hooks per domain
  ├── components/   ← shared UI
  ├── hooks/        ← useAuth, useSocket, usePagination
  ├── layouts/      ← AppLayout, AuthLayout, AdminLayout
  ├── pages/        ← feature pages (empty stubs this session)
  ├── store/        ← Zustand slices
  └── lib/          ← axios instance, socket client
  ```
- [ ] `lib/api.ts` — Axios instance
  - [ ] `baseURL` from `VITE_API_URL` env
  - [ ] Request interceptor: attach `Authorization: Bearer <accessToken>` from Zustand store
  - [ ] Response interceptor: on 401, call `POST /auth/refresh`, retry original request; on second 401, logout
- [ ] `lib/socket.ts` — Socket.IO client
  - [ ] Lazy connect only when authenticated
  - [ ] `connect(token)`, `disconnect()`, `on(event, handler)`, `off(event, handler)`
- [ ] Zustand store
  - [ ] `authSlice`: `{ user, accessToken, setAuth, clearAuth }`; persisted to `localStorage`
  - [ ] `notificationSlice`: `{ unreadCount, increment, reset }`
- [ ] TanStack Query setup
  - [ ] `QueryClient` with defaults: `staleTime: 60_000`, `retry: 1`
  - [ ] `ReactQueryDevtools` in dev only
- [ ] Tailwind config — design tokens from mobile app spec:
  - [ ] `primary: #1A6B3C`, `primary-light: #E8F5EE`, `primary-dark: #124D2B`
  - [ ] `accent-buy`, `accent-rent: #2563EB`, `accent-swap: #7C3AED`
  - [ ] `eco-gold: #F59E0B`
  - [ ] Font: `Outfit` (headings), `Inter` (body) via Google Fonts
- [ ] Shared components
  - [ ] `Button` (variants: primary, secondary, ghost, danger; sizes: sm, md, lg)
  - [ ] `Input`, `Textarea`, `Select`, `Checkbox`
  - [ ] `Badge` (variants: buy, rent, swap, success, warning, danger)
  - [ ] `Card`, `CardHeader`, `CardBody`
  - [ ] `Modal` (portal-based)
  - [ ] `Spinner`, `Skeleton` (block + text variants)
  - [ ] `Avatar` (image with initials fallback)
  - [ ] `Pagination`
- [ ] Routing
  - [ ] `AppRouter` with `react-router-dom` v7 data router
  - [ ] `ProtectedRoute` — redirects to `/login` if not authenticated
  - [ ] `AdminRoute` — redirects to `/` if not ADMIN/SUPER_ADMIN role
  - [ ] All page routes defined but rendering empty `<div>Coming soon</div>` stubs
- [ ] `AppLayout` — header (logo, search bar, nav links, notification bell, avatar menu), footer
- [ ] `AuthLayout` — centered card layout for login/register pages
- [ ] Socket.IO integration in a `useSocket` hook — auto-connects on auth, increments `notificationSlice.unreadCount` on `notification.*` events

### Done condition

- [ ] `turbo dev --filter=@punap/web` starts without errors on port 5173
- [ ] Navigating to a protected route while unauthenticated redirects to `/login`
- [ ] Axios interceptor: manually expire the access token in Zustand, the next API call refreshes it transparently
- [ ] Notification bell badge increments when a real-time notification arrives from the API
- [ ] All shared components render without errors in a simple component demo page

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 14 — React Web Scaffold.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/web/.
The NestJS API from Sessions 01-13 is running at http://localhost:3001.
Design tokens are in docs/design_phoneApp.md (brand palette section).
The existing Laravel web app at resources/views/ can be referenced for
UX patterns but do not copy Blade syntax — this is a React SPA.
Use Vite 8's native Tailwind CSS v4 integration (no PostCSS config needed).
```

---

## Session 15 — Web — Auth Pages

> **Status:** `Not Started`  
> **Depends on:** Session 14  
> **Repo location:** `apps/web/src/pages/auth/`

### What this session builds

All six authentication pages wired to the real API, with form validation,
loading states, error handling, and redirect logic.

### Tasks

- [ ] `api/auth.ts` — TanStack Query mutations:
  - [ ] `useRegister`, `useLogin`, `useLogout`, `useForgotPassword`, `useResetPassword`, `useVerifyEmail`
- [ ] Pages
  - [ ] `LoginPage` — email + password form; on success set auth store + redirect to dashboard; link to register + forgot password
  - [ ] `RegisterPage` — name, email, phone, password, confirm password, terms checkbox; on success show "check your email" message
  - [ ] `VerifyEmailPage` — reads `token` from URL query param, calls API on mount, shows success/failure
  - [ ] `ForgotPasswordPage` — email form; always shows "if account exists..." message
  - [ ] `ResetPasswordPage` — reads `token` from URL, new password + confirm; on success redirect to login
  - [ ] `ResendVerificationPage` — email form to trigger resend
- [ ] Form validation via native HTML5 + custom hook (no library — keep it simple)
- [ ] Error messages mapped from API `400` response body
- [ ] Loading spinners on submit buttons

### Done condition

- [ ] Full register → verify email (click link in console log) → login flow works end-to-end
- [ ] Login with wrong password shows error message under the password field
- [ ] Login with suspended account shows the suspension message
- [ ] Forgot password + reset password flow works end-to-end

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 15 — Web Auth Pages.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/web/src/pages/auth/.
The shared components (Button, Input, Card) from Session 14 should be used throughout.
The API base URL is http://localhost:3001. Refresh token is HttpOnly cookie —
the Axios interceptor from Session 14 handles rotation transparently.
```

---

## Session 16 — Web — Discovery + Product Detail

> **Status:** `Not Started`  
> **Depends on:** Session 15  
> **Repo location:** `apps/web/src/pages/home/`, `apps/web/src/pages/product/`

### What this session builds

The landing / home feed, search + filter page, and product detail page
with the dynamic BUY/RENT/SWAP CTA logic.

### Tasks

- [ ] `api/products.ts` — `useProducts(filters)`, `useProduct(id)`, `useMyProducts()`, `useSearchSuggestions(q)`, `useToggleWishlist()`
- [ ] `HomePage`
  - [ ] Featured listings grid (latest approved products)
  - [ ] Transaction type filter tabs (All / Buy / Rent / Swap)
  - [ ] Category pills (horizontal scroll)
  - [ ] `ProductCard` component — image, title, price, condition badge, transaction type badges, eco score chip, wishlist toggle
  - [ ] Skeleton loader grid while loading
  - [ ] Pagination
- [ ] `SearchPage`
  - [ ] URL-driven filters (`/search?q=bike&transactionType=RENT&minPrice=100`)
  - [ ] Filter sidebar: category tree, transaction type checkboxes, condition checkboxes, price range inputs, province/city selects
  - [ ] Sort dropdown
  - [ ] Results grid using `ProductCard`
  - [ ] Typeahead search bar calling `/search/suggestions`
- [ ] `ProductDetailPage` (`/products/:id`)
  - [ ] Image gallery (main + thumbnails)
  - [ ] Title, price, condition, location, description
  - [ ] Seller info card (avatar, name, rating, eco level)
  - [ ] Transaction type CTA logic:
    - [ ] `BUY` in types → `<BuyCTA>` button (→ initiates order)
    - [ ] `RENT` in types → `<RentCTA>` with date range picker
    - [ ] `SWAP` in types → `<SwapCTA>` button (→ opens swap request form)
    - [ ] Multiple types → tabbed CTAs
  - [ ] Reviews section (avg rating, review list, paginated)
  - [ ] Wishlist toggle button
  - [ ] `Recently Viewed` — records view on mount if authenticated

### Done condition

- [ ] Home page loads with product grid; category pill click filters correctly
- [ ] Search with `?q=` shows matching results; filter changes update URL and re-fetch
- [ ] Product detail for a BUY+RENT product shows tabbed CTA with both options
- [ ] Wishlist toggle persists across page reload (TanStack Query refetch on mount)

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 16 — Web Discovery + Product Detail.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/web/src/pages/home/ and product/.
The product detail CTA logic is driven by Product.transactionTypes (string array).
Design reference: docs/design_phoneApp.md for color tokens.
The BUY CTA will initiate an order (wired in Session 17).
The RENT CTA date picker will initiate a rental booking (wired in Session 17).
The SWAP CTA will open a swap request form (wired in Session 18).
For now, CTAs can show a "Coming in next session" toast.
```

---

## Session 17 — Web — Orders + Rentals

> **Status:** `Not Started`  
> **Depends on:** Session 16  
> **Repo location:** `apps/web/src/pages/orders/`, `apps/web/src/pages/rentals/`

### What this session builds

Complete order and rental flows: create → payment initiation → Khalti/eSewa
redirect → return to app → status update. My orders list and my rentals list
(renting and lending views).

### Tasks

- [ ] `api/orders.ts` — `useCreateOrder`, `useOrders`, `useOrder(id)`, `useCancelOrder`, `useInitiateOrderPayment`
- [ ] `api/rentals.ts` — `useBookRental`, `useRentals`, `useRental(id)`, `useLendingRentals`, `useRequestReturn`, `useConfirmReturn`, `useInitiateRentalPayment`
- [ ] Wire `BuyCTA` on product detail → `useCreateOrder` → `useInitiateOrderPayment` → `window.location.href = paymentUrl` (Khalti redirect)
- [ ] Payment return pages
  - [ ] `/payment/success` — reads `pidx` or `transaction_uuid` from URL, shows success message, links to order/rental detail
  - [ ] `/payment/failure` — shows failure message, links back to product
- [ ] Wire `RentCTA` on product detail → date range picker → `useBookRental` → `useInitiateRentalPayment` → redirect
- [ ] `MyOrdersPage` (`/orders`) — tabs: Buying / Selling; paginated list
- [ ] `OrderDetailPage` (`/orders/:id`) — status timeline, product snapshot, payment receipt, cancel button (if PENDING), mark-complete button (if seller and PAID)
- [ ] `MyRentalsPage` (`/rentals`) — tabs: Renting / Lending; paginated list
- [ ] `RentalDetailPage` (`/rentals/:id`)
  - [ ] Status timeline
  - [ ] Rental dates, pricing breakdown, deposit amount
  - [ ] Return request form (file upload for evidence photos) — visible to renter when status = ACTIVE
  - [ ] Confirm return button — visible to owner when status = RETURN_REQUESTED
  - [ ] Deposit refund status (if applicable)

### Done condition

- [ ] Buy flow: create order → Khalti redirect → return to `/payment/success` → order status = PAID in DB
- [ ] Rental flow: pick dates → book → payment → return to success page → rental status = ACTIVE
- [ ] `MyOrdersPage` Selling tab shows the order for the product owner
- [ ] `POST /rentals/:id/request-return` with a photo upload updates `evidencePhotos` and status

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 17 — Web Orders + Rentals.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/web/src/pages/orders/ and rentals/.
Payment flow: frontend calls POST /payments/initiate/order/:orderId (or /rental/:id),
gets back { paymentUrl }, then does window.location.href = paymentUrl.
After Khalti processes, it redirects to VITE_APP_URL/payment/success?pidx=...
The API handles the backend resolution via GET /payments/callback/khalti.
So the success page just needs to show a success state and let the user navigate to their order.
```

---

## Session 18 — Web — Swap Flow

> **Status:** `Not Started`  
> **Depends on:** Session 17  
> **Repo location:** `apps/web/src/pages/swaps/`

### What this session builds

The swap request form, swap inbox with negotiation thread, counter-offer
UI, accept/reject/cancel actions, dual-confirmation screen, and the
cash-difference payment flow.

### Tasks

- [ ] `api/swaps.ts` — `useCreateSwapRequest`, `useSwaps`, `useSwap(id)`, `useSwapEvents`, `useCounterOffer`, `useAcceptSwap`, `useRejectSwap`, `useCancelSwap`, `useConfirmReceived`
- [ ] Wire `SwapCTA` on product detail → `SwapRequestModal`
  - [ ] Modal: shows user's own listings as offered products (dropdown), message field, optional cash top-up fields
- [ ] `SwapInboxPage` (`/swaps`) — tabs: Sent / Received; each swap card shows status badge, product thumbnails, last event summary
- [ ] `SwapDetailPage` (`/swaps/:id`)
  - [ ] Two product cards side-by-side (target + offered)
  - [ ] Negotiation thread (chronological `SwapNegotiationEvent` list)
  - [ ] Current offer state: amounts, money direction
  - [ ] Action bar based on status + user role:
    - [ ] Owner (PENDING/COUNTERED): Accept, Reject, Counter
    - [ ] Requester (COUNTERED): Accept, Reject, Counter, Cancel
    - [ ] Both (CONFIRMATION_PENDING): Confirm Received button
  - [ ] Counter-offer form: update amounts, money direction, message
  - [ ] If status = AWAITING_PAYMENT (cash diff): Show payment CTA → `useInitiateSwapPayment`
  - [ ] Confirmation screen when both parties have confirmed

### Done condition

- [ ] Full no-cash swap: propose → counter → accept → both confirm → status COMPLETED, both products SWAPPED
- [ ] Swap with cash top-up: propose → accept → payment redirect → both confirm → fund release
- [ ] Swap inbox shows the correct "Sent" and "Received" tabs with correct swap state
- [ ] Counter-offer updates the swap state and the other party sees it on refresh

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 18 — Web Swap Flow.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/web/src/pages/swaps/.
The negotiation history is read from GET /swaps/:id/events (SwapNegotiationEvent table).
There are no counter_amount/counter_message fields on SwapRequest in the new schema —
the current offer state is derived from the latest SwapNegotiationEvent.
Money direction enum: NONE | OWNER_ASKS_CASH | REQUESTER_OFFERS_CASH.
```

---

## Session 19 — Web — Profile + Wallet + Reviews

> **Status:** `Not Started`  
> **Depends on:** Session 17  
> **Repo location:** `apps/web/src/pages/profile/`, `apps/web/src/pages/wallet/`

### What this session builds

User profile page, edit profile form, wallet balance + ledger, payout
request flow, and the review pages.

### Tasks

- [ ] `api/profile.ts` — `useProfile(userId)`, `useUpdateProfile`, `useMyEcoScore`
- [ ] `api/wallet.ts` — `useWallet`, `useWalletLedger`, `usePayoutRequests`, `useCreatePayoutRequest`
- [ ] `api/reviews.ts` — `useProductReviews(productId)`, `useUserReviews(userId)`, `useCreateReview`
- [ ] `ProfilePage` (`/profile/:userId`) — public view
  - [ ] Avatar, name, member since, eco level + score ring
  - [ ] Active listings grid
  - [ ] Reviews received (with rating breakdown)
- [ ] `EditProfilePage` (`/settings/profile`)
  - [ ] Name, phone, address, province/city dropdowns
  - [ ] Avatar upload
- [ ] `MyListingsPage` (`/settings/listings`)
  - [ ] Table of user's products with status badges
  - [ ] Edit, soft-delete, re-activate actions
- [ ] `WalletPage` (`/wallet`)
  - [ ] Balance card (available + pending payout)
  - [ ] Payout request form (amount, note)
  - [ ] Payout request history with status timeline
  - [ ] Ledger table (direction, type, amount, balance after, date, reference)
- [ ] `WriteReviewModal` — triggered from `OrderDetailPage` / `RentalDetailPage` / `SwapDetailPage` after COMPLETED status; only shown once (disabled if review already exists)

### Done condition

- [ ] After a completed order, a "Write a Review" button appears on `OrderDetailPage`; submitting it creates the review and the button disappears
- [ ] `WalletPage` shows the seller's credited balance after a completed order payment
- [ ] Payout request with amount > balance shows an error
- [ ] Edit profile form updates `name` and `cityId`; changes visible immediately on `ProfilePage`

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 19 — Web Profile + Wallet + Reviews.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/web/src/pages/profile/ and wallet/.
The eco score ring animation is described in docs/design_phoneApp.md
(animated stroke draw on first load, 600ms). Implement with CSS SVG stroke-dasharray animation.
Province/city dropdowns should use the GET /location/provinces and
GET /location/cities/:provinceId endpoints from Session 12.
```

---

## Session 20 — Web — Disputes + Admin Panel

> **Status:** `Not Started`  
> **Depends on:** Session 19  
> **Repo location:** `apps/web/src/pages/disputes/`, `apps/web/src/pages/admin/`

### What this session builds

The dispute open/view flow for users, and the full admin panel: dashboard
stats, user management, product approval queue, dispute resolution, and
payout management.

### Tasks

- [ ] `api/disputes.ts` — `useMyDisputes`, `useDispute(id)`, `useOpenDispute`
- [ ] `api/admin.ts` — `useAdminStats`, `useAdminUsers`, `useAdminProducts`, `useAdminDisputes`, `useAdminPayouts`, `useAdminSettings`
- [ ] `OpenDisputeModal` — shown on OrderDetail/RentalDetail/SwapDetail for DISPUTED-eligible statuses
  - [ ] Fields: subject, description, evidence photo upload, transaction reference auto-filled
- [ ] `MyDisputesPage` (`/disputes`) — list with status badges
- [ ] `DisputeDetailPage` (`/disputes/:id`) — description, evidence photos, resolution (if resolved)
- [ ] Admin panel (all under `/admin/`, guarded by `AdminRoute`)
  - [ ] `AdminDashboardPage` — stat cards: users, products, revenue, disputes, pending payouts
  - [ ] `AdminUsersPage` — searchable table with suspend/ban/activate actions
  - [ ] `AdminProductsPage` — filter by `approvalStatus=PENDING`; approve/reject with one click
  - [ ] `AdminDisputesPage` — open disputes table; click → `AdminDisputeDetailPage`
  - [ ] `AdminDisputeDetailPage` — evidence photos, transaction detail, resolve form (favoredUserId select, resolution text, rental claim amount)
  - [ ] `AdminPayoutsPage` — pending payout requests table with approve/reject/mark-paid inline actions
  - [ ] `AdminSettingsPage` — editable key-value table for `PlatformSetting` rows

### Done condition

- [ ] Open dispute from an order detail page → appears in `MyDisputesPage` and `AdminDisputesPage`
- [ ] Admin resolves dispute → `DisputeDetailPage` shows resolution + favored party name
- [ ] Admin `AdminProductsPage` pending tab shows newly created products; approve updates their status
- [ ] Admin suspends a user → that user's next login returns 403
- [ ] `AdminSettingsPage` can update `commission_percent`; next order checkout uses new value

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 20 — Web Disputes + Admin Panel.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/web/src/pages/disputes/ and admin/.
The admin panel should be accessible at /admin and uses AdminLayout (sidebar nav).
After this session the web app is feature-complete.
Run through the full user journey (register → list product → buy → dispute → resolve)
before marking done.
```

---

---

## Session 21 — Expo Mobile Scaffold + Auth

> **Status:** `Not Started`  
> **Depends on:** Session 13 (API complete)  
> **Repo location:** `apps/mobile/`

### What this session builds

Expo 56 app with Expo Router v4 file-based navigation, NativeWind v4
styling with all design tokens, shared API and socket clients, Zustand
with SecureStore persistence, and all auth screens.

### Tasks

- [ ] Bootstrap Expo app
  - [ ] `npx create-expo-app@latest apps/mobile --template blank-typescript`
  - [ ] Upgrade to SDK 56.0.11 if template ships older
  - [ ] Install `expo-router@4.x`
  - [ ] Install `nativewind@4.2.5` + `tailwindcss`
  - [ ] Install `expo-secure-store`, `expo-web-browser`, `expo-linking`
  - [ ] Install `socket.io-client@4.8.3`
  - [ ] Install `@tanstack/react-query@5.101.0`, `zustand@5.0.14`, `axios`
  - [ ] Install `@punap/types`, `@punap/utils` from monorepo
- [ ] NativeWind v4 setup
  - [ ] `tailwind.config.js` with full design token palette (same values as web)
  - [ ] `global.css` with `@tailwind` directives
  - [ ] Babel plugin config
  - [ ] Fonts: `expo-font` + `useFonts` for Outfit + Inter
- [ ] `app/_layout.tsx` — root layout with `QueryClientProvider`, Zustand hydration
- [ ] Navigation structure
  ```
  app/
  ├── _layout.tsx             ← root (font load, providers)
  ├── (auth)/
  │   ├── _layout.tsx         ← AuthLayout (no tabs)
  │   ├── login.tsx
  │   ├── register.tsx
  │   ├── verify-email.tsx
  │   └── forgot-password.tsx
  ├── (tabs)/
  │   ├── _layout.tsx         ← Bottom tab bar (5 tabs)
  │   ├── index.tsx           ← Home
  │   ├── search.tsx
  │   ├── activity.tsx
  │   ├── notifications.tsx
  │   └── profile.tsx
  └── product/[id].tsx        ← deep link target
  ```
- [ ] Zustand store with `expo-secure-store` persistence
  - [ ] `authSlice` — `{ user, accessToken, setAuth, clearAuth }`, persisted to SecureStore
  - [ ] `notificationSlice` — `{ unreadCount, increment, reset }`
- [ ] API client (`lib/api.ts`) — same Axios interceptor pattern as web, reads token from Zustand
- [ ] Socket client (`lib/socket.ts`) — same interface as web
- [ ] Auth screens
  - [ ] `LoginScreen` — email + password; on success navigate to `/(tabs)/`
  - [ ] `RegisterScreen` — name, email, phone, password, confirm, terms
  - [ ] `VerifyEmailScreen` — enter 6-char token (or deep link `punap://verify?token=...`)
  - [ ] `ForgotPasswordScreen`
- [ ] Deep linking config in `app.json`: scheme `punap`, payment callback path `punap://payment/callback`
- [ ] Bottom tab bar with icons (Lucide or `@expo/vector-icons`), active state animation

### Done condition

- [ ] `npx expo start` runs without errors
- [ ] Login flow works end-to-end on iOS Simulator or Android Emulator
- [ ] Tab bar renders with 5 tabs; correct screen mounts per tab
- [ ] SecureStore persistence: kill and reopen app → still logged in
- [ ] Deep link `punap://verify?token=abc` opens verify screen with token pre-filled

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 21 — Expo Mobile Scaffold + Auth.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/mobile/.
Design tokens are in docs/design_phoneApp.md — use those exact hex values in the
Tailwind config.
Typography: Outfit (headings) and Inter (body) via expo-google-fonts or expo-font.
Micro-animations from the design doc (tab bar scale, card press) should be implemented
with react-native-reanimated (included in Expo 56).
The API runs at the machine's local IP (not localhost) for device testing — use
EXPO_PUBLIC_API_URL env var.
```

---

## Session 22 — Mobile — Discovery + Product Detail

> **Status:** `Not Started`  
> **Depends on:** Session 21  
> **Repo location:** `apps/mobile/app/(tabs)/`, `apps/mobile/app/product/`

### What this session builds

Home feed, search screen with filters, and the product detail screen
with the CTA logic (Buy/Rent/Swap tabs).

### Tasks

- [ ] `api/products.ts` (React Query hooks — same shape as web)
- [ ] `HomeScreen` (`(tabs)/index.tsx`)
  - [ ] Transaction type toggle (Buy / Rent / Swap) with animated underline slide
  - [ ] Category horizontal scroll (pill buttons)
  - [ ] `FlatList` of `ProductCard` components
  - [ ] Skeleton loaders (not spinners per design spec)
  - [ ] Pull-to-refresh
- [ ] `ProductCard` component
  - [ ] Image (with `expo-image` for caching), title, price, condition badge, type badges
  - [ ] Eco score chip with leaf icon
  - [ ] Wishlist heart button (optimistic toggle)
- [ ] `SearchScreen` (`(tabs)/search.tsx`)
  - [ ] Search bar with live typeahead suggestions
  - [ ] Filter bottom sheet (NativeWind styled): category, type, condition, price range, location
  - [ ] Results `FlatList` with `ProductCard`
  - [ ] Empty state illustration
- [ ] `ProductDetailScreen` (`product/[id].tsx`)
  - [ ] Image gallery (horizontal `FlatList` with dot indicator)
  - [ ] Sticky bottom action bar
  - [ ] CTA tabs based on `transactionTypes`:
    - [ ] BUY tab → price + "Buy Now" button
    - [ ] RENT tab → price/period + date range picker (bottom sheet) + "Book" button
    - [ ] SWAP tab → "Propose Swap" button → opens swap modal
  - [ ] Seller info row (tap → public profile)
  - [ ] Reviews section (horizontal scroll of rating cards)
  - [ ] Wishlist toggle in header

### Done condition

- [ ] Home screen loads product grid with skeleton → content transition
- [ ] Category filter updates the product list
- [ ] Search typeahead shows suggestions as user types
- [ ] Product detail for a multi-type product shows tabs; correct action available per type
- [ ] Wishlist toggle heart animates and persists

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 22 — Mobile Discovery + Product Detail.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/mobile/app/(tabs)/ and product/.
Use expo-image for product images (better caching than <Image>).
Bottom sheet for filters and date picker: use @gorhom/bottom-sheet (install it).
Date range picker: build a simple two-date calendar using react-native-calendars.
Animations (tab toggle underline, card press scale) use react-native-reanimated
which is already in Expo 56.
```

---

## Session 23 — Mobile — Orders + Rentals

> **Status:** `Not Started`  
> **Depends on:** Session 22  
> **Repo location:** `apps/mobile/app/(tabs)/activity.tsx` and detail screens

### What this session builds

Order and rental transaction flows on mobile. Payment goes through
`expo-web-browser` (in-app browser) for Khalti/eSewa. The activity tab
shows all transaction history. Return request with photo upload.

### Tasks

- [ ] `api/orders.ts`, `api/rentals.ts` (same hooks as web)
- [ ] Wire `BuyCTA` → `useCreateOrder` → `useInitiateOrderPayment` → `WebBrowser.openAuthSessionAsync(paymentUrl, 'punap://payment/callback')`
- [ ] Wire `RentCTA` date picker confirmation → `useBookRental` → `useInitiateRentalPayment` → `WebBrowser.openAuthSessionAsync`
- [ ] Deep link handler for `punap://payment/callback` → refetch relevant order/rental
- [ ] `ActivityScreen` (`(tabs)/activity.tsx`)
  - [ ] Segmented control: Orders / Rentals / (Swaps added in Session 24)
  - [ ] Orders tab: `FlatList` of order cards with status badges
  - [ ] Rentals tab: sub-tabs Renting / Lending
- [ ] `OrderDetailScreen` (`/order/[id]`)
  - [ ] Status stepper (visual timeline)
  - [ ] Product snapshot, pricing, cancel button
- [ ] `RentalDetailScreen` (`/rental/[id]`)
  - [ ] Status stepper
  - [ ] Return request button → opens camera or gallery (via `expo-image-picker`) → uploads evidence
  - [ ] Confirm return button for owner
  - [ ] Deposit status section

### Done condition

- [ ] Buy flow: tap "Buy Now" → in-app browser opens Khalti → complete payment → browser closes → order status updated on return
- [ ] `ActivityScreen` Orders tab lists the completed order
- [ ] Rental return: photo picker uploads evidence, status changes to RETURN_REQUESTED
- [ ] Owner taps "Confirm Return" → status = COMPLETED

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 23 — Mobile Orders + Rentals.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/mobile/app/order/ and rental/.
Payment: use expo-web-browser's openAuthSessionAsync with the deep link scheme
punap://payment/callback as the return URL. After the browser closes, use
expo-linking's useURL hook to detect the deep link and trigger a query refetch.
For photo upload in return requests: use expo-image-picker to select from gallery or
camera, then POST multipart/form-data to POST /rentals/:id/request-return.
```

---

## Session 24 — Mobile — Swaps + Notifications

> **Status:** `Not Started`  
> **Depends on:** Session 23  
> **Repo location:** `apps/mobile/app/` swap screens + `(tabs)/notifications.tsx`

### What this session builds

The full swap flow on mobile, notifications screen, and real-time
notification badge in the tab bar.

### Tasks

- [ ] `api/swaps.ts` (same hooks as web)
- [ ] `ProposeSwapModal` — bottom sheet triggered from product detail SWAP tab
  - [ ] Picker for user's own products (scrollable list)
  - [ ] Message field, optional cash amounts
- [ ] Add Swaps segment to `ActivityScreen`
  - [ ] Swap cards: two product thumbnails + arrow, status badge, last event summary
- [ ] `SwapDetailScreen` (`/swap/[id]`)
  - [ ] Two product cards side-by-side (thumbnail + title + owner)
  - [ ] Negotiation timeline (list of `SwapNegotiationEvent`)
  - [ ] Current offer amounts if cash involved
  - [ ] Action buttons based on role + status
  - [ ] `CounterOfferSheet` bottom sheet
  - [ ] Dual-confirmation button
- [ ] `NotificationsScreen` (`(tabs)/notifications.tsx`)
  - [ ] `FlatList` of notification rows (icon by type, title, body, timestamp)
  - [ ] Tap → navigate to the relevant screen
  - [ ] Swipe-to-dismiss or "Mark all read" header button
- [ ] Real-time via Socket.IO
  - [ ] Connect on auth (from `_layout.tsx`)
  - [ ] On any `notification.*` event → increment `notificationSlice.unreadCount`
  - [ ] Tab bar badge on Notifications tab shows count
  - [ ] Entering `NotificationsScreen` calls `markAllRead` and resets count

### Done condition

- [ ] Propose swap from product detail → appears in `ActivityScreen` Swaps tab for both users
- [ ] Counter-offer from bottom sheet → negotiation event appears in `SwapDetailScreen` timeline
- [ ] A real-time notification from the API (e.g., completing an order) increments the tab badge
- [ ] Entering notifications screen marks all read and badge clears

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 24 — Mobile Swaps + Notifications.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/mobile/app/swap/ and notifications/.
Socket.IO connection: initialize in the root _layout.tsx when accessToken is available.
Use the same socket.ts lib as the web (same interface, different connection point).
For the notifications tab badge: Expo Router's Tabs component supports a
badge prop on Tab.Screen — pass the unreadCount from Zustand.
```

---

## Session 25 — Mobile — Profile + Wallet + Polish

> **Status:** `Not Started`  
> **Depends on:** Session 24  
> **Repo location:** `apps/mobile/app/` profile + wallet screens

### What this session builds

Profile screen, wallet + ledger, payout request, eco score display,
and final polish: app icon, splash screen, loading states, empty states,
and error boundaries.

### Tasks

- [ ] `api/profile.ts`, `api/wallet.ts`, `api/reviews.ts`
- [ ] `ProfileScreen` (`(tabs)/profile.tsx`) — current user's own profile
  - [ ] Avatar (tap to replace via `expo-image-picker`), name, eco level badge
  - [ ] Animated eco score ring (SVG stroke-dasharray, 600ms draw on mount)
  - [ ] Quick stats: listings, completed transactions, reviews
  - [ ] My Listings section (horizontal scroll)
  - [ ] Settings links: Edit Profile, Wallet, Notifications, Logout
- [ ] `EditProfileScreen` — name, phone, address, province/city
- [ ] `PublicProfileScreen` (`/profile/[userId]`) — another user's profile
- [ ] `WalletScreen`
  - [ ] Balance card with available + pending
  - [ ] "Request Payout" button → `PayoutRequestSheet`
  - [ ] Ledger `FlatList` (direction icon, type label, amount, date)
  - [ ] Payout history section
- [ ] Polish tasks
  - [ ] App icon: replace default with PUNAP brand icon (green leaf or circular arrow)
  - [ ] Splash screen: brand primary colour background + logo
  - [ ] Every `FlatList` has an `EmptyState` component (illustration + message)
  - [ ] Every screen that fetches has an error boundary with "Retry" button
  - [ ] Haptic feedback (`expo-haptics`) on: wishlist toggle, swap accept/reject, review submit
  - [ ] `expo-status-bar` with `style="light"` on dark header screens
  - [ ] Ensure all touchable targets are ≥ 48dp (accessibility)
  - [ ] Test on both iOS Simulator and Android Emulator before marking done
  - [ ] `eas build --platform all --profile preview` builds without errors

### Done condition

- [ ] Profile screen eco score ring animates on mount
- [ ] Wallet shows correct balance after completing a test order (as seller)
- [ ] Payout request form validates balance, creates request, appears in history
- [ ] App builds successfully with `eas build --profile preview`
- [ ] No TypeScript errors (`turbo run typecheck`)

### Start prompt for Claude Code

```
@docs/migration_plan.md

Execute Session 25 — Mobile Profile + Wallet + Polish.
Working directory: ~/Desktop/PPPPP/FYP/ — new code in apps/mobile/app/profile/ and wallet/.
This is the final session. After completing it, run a full end-to-end smoke test:
register → list a product → buy it (as another user) → confirm → check wallet → request payout.
The eco score ring uses SVG with react-native-svg (install if not present).
For the EAS build: assume an eas.json with a "preview" profile using internal distribution.
```

---

## Global Notes for All Sessions

### Environment variables required (`.env`)

```
# Database
DATABASE_URL=postgresql://punap:password@localhost:5432/punap

# JWT
JWT_ACCESS_SECRET=<generate: openssl rand -hex 64>
JWT_REFRESH_SECRET=<generate: openssl rand -hex 64>
JWT_ACCESS_EXPIRES_IN=15m
JWT_REFRESH_EXPIRES_IN=7d

# Mailer
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USER=
MAIL_PASS=
MAIL_FROM=noreply@punap.com

# Storage
STORAGE_DRIVER=local   # or s3
S3_BUCKET=
S3_REGION=
S3_ACCESS_KEY=
S3_SECRET_KEY=
S3_ENDPOINT=           # for Cloudflare R2

# Khalti
KHALTI_SECRET_KEY=
KHALTI_INITIATE_URL=https://a.khalti.com/api/v2/epayment/initiate/
KHALTI_LOOKUP_URL=https://a.khalti.com/api/v2/epayment/lookup/
KHALTI_REFUND_URL=

# eSewa
ESEWA_MERCHANT_CODE=
ESEWA_SECRET_KEY=
ESEWA_PAYMENT_URL=https://rc-epay.esewa.com.np/api/epay/main/v2/form

# App
API_PORT=3001
FRONTEND_URL=http://localhost:5173
ALLOWED_ORIGINS=http://localhost:5173,http://localhost:8081

# Admin seed
SUPER_ADMIN_EMAIL=admin@punap.com
SUPER_ADMIN_PASSWORD=<strong password>
```

### Session completion checklist (applies to every session)

- [ ] TypeScript: `turbo run typecheck --filter=@punap/api` (or web/mobile) exits 0
- [ ] No `any` types introduced without a `// eslint-disable` comment explaining why
- [ ] New Prisma models have been migrated (`npx prisma migrate dev`)
- [ ] New endpoints are documented with a brief comment in the controller
- [ ] Git commit made with a descriptive message before closing the session

### How to update this file

When a session is done, change its `Status` in the Session Index table from
`Not Started` → `In Progress` → `Done` and check off completed tasks.
