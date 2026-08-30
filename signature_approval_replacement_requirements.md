# Signature Approval and Replacement Requirements

## 1. Signature Approval Notification

When a user who has been requested to provide a signature approves the signature request, a notification must appear on the **requester’s page**.

The notification should clearly inform the requester that:

- The requested party has **approved the signature request**.
- The requester must perform a **“Replace Signature”** action to display the approved signature in the document.
- This manual replacement is required because **OnlyOffice does not automatically load or fetch the approved signature** into the document.

## 2. Signature Replacement Limitation

Implement a **one-to-one replacement rule** for approved signatures:

- Each approved signature request provides **one available signature replacement**.
- One approval can only be used to replace **one signature**.
- After the requester uses the replacement action, that approval is considered **consumed** and cannot be reused.
- The system must prevent the same approval from being used for multiple signature replacements.

## 3. Multiple Signature Requests

The requester must be able to request signatures from **multiple distinct users** within the same document.

For example:

- Request Signature → User A
- Request Signature → User B
- Request Signature → User C

Each requested user must have their own independent signature request and approval status.

## 4. Insert Signature Modal

Update the **“Insert Signature”** modal to include a signature request status section.

The modal should display:

### Available Signatures to Replace

- Show the number of approved signatures that are available for replacement.
- Example: `Available to Replace: 2`
- Only approved and unused signatures should be counted.

### Pending Signature Requests

Display a list of users whose signature requests have not yet been approved.

Each request should show its current status, such as:

- **Pending**
- **Approved**
- **Replaced / Used**

### Replacement Availability

- Only approved and unused signatures can be selected for replacement.
- Once a signature has been replaced, it must no longer be counted as available.
- A used approval cannot be reused for another signature replacement.

## 5. Expected Flow

1. The requester sends a signature request to one or more users.
2. Each requested user receives their own independent signature request.
3. A requested user approves the signature request.
4. The requester receives a notification that the signature has been approved.
5. The requester opens the **Insert Signature** modal.
6. The modal shows:
   - The number of approved signatures available for replacement.
   - The list of pending signature requests.
   - The current status of each signature request.
7. The requester performs **Replace Signature**.
8. The approved signature is inserted/displayed in the document.
9. The approval is marked as **used/replaced**.
10. The same approval cannot be used for another replacement.

## 6. Important Rules

- Each signature request belongs to one specific requested user.
- Multiple different users can be requested to sign the same document.
- Each approval provides exactly **one replacement opportunity**.
- One approval cannot be used to replace multiple signatures.
- Only approved and unused signatures are available for replacement.
- Pending requests must remain visible until they are approved or otherwise completed.
- The requester must be explicitly notified when a requested user approves their signature request.
- The requester must manually use **Replace Signature** because OnlyOffice does not automatically fetch the approved signature.
