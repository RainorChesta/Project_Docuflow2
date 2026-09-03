# Multi-Signature Feature Spec

## 1. Overview
Users can create & store multiple signatures. Each additional signature must be associated with a specific company (signature + company stamp).

## 2. Signature Creation Rules

### 2.1 Original Signature (Signature #1)
- Must be user's first signature.
- No company stamp.
- Method: draw via canvas OR upload image.

### 2.2 Additional Signatures (Signature #2, #3, ...)
- Can only be created after Original Signature exists.
- Method: upload image only. Canvas draw NOT allowed.
- Must select 1 company on create.
- Signature+stamp image prepared by user themselves, system just accepts upload.

### 2.3 Company Selection on Create
- Dropdown/list only shows companies user has access to.
- Example: user has access to CMH & JBM → only CMH, JBM appear in selection.

## 3. Editor — Insert Signature

- "Insert Signature" button existing, add "My Signatures" section.
- List all user's signatures, differentiated by:
  - Type: Original vs Company Stamp
  - Company name (for signature+stamp)
- Example display:
  - Original Signature
  - Signature + Company A Stamp
  - Signature + Company B Stamp

## 4. Request Signature Flow

### 4.1 Performance Constraint
- DO NOT preload/display signature list before requester searches & selects target user.
- Reason: prevent lag, many users × many signatures+stamps.

### 4.2 After User Selected
- System loads & displays signature options for that user.
- Requester can choose:
  - That user's Original Signature, or
  - Company-specific signature (stamp) — limited to companies target user has access to.

### 4.3 Authorization Guard
- Requester cannot request signature for company not owned/accessed by target user.
- Validation: requested signature must exist in target user's stored signatures AND company must match user's access list.

## 5. Data Model (suggestion)

```
Signature {
  id
  user_id
  type: "original" | "company_stamp"
  company_id: nullable (null if original)
  image_url
  created_via: "canvas" | "upload"
  created_at
}

UserCompanyAccess {
  user_id
  company_id
}
```

Constraints:
- type=original → company_id NULL, created_via either (canvas/upload).
- type=company_stamp → company_id NOT NULL, created_via = upload only.
- company_id on Signature must exist in UserCompanyAccess for same user_id.

## 6. Validation Rules Summary

| Rule | Enforcement |
|---|---|
| Signature #1 = original, no stamp | Block create type=company_stamp if no original yet |
| Signature #1 method canvas/upload | Both allowed |
| Signature #2+ method | Upload only, block canvas |
| Signature #2+ company | Required, must be in user's access list |
| Request Signature preload | Lazy load, triggered by user search+select |
| Request Signature company options | Filtered by target user's access + stored signatures |
