# Implementation Plan — DocuFlow UI/UX Revamp

**Project:** DocuFlow (Web-based Document Management System)
**Goal:** UI/UX revision as directed by the supervising lecturer
**Assumed stack:** Laravel + Blade + Tailwind + Alpine.js

---

## Scope Summary

| # | Feature | Complexity |
|---|-------|---------------|
| 1 | Separate color palette per theme (light/dark) for primary, accent, success, warning, error | Low |
| 2 | Micro-interactions & animation on the left navbar | Low–Medium |
| 3 | Profile + logout moved to bottom-left corner (dropdown) | Medium |
| 4 | Top-right profile icon replaced with search icon | Low |
| 5 | Document search scoped globally, but respecting visibility (division documents must not leak to users outside that division) | Medium–High |
| 6 | Real-time notifications (bell icon) via Laravel Reverb, tied to the relevant user | High |

---

## Phase 1 — Theming (Dark/Light Colors)

**Current issue:** colors are likely just an automatic brightness invert, not a separate palette.

**Plan:**
- Define CSS variables per color token, with different values for `:root` (light) and `.dark`:
  - `--color-primary`
  - `--color-accent`
  - `--color-success`
  - `--color-warning`
  - `--color-error`
- Light mode: more saturated/darker colors for contrast on a light background.
- Dark mode: slightly lighter/desaturated colors so they don't glare against the dark background.
- Audit all Blade components that hardcode Tailwind colors (e.g. `bg-blue-600`, `text-red-500`) → replace with color tokens.

**Affected files:**
- `resources/css/app.css` or `tailwind.config.js`
- Components: button, badge, alert, status indicator

**Work batches:**
- Batch 1: Set up light + dark color tokens
- Batch 2: Apply tokens to existing components

---

## Phase 2 — Left Navbar: Micro-interactions & Animation

**Plan:**
- Smooth hover transition on menu items
- Active-state indicator (line/pill) that animates/slides when switching menus
- Expand/collapse animation if the sidebar is collapsible
- Implement using CSS transitions + Alpine.js `x-transition` (lightweight, fits the Blade stack)

**Work batches:**
- Batch 1: Left navbar animation (hover + active state)
- Batch 2: Testing & polish

---

## Phase 3 — Profile & Logout to Bottom-Left Corner

**Plan:**
- Build a dropdown/popover component at the bottom of the left sidebar containing the user's avatar/name
- Clicking it → shows 2 options: "Profile" and "Logout"
- Use Alpine.js `x-data` to toggle open/close + close-on-click-outside
- Remove the old profile component from the top-right navbar

**Work batches:**
- Batch 1: Profile+logout dropdown component in the bottom-left
- Batch 2: Remove old profile from the top navbar

---

## Phase 4 — Global Search (with Visibility Control)

**Decision:** search is scoped globally (all pages), but results must be filtered by visibility — division documents (e.g. HRD) must never appear for users outside that division.

**Plan:**
- Search icon replaces the old profile position in the top-right navbar
- The search query **must reuse** the same visibility logic already used on the Division/General Document listing pages — it must not be rewritten separately, to avoid a data-leak gap
- Visibility rules:
  - General/public documents → visible to anyone
  - Division documents → only visible to members of that division or admins

**Work batches:**
- Batch 1: Search icon in the navbar + search box/modal UI
- Batch 2: Search query with visibility filter + integration across all pages

---

## Phase 5 — Real-Time Notifications (Bell Icon)

**Decision:** true real-time delivery via Laravel Reverb (not polling).

**Plan:**
1. Install & set up Laravel Reverb (Laravel's built-in self-hosted WebSocket server)
2. Create a private channel per user: `private-notifications.{userId}` — so user A's notifications can't be accessed by user B
3. Trigger notifications from events already present in the project:
   - Signature (TTD) approval requests
   - New document added to a division
   - Other relevant events
4. `notifications` model & migration (can leverage Laravel's built-in `Notifiable` trait): columns `user_id`, `type`, `data` (json), `read_at`
5. Frontend: Laravel Echo (JS client) listens to the channel → live-updates the badge counter & notification list without a refresh
6. Run a separate Reverb server (`php artisan reverb:start`) in both development and deployment

**Work batches:**
- Batch 1: Install & configure Reverb + private channel + event broadcasting
- Batch 2: `notifications` model & migration
- Batch 3: Bell icon UI + list panel + Echo listener

---

## Overall Execution Order

1. Set up color tokens (light+dark)
2. Apply tokens to existing components
3. Left navbar animation
4. Navbar testing/polish
5. Profile+logout dropdown component in the bottom-left
6. Remove old profile from the top navbar
7. Search icon in the top navbar
8. Search box/modal UI
9. Search query with visibility filter
10. Integrate search across all pages
11. Install & configure Reverb
12. Private channel + event broadcasting
13. `notifications` model & migration
14. Bell UI + list + Echo listener

---

## Implementation Notes

- Execution follows the stated preference: 2 steps per batch, code delivered as full files (not diffs), moving to the next batch only after confirmation.
- Critical security point: the document visibility logic in Phase 4 **must stay consistent** with the logic used on the document listing pages — avoid duplicating logic, which can lead to inconsistencies.
