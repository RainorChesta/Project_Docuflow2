# DESIGN.md: Enterprise Design System for Document Consolidation & Version Control

## 1. Design Philosophy

This design system is built for **productivity, clarity, and trust**. 

We are designing an enterprise document management system. The users are professionals who need to create, review, and approve documents efficiently. The interface must be **invisible**. Users should notice how easy it is to complete their tasks, not the UI itself.

**Core Principles:**
*   **Calm & Minimal:** No visual noise. No gradients, no glassmorphism, no decorative illustrations.
*   **High Data Density:** Enterprise users need to scan information quickly. Optimize for reading and scanning, not for empty whitespace.
*   **Deterministic:** Every action has a clear, immediate, and predictable outcome.
*   **Strict Hierarchy:** Primary actions are obvious. Secondary actions are tucked away. Metadata is subtle.

---

## 2. Design Tokens

### Color Tokens
Colors are semantic. They communicate state, not decoration. We use a neutral-heavy palette with a single primary accent.

**Light Mode (Default)**
*   **Background:** `gray-50` (App canvas), `white` (Surfaces/Cards)
*   **Text:** `gray-900` (Primary), `gray-600` (Secondary), `gray-400` (Muted/Placeholder)
*   **Borders:** `gray-200` (Default), `gray-300` (Hover/Focus)
*   **Primary Action:** `indigo-600` (Hover: `indigo-700`, Text on Primary: `white`)
*   **Semantic States:**
    *   **Pending/Warning:** `amber-50` (Bg), `amber-800` (Text), `amber-200` (Border)
    *   **Active/Success:** `emerald-50` (Bg), `emerald-800` (Text), `emerald-200` (Border)
    *   **Rejected/Danger:** `red-50` (Bg), `red-800` (Text), `red-200` (Border)
    *   **Information:** `blue-50` (Bg), `blue-800` (Text), `blue-200` (Border)

**Dark Mode (Optional/Future)**
*   Invert neutrals. Keep semantic colors but adjust luminance for contrast. *Primary focus is Light Mode.*

### Typography
Font Family: **Inter** (or system-ui fallback). Clean, highly legible, optimized for UI and dense text.

*   **H1 (Page Title):** 24px, Weight 600, Tracking -0.02em, Color `gray-900`
*   **H2 (Section Title):** 20px, Weight 600, Tracking -0.01em, Color `gray-900`
*   **H3 (Card/Modal Title):** 16px, Weight 600, Color `gray-900`
*   **Body (Default):** 14px, Weight 400, Line-height 1.5, Color `gray-700`
*   **Small (Table data, metadata):** 13px, Weight 400, Color `gray-600`
*   **Caption (Labels, hints):** 12px, Weight 500, Uppercase or Sentence case, Color `gray-500`
*   **Monospace (Document IDs, Code):** 13px, `JetBrains Mono` or `ui-monospace`, Color `gray-700`

### Spacing
Strict **4pt / 8pt grid**. Never use arbitrary spacing (e.g., `13px`, `7px`).
*   **xs:** 4px
*   **sm:** 8px
*   **md:** 12px
*   **lg:** 16px
*   **xl:** 24px
*   **2xl:** 32px
*   **3xl:** 48px

### Radius
Subtle and professional. 
*   **Inputs/Buttons:** 4px
*   **Cards/Modals:** 6px
*   **Status Badges:** 9999px (Pill shape, but small)
*   *Never use massive 12px+ rounding for standard UI elements.*

### Elevation
**Borders over shadows.** 
*   Standard cards and tables use a 1px `gray-200` border.
*   Shadows are reserved *only* for floating elements: Dropdowns, Modals, Tooltips.
*   **Shadow sm:** `0 1px 2px 0 rgb(0 0 0 / 0.05)` (Dropdowns)
*   **Shadow md:** `0 4px 6px -1px rgb(0 0 0 / 0.1)` (Modals)

### Icons
Library: **Lucide Icons** or **Heroicons** (Outline style).
*   Stroke width: 1.5px or 2px (Consistent across the app).
*   Sizes: 16px (inline), 20px (standard UI), 24px (navigation/empty states).
*   *Never mix icon libraries. Never use filled icons unless indicating an active/favorited state.*

### Motion
*   Duration: 150ms.
*   Easing: `ease-out`.
*   Use only for state changes (hover, focus, modal entry). *No bouncing, no sliding in from off-screen for standard elements.*

---

## 3. Component Standards

### Buttons
*   **Primary:** Solid `indigo-600`, white text. Used for the single most important action on a page (e.g., "Save", "Approve", "Create Document").
*   **Secondary:** White background, `gray-300` border, `gray-700` text. Used for secondary actions (e.g., "Cancel", "Filter").
*   **Ghost:** Transparent background, `gray-600` text. Hover shows `gray-100` bg. Used for row actions, inline edits.
*   **Danger:** White background, `red-200` border, `red-700` text. Used for destructive actions (Delete, Revoke Link).
*   *Do:* Keep button text concise (1-3 words).
*   *Don't:* Use primary buttons for secondary actions. Don't use color variations (green, blue, purple) for different buttons.

### Inputs & Forms
*   **Text Input:** White bg, 1px `gray-300` border, 8px vertical / 12px horizontal padding. Focus state: 2px `indigo-500` ring, border becomes `indigo-500`.
*   **Labels:** 14px, Weight 500, `gray-700`, placed above the input.
*   **Helper Text:** 13px, `gray-500`, placed below input.
*   **Error State:** Border becomes `red-500`, ring becomes `red-500`. Error message in 13px `red-600` below input.
*   *Do:* Group related fields in a bordered card.
*   *Don't:* Use floating labels. Use placeholder text as the only label.

### Tables (High Density)
*   **Header:** `gray-50` background, 13px Weight 600 `gray-500` uppercase text. Sticky top.
*   **Rows:** White background, 14px `gray-700` text. Bottom border `gray-200`.
*   **Hover:** Row background changes to `gray-50`.
*   **Actions:** Right-aligned. Primary action is clicking the title. Secondary actions (Edit, Share, Delete) are in a 3-dot (Kebab) menu.
*   *Do:* Allow sorting via clickable headers.
*   *Don't:* Add excessive padding. Don't wrap text unnecessarily in columns like "Status" or "Date".

### Status Badges
Used for Document and Version states. Small pill shape, light background, dark text, 12px font, Weight 500.
*   **Pending:** `bg-amber-50 text-amber-800 ring-1 ring-amber-200`
*   **Active:** `bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200`
*   **Rejected:** `bg-red-50 text-red-800 ring-1 ring-red-200`
*   **Draft:** `bg-gray-50 text-gray-800 ring-1 ring-gray-200`

### Cards & Containers
*   White background, 1px `gray-200` border, 6px radius.
*   Padding: 16px or 24px.
*   *Do:* Use cards to group related settings (e.g., "Share Link Settings").
*   *Don't:* Nest cards inside cards. Don't use shadows for standard cards.

### Dialogs (Modals) & Drawers
*   **Modals:** Centered, max-width 480px (small) to 640px (large). White bg, `shadow-md`. Used for confirmations ("Are you sure you want to delete?") and quick forms ("Generate Share Link").
*   **Drawers:** Slide in from the right, width 400px to 500px. Used for secondary contexts without leaving the page (e.g., viewing Version History details, editing document metadata).

---

## 4. Layout System

### Application Shell
*   **Sidebar (Left):** Fixed width 240px. Collapsible to 64px (icons only). Background `white`, right border `gray-200`. Contains main navigation.
*   **Topbar (Top):** Fixed height 64px. Background `white`, bottom border `gray-200`. Contains Breadcrumbs (left), Global Search (center/left), Notifications & Profile (right).
*   **Content Area:** Background `gray-50`. Padding 24px or 32px. Max-width 1280px centered, or fluid depending on the page.

### Page Layouts
1.  **List Pages (Documents, Users, Divisions):**
    *   Header: Page Title (H1) + Primary Action Button (e.g., "Create Document") on the right.
    *   Filters/Search: Row of inputs/dropdowns below header.
    *   Content: Data Table.
2.  **Detail Pages (Document Detail):**
    *   Header: Breadcrumb, Document ID (Monospace), Document Title (H1).
    *   Actions: "Edit", "Share", "Public Toggle" aligned right.
    *   Content: Tabbed interface (`Content`, `Version History`, `Access & Sharing`).
3.  **Editor Page:**
    *   Distraction-free. Topbar remains for global nav.
    *   Main area: JoditEditor taking up available width.
    *   Right Sidebar (optional/collapsible): Document metadata, quick version info.
4.  **Admin Settings:**
    *   Left sub-navigation for settings categories (Divisions, Users, Retention).
    *   Right content area for the specific setting form/table.

---

## 5. Data Density & UX Principles

### Data Density
*   **Tables:** Row height should be compact (approx 48px-52px). Do not use 80px+ rows.
*   **Forms:** Stack inputs vertically. Use 2-column grids only for very short inputs (e.g., First Name / Last Name).
*   **Whitespace:** Use whitespace to separate *sections*, not to push content to the bottom of the screen.

### UX Feedback Loops
*   **Loading:** Use Skeleton loaders for tables and dashboards. Never use full-page spinners if only a section is loading.
*   **Saving:** Disable the "Save" button and show a spinner inside it while processing.
*   **Success:** Toast notification at top-right (e.g., "Document saved successfully"). Auto-dismiss in 3s.
*   **Errors:** Inline validation for forms. Toast notification for global errors.
*   **Destructive Actions:** Always require a confirmation modal. If deleting a document, require typing the document ID to confirm.

---

## 6. Requirement Traceability (Page Mapping)

Every page must be traced to the System Analysis Document.

| Page / Screen | Related Requirements | Target User | Core Workflow Supported | Key UI Components |
| :--- | :--- | :--- | :--- | :--- |
| **Login** | FR-001, NFR-001 | All | Authentication | Centered Card, Email/Password inputs, Primary Button. |
| **Dashboard** | FR-011, FR-013 | All | Quick access, Notifications | "My Documents" table, "Pending Approvals" list (for Head), Notification dropdown. *No giant KPI cards.* |
| **Document List** | FR-002, FR-007, FR-011 | Owner, Viewer | Browse, Search, Filter | Header with "Create" button, Search bar, Division/Status filters, Data Table. |
| **Document Detail** | FR-005, FR-008, FR-009 | Owner, Head, Viewer | View, Manage Access, Review History | Tabs (Content, History, Access). Public Toggle switch. "Generate Link" button. |
| **Document Editor** | FR-003, FR-004, FR-006 | Owner, Editor | Create/Edit content | JoditEditor (restricted toolbar). "Save" (creates pending version). |
| **Approval Queue** | FR-006, FR-012 | Division Head | Review and Approve/Reject | List of pending edits/rollbacks. "Approve" (Primary), "Reject" (Danger) buttons. |
| **Admin: Divisions** | FR-009, FR-011 | Admin | Manage organizational structure | Data Table of divisions. Create/Edit Modal. |
| **Admin: Users** | FR-001, FR-009 | Admin | Manage systemic roles | Data Table of users. Create/Edit Modal with Division & Role selects. |
| **Admin: Settings** | FR-010 | Admin | Configure retention | Form with "Retention Days" input. Save button. |

---

## 7. Self-Review Checklist

Before generating any UI code, the AI Agent must verify:

1.  [ ] **Is it calm?** Are there any unnecessary gradients, shadows, or colors? (If yes, remove them).
2.  [ ] **Is it dense?** Can the user see more data without scrolling? (Optimize table padding).
3.  [ ] **Is the hierarchy clear?** Is the primary action obvious? Are secondary actions muted?
4.  [ ] **Is it consistent?** Am I using the exact tokens defined in this document? (e.g., `gray-200` for borders, not `gray-300`).
5.  [ ] **Is it accessible?** Do buttons have text or clear aria-labels? Is contrast sufficient?
6.  [ ] **Does it match the workflow?** Does this screen actually solve the business requirement, or is it just decorative?

---

## 8. Final Directives for AI Agents

*   **Never invent features.** If it's not in the System Analysis Document, do not build it.
*   **Never use "AI Slop" UI.** No giant colorful stat cards. No glassmorphism. No floating decorative shapes.
*   **Strictly enforce tokens.** Do not use arbitrary Tailwind classes like `p-[13px]` or `text-[#1a1a1a]`. Use the defined scale (`p-3`, `text-gray-900`).
*   **Prioritize the Editor.** The JoditEditor is the core of the app. Ensure it has enough space, clear boundaries, and a clean, restricted toolbar.
*   **Respect the Workflow.** The transition from `Pending` to `Active` via `Approval` is the most critical business logic. The UI must make the status of a document instantly recognizable via the Status Badges.