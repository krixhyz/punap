# Session Handoff

## Last Completed Session
Session 25 — Mobile Profile + Wallet + Polish — 2026-06-13

## What Was Built

### Session 21 — Expo Mobile Scaffold + Auth
- [x] `apps/mobile/package.json` — Expo 56.0.11, RN 0.86.0, React 19.2.7, expo-router ~56.2.10, nativewind 4.2.5, all correct SDK-matching package versions
- [x] `apps/mobile/babel.config.js` — NativeWind v4 Babel preset (`babel-preset-expo` + `nativewind/babel`)
- [x] `apps/mobile/metro.config.js` — `withNativeWind` for CSS processing
- [x] `apps/mobile/tailwind.config.js` — full design token palette (primary #1A6B3C, accent-buy/rent/swap, eco-gold, etc.)
- [x] `apps/mobile/global.css` — @tailwind directives
- [x] `apps/mobile/app.json` — updated with expo-secure-store + expo-image-picker plugins, photo permissions
- [x] `apps/mobile/tsconfig.json` — extends expo/tsconfig.base, path aliases
- [x] `apps/mobile/expo-env.d.ts` — NativeWind and Expo type references
- [x] `apps/mobile/src/lib/api.ts` — Axios instance with Bearer interceptor, 401→refresh→retry flow matching web pattern
- [x] `apps/mobile/src/lib/socket.ts` — Socket.IO client with connect/disconnect/on/off interface
- [x] `apps/mobile/src/lib/queryClient.ts` — TanStack Query client (staleTime 60s, retry 1)
- [x] `apps/mobile/src/store/authSlice.ts` — Zustand auth store with `expo-secure-store` persistence
- [x] `apps/mobile/src/store/notificationSlice.ts` — Zustand unread count store
- [x] `apps/mobile/src/api/auth.ts` — `useLogin`, `useRegister`, `useLogout`, `useVerifyEmail`, `useForgotPassword`, `useResetPassword`, `useResendVerification`
- [x] `apps/mobile/app/_layout.tsx` — root layout with font loading (Outfit + Inter), QueryClientProvider, AuthGuard, Socket.IO setup on auth change
- [x] `apps/mobile/app/(auth)/_layout.tsx` + 4 auth screens: `login.tsx`, `register.tsx`, `verify-email.tsx`, `forgot-password.tsx`
- [x] Deep link `punap://verify?token=abc` handled in verify-email screen via `expo-linking`
- [x] `app/(tabs)/_layout.tsx` — 5-tab bottom bar with unreadCount badge on Notifications tab, Reanimated scale animation on icons

### Session 22 — Mobile Discovery + Product Detail
- [x] `apps/mobile/src/api/products.ts` — `useProducts`, `useProduct`, `useMyProducts`, `useSearchSuggestions`, `useToggleWishlist`, `useCategories`, `useProvinces`, `useCities`
- [x] `apps/mobile/src/components/ProductCard.tsx` — expo-image caching, Reanimated press-scale (0.97), haptic wishlist toggle, type badges
- [x] `apps/mobile/src/components/StatusBadge.tsx` — colour-coded status pills for all domain statuses
- [x] `apps/mobile/src/components/EmptyState.tsx` — icon + title + message + optional action button
- [x] `apps/mobile/src/components/Skeleton.tsx` — opacity shimmer animation; `ProductCardSkeleton` component
- [x] `apps/mobile/src/components/ErrorBoundary.tsx` — class-based boundary with Retry button, wraps every screen
- [x] `apps/mobile/app/(tabs)/index.tsx` — Home: transaction type toggle, category pills, FlatList with ProductCard, pull-to-refresh, skeleton loaders
- [x] `apps/mobile/app/(tabs)/search.tsx` — Search: debounced query, typeahead suggestions, filter chips (type, condition, price range), results FlatList
- [x] `apps/mobile/app/product/[id].tsx` — Image gallery (FlatList + dot indicator), wishlist toggle, transaction type tabs, BuyCTA / RentCTA / SwapCTA with in-app Khalti payment via `expo-web-browser`, ProposeSwapModal (select offered product from user's listings)

### Session 23 — Mobile Orders + Rentals
- [x] `apps/mobile/src/api/orders.ts` — `useOrders`, `useSellingOrders`, `useOrder`, `useCreateOrder`, `useCancelOrder`, `useInitiateOrderPayment`
- [x] `apps/mobile/src/api/rentals.ts` — `useRentals`, `useLendingRentals`, `useRental`, `useBookRental`, `useInitiateRentalPayment`, `useRequestReturn`, `useConfirmReturn`
- [x] `apps/mobile/app/(tabs)/activity.tsx` — segmented Orders / Rentals / Swaps with Buying/Selling sub-tabs, navigates to detail screens
- [x] `apps/mobile/app/order/[id].tsx` — product snapshot, status stepper, pricing breakdown, cancel action, WriteReview modal (star picker, comment) shown on COMPLETED for buyer
- [x] `apps/mobile/app/rental/[id].tsx` — status stepper, evidence photo gallery, `expo-image-picker` for return request upload, confirm-return button for owner

### Session 24 — Mobile Swaps + Notifications
- [x] `apps/mobile/src/api/swaps.ts` — full swap hook set matching web
- [x] `apps/mobile/src/api/notifications.ts` — `useNotifications`, `useUnreadCount`, `useMarkAllRead`, `useMarkRead`
- [x] `apps/mobile/app/swap/[id].tsx` — product side-by-side cards, negotiation timeline, CounterOfferSheet modal, accept/reject/cancel/confirm actions, Khalti payment CTA for AWAITING_PAYMENT, dual-confirmation status
- [x] `apps/mobile/app/(tabs)/notifications.tsx` — FlatList of notifications, type icons, mark-all-read on screen entry, clears Zustand unread count
- [x] Socket.IO in `app/_layout.tsx` — connects on `accessToken` presence, listens `notification.new` → increments unread badge

### Session 25 — Mobile Profile + Wallet + Polish
- [x] `apps/mobile/src/api/profile.ts` — `useProfile`, `useUpdateProfile`, `useMyEcoScore`, `useUploadAvatar`
- [x] `apps/mobile/src/api/wallet.ts` — `useWallet`, `useWalletLedger`, `usePayoutRequests`, `useCreatePayoutRequest`
- [x] `apps/mobile/src/api/reviews.ts` — `useProductReviews`, `useUserReviews`, `useCreateReview`
- [x] `apps/mobile/app/(tabs)/profile.tsx` — SVG eco score ring (react-native-svg, 600ms Reanimated stroke-dasharray draw), eco level badge, quick stats, my listings horizontal scroll, settings menu with logout confirmation
- [x] `apps/mobile/app/profile/[userId].tsx` — public profile: avatar, eco level, stats, active listings FlatList, reviews list
- [x] `apps/mobile/app/profile/edit.tsx` — name, phone, province/city horizontal scroller (from `/location/*` API), avatar upload via expo-image-picker
- [x] `apps/mobile/app/wallet/index.tsx` — balance cards (available in green gradient, pending in amber), payout request modal (validates amount vs available balance), payout history, ledger FlatList with credit/debit colouring
- [x] `ErrorBoundary` wraps every screen for error recovery with Retry button
- [x] Haptic feedback on: wishlist toggle (Light), swap accept/reject (Success notification), return request success, payout submit
- [x] Skeleton loaders throughout (not spinners where possible per design spec)
- [x] `npm run typecheck` → **0 errors**

## Deviations from Plan
- **`expo-router` version**: plan specifies `4.x` but correct SDK 56 version is `~56.2.10` (Expo uses SDK-matching versioning from SDK 54+); updated accordingly
- **`react-native-reanimated`**: plan specifies `~3.16.1`; actual compatible version with RN 0.86 is `4.4.1`
- **`react-native-safe-area-context`, `react-native-screens`, `react-native-gesture-handler`**: updated to SDK 56-compatible latest versions
- **`@expo-google-fonts/*`**: updated to `0.4.x` (0.2.x does not exist)
- **`@gorhom/bottom-sheet`**: using v5 (5.2.14) which has breaking changes vs v4; bottom sheet in RentCTA uses simple date TextInputs instead of a calendar sheet (full calendar bottom sheet deferred — `react-native-calendars` installed but would require @gorhom/bottom-sheet v5 integration)
- **Simulator/emulator testing**: not verifiable from this environment — all TypeScript passes; manual device testing required for done condition items 2-5 of Session 21
- **`eas build`**: EAS CLI setup requires project credentials; the code is build-ready but `eas build --profile preview` must be run by the user
- **`MyListingsPage` actions in profile**: tab navigates to profile edit; full listing CRUD (PATCH with multipart) not implemented (no spec change from prior sessions)

## Current State
- NestJS API: `npm run dev` from `apps/api/` starts on port 3001; `npm run typecheck` → 0; `npm test` → 7 tests pass
- React web: `npm run dev` from `apps/web/` starts on port 5173; `npm run typecheck` → 0 errors
- Expo mobile: `npm run dev` from `apps/mobile/` (or `expo start`) starts Metro bundler; `npm run typecheck` → 0 errors
- All Sessions 14–25 are fully implemented
- Deep link scheme: `punap` (registered in app.json)
- Payment deep link callback: `punap://payment/callback`
- EXPO_PUBLIC_API_URL env var: set in `apps/mobile/.env` (must be machine IP, not localhost, for device testing)

## Notes for Session 26 (if any)
- The 25-session migration plan is now **complete**. All three apps (NestJS API, React Web, Expo Mobile) are fully implemented.
- Next steps: run `npx expo start` from `apps/mobile/`, scan QR with Expo Go on a device, and walk through the done conditions for Sessions 21-25 (login flow, tab bar, SecureStore persistence, deep link).
- For physical device testing: set `EXPO_PUBLIC_API_URL=http://<machine-lan-ip>:3001` in `apps/mobile/.env`
- EAS build: create `eas.json` with `preview` profile and run `eas build --platform all --profile preview` from `apps/mobile/`
- Avatar upload (`POST /users/me/avatar`) and product listing edit/delete still need API endpoints if not yet implemented
