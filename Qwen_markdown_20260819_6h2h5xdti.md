# 🤖 AI Agent Implementation Plan

**Context:** You are an expert full-stack developer AI agent. Your task is to resolve bugs and implement missing features based on the ticket list below. 
**Rules:** 
1. Read the existing codebase structure before making changes.
2. Follow the existing design system and coding conventions.
3. Ensure all UI changes support both Light and Dark themes.
4. Write clean, maintainable code and update relevant tests if applicable.

---

## 🎟️ Task 1: Fix & Implement User-to-User Notification System
**Priority:** High
**Objective:** Fix the broken notification system and ensure all user interactions trigger UI notifications.

- [x] **1.1 Debug Notification Triggers:** Investigate why notifications are not firing when a user is requested to approve a Document or TDD (Technical Design Document). Ensure the backend emits the correct events/payloads for these actions.
- [x] **1.2 Fix Notification UI:** Ensure the frontend notification UI (bell icon, dropdown, or toast) correctly renders and displays the approval requests.
- [x] **1.3 Expand Interaction Notifications:** Audit all user-to-user interactions (e.g., comments, mentions, task assignments, approvals). Ensure every interaction triggers a corresponding notification.
- [x] **1.4 Real-time / Polling:** Ensure the notification state updates in real-time (via WebSockets/Socket.io) or via reliable polling.

**Acceptance Criteria:**
* When User A requests User B to approve a Document/TDD, User B's notification UI immediately shows the request.
* All defined user-to-user interactions successfully generate a notification record and display it in the UI.

---

## 🎟️ Task 2: Fix Server-Side Search & Localization
**Priority:** High
**Objective:** Restore server-side search functionality, add missing filters, and implement language-aware searching.

- [x] **2.1 Fix Server-Side Search API:** Debug the backend search endpoint. Ensure it correctly queries the database and returns paginated results instead of failing or returning empty.
- [x] **2.2 Add Document Type Filter:** Add a missing `document_type` filter/parameter to the search API and the frontend search UI.
- [x] **2.3 Implement Language-Aware Search:** Modify the search logic so that the text query matches the currently selected language (i18n). If the user selects English, search English content fields; if Indonesian, search Indonesian fields.

**Acceptance Criteria:**
* Server-side search returns accurate, paginated results.
* Users can filter search results specifically by Document Type.
* Search queries only match text in the currently selected UI language.

---

## 🎟️ Task 3: Restore Admin Sidebar "Signature" Menu
**Priority:** Medium
**Objective:** Fix the missing navigation item in the Admin layout.

- [x] **3.1 Add Sidebar Item:** Add the "Signature" menu item back into the Admin Sidebar component.
- [x] **3.2 Verify Routing:** Ensure the menu item links to the correct admin signature management route/page.
- [x] **3.3 Check Permissions:** Ensure the menu item respects admin role/permission checks (if applicable).

**Acceptance Criteria:**
* The "Signature" item is visible in the Admin Sidebar.
* Clicking it navigates to the correct Signature management page without 404 errors.

---

## 🎟️ Task 4: Fix Dark Theme Sidebar Active State Contrast
**Priority:** Medium
**Objective:** Fix UI/UX bug where the active sidebar item blends into the dark background.

- [x] **4.1 Update CSS/Tailwind Classes:** Locate the Sidebar component and update the styling for the `active` and `hover` states specifically for the Dark Theme.
- [x] **4.2 Ensure Visibility:** Change the active/hover state text, icon, or background indicator to **white** (or a high-contrast color) so it stands out against the dark sidebar background.

**Acceptance Criteria:**
* In Dark Mode, when a sidebar item is clicked or active, it is clearly visible (text/icon turns white or has a distinct white indicator).
* The fix does not break the Light Mode sidebar styling.

---

## 🎟️ Task 5: Implement User Profile Picture Feature
**Priority:** Medium
**Objective:** Create a complete feature allowing users to upload, update, and view their profile pictures.

- [x] **5.1 Backend / Database:** Add an `avatar_url` or `profile_picture` field to the User model/schema. Create an API endpoint for uploading images (ensure file validation, size limits, and secure storage like S3/local uploads).
- [x] **5.2 Frontend Upload Component:** Create a UI component in the User Profile/Settings page allowing users to select, preview, and upload a profile picture.
- [x] **5.3 Display Profile Picture:** Update the global Header/Navbar and User Profile page to display the user's profile picture (with a fallback to initials/placeholder if no picture is uploaded).

**Acceptance Criteria:**
* Users can successfully upload an image from their device.
* The uploaded image is saved and displayed correctly in the Header and Profile page.
* Image uploads are validated (e.g., max 2MB, JPG/PNG only).

---

### 🛠️ Agent Execution Instructions
1. Start by analyzing the current file structure to locate the relevant components for Tasks 1-5.
2. Execute tasks in order of Priority (High -> Medium).
3. For every UI change, verify both **Light** and **Dark** modes.
4. Once a task is completed, check the corresponding `[ ]` box to `[x]`.