# SKILLS.md: UI/UX Design Guidelines for Document Consolidation & Version Control System

## 1. Design Vision

**Overall Design Philosophy:**
The interface is designed to be a highly productive, enterprise-grade document management system. It prioritizes clarity, efficiency, and trust over visual flair. The design language is inspired by modern SaaS and developer tools (Linear, Notion, Stripe, GitHub), focusing on dense but readable information architecture.

**User Experience Goals:**
*   **Frictionless Workflow:** Minimize clicks to create, edit, and approve documents.
*   **Contextual Awareness:** Users should always know the status of a document (Draft, Pending, Active) and their permissions (Owner, Viewer, Editor).
*   **Calm and Focused:** The editing environment must be distraction-free to encourage writing and reviewing.

**Interface Characteristics:**
*   **Minimalist:** Generous use of white space, subtle borders, and lack of heavy shadows.
*   **Data-Dense but Readable:** Tables and lists are optimized for scanning.
*   **Deterministic:** Every action has a clear, immediate, and predictable outcome.

---

## 2. Understanding the System

**System Objectives:**
To provide a centralized, in-system document creation and management platform for various divisions. It enforces governance through automatic version control, tiered approval by Division Heads, and granular access control (default division read-only, public toggle, role-based shareable links).

**Primary Users & Roles:**
*   **Owner:** Creates and edits documents, manages access, requests rollbacks.
*   **Editor (via Link):** Edits specific documents via a shared link.
*   **Viewer:** Reads documents (default for same-division users, public docs, or via link).
*   **Division Head (Kepala Divisi):** Approves/rejects edits and rollbacks, monitors division documents, can toggle public access.
*   **System Admin:** Manages divisions, systemic roles, and version retention policies.

**Core Workflows:**
1.  **Creation & Editing:** User creates doc -> System generates ID -> User edits in JoditEditor -> Saves (creates pending version) -> Head approves -> Becomes active (read-only for division).
2.  **Access Management:** Owner toggles "Public" for cross-division read access OR generates a time-bound/role-based share link.
3.  **Rollback:** Owner selects an old version -> Requests rollback -> Head approves -> Creates a new version with old content.

**Major Modules:**
*   Authentication & Dashboard
*   Document Management (List, Create, View, Edit)
*   Version Control & Approval Queue
*   Access Control & Sharing
*   System Administration (Divisions, Users, Settings)

---

## 3. Design Principles

*   **Consistency:** Identical components must look and behave identically across all modules. Use a strict design token system.
*   **Simplicity:** Remove unnecessary UI elements. If a feature doesn't aid the primary workflow, hide it or remove it.
*   **Clarity:** Use clear typography, high contrast, and explicit labeling. Statuses (Pending, Active) must be immediately distinguishable.
*   **Predictability:** Destructive actions (Delete, Reject) require confirmation. Safe actions (Save, Approve) are immediate or clearly indicated.
*   **Efficiency:** Support keyboard shortcuts, inline editing where appropriate, and bulk actions in tables.
*   **Progressive Disclosure:** Show only what is necessary for the current task. Hide advanced settings (like share link expiration) behind expandable sections or modals.

---

## 4. Visual Design Guidelines

*   **Typography:** Use a clean, highly legible sans-serif font (e.g., Inter, Roboto, or system-ui). 
    *   *Headings:* Semibold/Bold, tight tracking.
    *   *Body:* Regular, relaxed line-height (1.5 - 1.6) for readability in long documents.
*   **Spacing System:** Base unit of 4px. Use multiples (4, 8, 12, 16, 24, 32, 48) for margins and padding to create a consistent visual rhythm.
*   **Grid System:** 12-column grid for desktop layouts. Content max-width of 1280px to prevent line-length fatigue.
*   **Border Radius:** Subtle and consistent. 4px for inputs/buttons, 6px or 8px for cards/modals. Avoid pill-shaped buttons unless for specific status badges.
*   **Shadows & Elevation:** Use borders (`border-gray-200`) instead of heavy drop shadows for cards and containers. Use very subtle shadows (`shadow-sm`) only for floating elements like dropdowns and modals.
*   **Icons:** Use a consistent icon set (e.g., Heroicons or Lucide). 20px for standard UI, 16px for inline text, 24px for navigation. Stroke width should be uniform.

---

## 5. Color Guidelines

The palette is neutral-heavy, using color strictly for semantics and primary actions.

*   **Primary Action:** Indigo-600 (`#4F46E5`) or Blue-600. Used *only* for primary buttons (Save, Approve, Create) and active navigation states.
*   **Neutral Palette:** Slate or Gray. 
    *   *Text:* Gray-900 (Headings), Gray-700 (Body), Gray-500 (Muted/Secondary).
    *   *Backgrounds:* White (Cards), Gray-50 (App Background).
    *   *Borders:* Gray-200 (Standard), Gray-300 (Hover/Focus).
*   **Semantic Colors:**
    *   *Success:* Emerald-500 / Green-600 (Approved, Active status).
    *   *Warning:* Amber-500 / Yellow-600 (Pending status).
    *   *Error/Destructive:* Red-500 / Red-600 (Rejected, Delete actions).
    *   *Information:* Blue-500 (Info alerts, links).
*   **Contrast:** Ensure all text meets WCAG AA contrast ratios (4.5:1 for normal text). Never use light gray text on white backgrounds.

---

## 6. Component Library Standards

*   **Buttons:** 
    *   *Primary:* Solid background, white text.
    *   *Secondary:* White background, gray border, gray text.
    *   *Danger:* Red text, white background, red border (or solid red for destructive modals).
    *   *Ghost:* Transparent background, gray text (for inline actions).
*   **Inputs:** White background, 1px gray border, 8px padding. Focus state shows a 2px primary color ring.
*   **Tables:** Clean rows, subtle horizontal dividers (`border-b`). Hover state on rows (`bg-gray-50`). Sticky header.
*   **Badges/Status Indicators:** Small, pill-shaped, light background with dark text (e.g., `bg-yellow-50 text-yellow-800` for Pending).
*   **Modals:** Centered, max-width 480px-640px, white background, subtle shadow, clear header, footer with action buttons.
*   **Empty States:** Centered illustration (minimal line-art) or icon, clear heading, descriptive text, and a primary CTA button.

---

## 7. Layout System

*   **Authentication Pages:** Centered card on a subtle gray background. Clean, minimal, no sidebar.
*   **Application Shell:**
    *   *Sidebar (Left):* Fixed width (240px), collapsible. Contains main navigation (Dashboard, Documents, Approvals, Admin).
    *   *Topbar (Top):* Contains global search, notification bell, and user profile dropdown.
    *   *Content Area:* Padded (24px or 32px), max-width constrained, scrollable independently of sidebar/topbar.
*   **Document Editor Page:** 
    *   Full-width or split layout. Main area for JoditEditor. 
    *   Right sidebar (collapsible) for Document Metadata (Status, Version History, Access Settings).
*   **Detail Pages:** Breadcrumb at the top. Title and primary actions (Edit, Share, Toggle Public) in a header row. Content below in tabs or sections.

---

## 8. Navigation Guidelines

*   **Sidebar Organization:** Group by user context. 
    *   *General:* Dashboard, All Documents.
    *   *Workflow:* Pending Approvals (for Heads).
    *   *Admin:* Divisions, Users, Settings (for Admins).
*   **Active States:** Highlight the active sidebar item with a subtle background tint (`bg-gray-100`) and primary text color. Do not use heavy left-border indicators if it breaks alignment.
*   **Breadcrumbs:** Use for deep navigation (e.g., `Documents > HRD > HRD-20260729-001`). Clickable to navigate up.
*   **Context Navigation:** Inside a document, use Tabs for `Content`, `Version History`, and `Access & Sharing`.

---

## 9. Form Design Standards

*   **Labels:** Top-aligned, sentence case, bold/medium weight.
*   **Helper Text:** Placed directly below the input, small font, gray color.
*   **Validation:** Inline validation on blur or submit. Error messages in red, placed directly below the input with an alert icon.
*   **Required Indicators:** Use a subtle red asterisk `*` or state "All fields are required" at the top.
*   **Field Grouping:** Group related fields (e.g., "Share Link Settings": Role, Expiration) inside a bordered card with a subtle header.
*   **Actions:** Primary action (Save/Submit) aligned right or at the bottom. Secondary action (Cancel) next to it.

---

## 10. Table and Data Display

*   **Document List Table:** 
    *   *Columns:* Document ID, Title, Division, Status (Badge), Owner, Last Updated, Actions.
    *   *Sorting:* Clickable headers with up/down arrows.
    *   *Filtering:* Top-right filter dropdowns (by Division, Status) and a search input.
*   **Pagination:** Bottom right. "Showing 1-10 of 50". Previous/Next buttons.
*   **Row Actions:** Use a 3-dot (kebab) menu on the right side of each row for secondary actions (View, Share, Delete) to keep the UI clean. Primary action (View) can be clicking the Title.

---

## 11. Dashboard Design

Dashboards must be role-specific and actionable.

*   **Owner/Viewer Dashboard:** 
    *   *Quick Stats:* Total Documents, Pending Approvals.
    *   *Recent Documents:* Table of recently viewed/edited docs.
    *   *Quick Action:* Prominent "Create New Document" button.
*   **Division Head Dashboard:**
    *   *Action Required:* List of documents pending approval (Edit/Rollback).
    *   *Division Activity:* Recent approvals/rejections.
*   **Admin Dashboard:**
    *   *System Stats:* Total users, total divisions, storage used.
    *   *System Health:* Cronjob status, recent audit logs.

---

## 12. Responsive Design

*   **Desktop (1280px+):** Primary experience. Full sidebar, multi-column layouts.
*   **Laptop (1024px):** Sidebar collapses to icons only.
*   **Tablet/Mobile (<768px):** Sidebar becomes an off-canvas drawer. Tables become horizontally scrollable or transform into card lists. Editor takes full width, metadata moves to a bottom sheet or separate tab.

---

## 13. Accessibility

*   **Keyboard Navigation:** All interactive elements must be focusable. Logical tab order. Escape key closes modals/dropdowns.
*   **Focus Indicators:** Clear, high-contrast focus rings (`ring-2 ring-offset-2 ring-primary`) for all inputs and buttons.
*   **Color Contrast:** Strict adherence to WCAG AA. Never convey status (like Pending vs Active) *only* by color; always use text labels or icons alongside color.
*   **Semantic HTML:** Use `<button>` for actions, `<a>` for navigation. Proper heading hierarchy (`h1` to `h6`).

---

## 14. UX Guidelines

*   **User Feedback:** 
    *   *Success:* Toast notification at top-right (e.g., "Document saved successfully"). Auto-dismiss after 3s.
    *   *Error:* Toast notification (red) or inline form errors.
*   **Loading States:** Use skeleton loaders for tables and dashboards. Use spinner inside the button for form submissions. Disable the button while loading to prevent double-clicks.
*   **Confirmation Dialogs:** Required for destructive actions (Delete Document, Revoke Link, Reject Version). Must clearly state the consequence.
*   **Empty States:** Never show a blank table. Show a friendly message and a CTA (e.g., "No documents found. Create your first document.").

---

## 15. Design Consistency Rules

*   **Terminology:** Use "Document" not "File". Use "Division" not "Department". Use "Approve/Reject" not "Accept/Decline".
*   **Date/Time Format:** Standardize to `DD MMM YYYY, HH:mm` (e.g., 29 Jul 2026, 14:30).
*   **Statuses:** Strictly use: `Draft` (never used per PDF, but if needed), `Pending` (Yellow), `Active` (Green), `Rejected` (Red).
*   **Icons:** Never mix different icon families.

---

## 16. Feature-to-UI Mapping

| Feature / Requirement | Page / Screen | Main Components | User Interaction |
| :--- | :--- | :--- | :--- |
| **FR-001: Login** | Login Page | Email input, Password input, Login button. | Enter credentials -> Redirect to Dashboard. |
| **FR-002: Create Doc** | Document List / Editor | "Create" button -> Redirects to Editor page with auto-generated ID. | Click Create -> System generates ID -> User enters editor. |
| **FR-003: Editor** | Document Editor Page | JoditEditor (restricted toolbar: bold, italic, heading, list, image, link). | Type/format text -> Click "Save". |
| **FR-004/005: Versioning** | Document Detail (History Tab) | Version timeline/list. Shows version number, author name, timestamp, status. | Click version to view content. |
| **FR-006: Approval** | Pending Approvals Page | List of pending edits/rollbacks. "Approve" (Green) and "Reject" (Red) buttons. | Head reviews diff/content -> Clicks Approve/Reject. |
| **FR-007: Default Read-Only** | Document View Page | Read-only view of Jodit content. No edit toolbar. | User views content. Edit button hidden/disabled. |
| **FR-008: Public Toggle** | Document Detail (Access Tab) | Toggle switch labeled "Public Access". Helper text explaining effect. | Owner/Head toggles switch -> Updates instantly. |
| **FR-009: Share Link** | Document Detail (Access Tab) | "Generate Link" button -> Modal with Role dropdown, Expiration date picker, "Forever" checkbox. | Configure link -> Generate -> Copy to clipboard. |
| **FR-011: Admin Roles/Div** | Admin Settings Pages | Data tables for Users and Divisions. CRUD modals. | Admin creates/edits/deletes records. |
| **FR-012: Retention** | Admin Settings Page | Input field for "Version Retention (Days)". Save button. | Admin updates days -> Saves. |
| **FR-013: Search** | Global Topbar / Doc List | Search input with magnifying glass icon. | Type query -> Results filter instantly or on enter. |
| **FR-014: Audit Log** | Admin/Head Audit Page | Read-only table. Columns: User, Action, Target, Timestamp. | View history. No edit/delete actions. |
| **FR-015: Notifications** | Topbar Notification Bell | Dropdown panel listing recent events (e.g., "Doc X edited"). | Click bell -> View list -> Click item to navigate. |

---

## 17. Open Questions & Clarifications Needed

Before finalizing the UI for specific edge cases, the following contradictions or missing details from the System Analysis Document must be clarified:

1.  **Document ID Format Contradiction:** 
    *   *Assumption 5* states the format is `[Kode Divisi]-[Tgl YYYYMMDD]-[Nomor Urut]` (e.g., HRD-20260729-001). 
    *   *FR-002 & Acceptance Criteria* state the format is `[Divisi]-[NomorUrut]` (e.g., HRD-001). 
    *   *Question:* Which format should be displayed in the UI and generated by the system?
2.  **Audit Log Deletion Risk:** 
    *   In Q3, the client answered "Ya tetap dihapus" (Yes, still deleted) regarding whether approval logs are kept after the document version is deleted by the retention cronjob. 
    *   *Question:* This violates standard audit/compliance practices. Should the UI include a warning when the Admin changes retention settings, or is the deletion of audit logs a strict, non-negotiable requirement?
3.  **Public Toggle Approval:** 
    *   In Q1, the client answered: "wewenang owner tapi status dokumen sudah di approve oleh kepala divisi" (Owner's authority, but the document status must be approved by the division head). 
    *   *Question:* Does this mean the "Public" toggle is disabled/hidden until the document has at least one `Active` (approved) version? Or does the act of toggling "Public" itself require the Head's approval?
4.  **User Resignation/Inactivity:** 
    *   The document mentions ownership transfers if the Owner "resigns". 
    *   *Question:* How is resignation represented in the system? Is there an `is_active` boolean flag for users? If a user is inactive, should their name be grayed out in the UI, and should the system automatically prompt the next editor to take ownership?