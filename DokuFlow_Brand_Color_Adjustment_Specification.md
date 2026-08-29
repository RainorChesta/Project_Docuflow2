# Brand Color Adjustment Specification

## Objective

Update the web project's primary visual color system so that it matches
the supplied DMS logo.

The logo is visually built around two dominant brand tones:

1.  **Blue** --- represents trust, technology, documents, and
    reliability.
2.  **Teal** --- represents validation, workflow, approval, and
    successful document processing.

The implementation must use **only these two brand tones** for the
primary visual identity. Avoid introducing additional brand colors,
gradients, purple, orange, or unrelated accent colors.

The result should feel clean, professional, modern, and consistent
across both **Light Mode** and **Dark Mode**.

------------------------------------------------------------------------

## 1. Approved Brand Palette

### Light Mode

  --------------------------------------------------------------------------
  Token               Color             Hex               Primary Usage
  ------------------- ----------------- ----------------- ------------------
  `--brand-primary`   Deep Logo Blue    `#0F6DB7`         Primary buttons,
                                                          links, active
                                                          states, navigation
                                                          accents

  `--brand-accent`    Deep Logo Teal    `#147D6A`         Secondary actions,
                                                          success-oriented
                                                          brand accents,
                                                          confirmation
                                                          states
  --------------------------------------------------------------------------

These two colors should be the only dominant brand colors in the
interface.

### Dark Mode

Use brighter variants only where necessary to maintain visibility
against dark surfaces.

  -------------------------------------------------------------------------
  Token               Color             Hex               Primary Usage
  ------------------- ----------------- ----------------- -----------------
  `--brand-primary`   Light Logo Blue   `#4AA9F0`         Primary buttons,
                                                          links, focus
                                                          states, active
                                                          navigation

  `--brand-accent`    Light Logo Teal   `#35C8A8`         Secondary
                                                          actions,
                                                          confirmation
                                                          accents, active
                                                          indicators
  -------------------------------------------------------------------------

The dark-mode variants should preserve the same blue/teal identity
rather than introducing a new color family.

------------------------------------------------------------------------

## 2. Design Principle

The interface should visually follow this hierarchy:

**Blue = Primary**

Use blue for the majority of interactive brand elements:

-   Primary buttons
-   Main call-to-action buttons
-   Navigation active states
-   Selected tabs
-   Links
-   Focus indicators
-   Main icons
-   Progress indicators
-   Important interactive controls

**Teal = Secondary**

Use teal more selectively:

-   Secondary actions
-   Approval/confirmation actions
-   Successful workflow indicators
-   Document validation indicators
-   Selected secondary controls
-   Supporting icons
-   Small visual accents

Do not use blue and teal together excessively inside the same component.
One should normally be the dominant color and the other should act as a
supporting accent.

------------------------------------------------------------------------

## 3. Light/Dark Theme Variables

Implement the color system using CSS custom properties so the theme can
be changed centrally.

``` css
:root {
    --brand-primary: #0F6DB7;
    --brand-primary-hover: #0B5F9F;
    --brand-primary-soft: #EAF5FC;

    --brand-accent: #147D6A;
    --brand-accent-hover: #0F6E5D;
    --brand-accent-soft: #E8F6F2;

    --brand-on-primary: #FFFFFF;
    --brand-on-accent: #FFFFFF;
}

[data-theme="dark"] {
    --brand-primary: #4AA9F0;
    --brand-primary-hover: #67BEF8;
    --brand-primary-soft: #12324A;

    --brand-accent: #35C8A8;
    --brand-accent-hover: #52D7BB;
    --brand-accent-soft: #123B34;

    --brand-on-primary: #07111A;
    --brand-on-accent: #06130F;
}
```

If the project uses a different dark-mode selector, such as `.dark`,
`[data-bs-theme="dark"]`, or `html.dark`, adapt the selector without
changing the approved color values.

------------------------------------------------------------------------

## 4. Application to the Web UI

Replace existing generic primary colors with the new brand tokens.

### Buttons

Primary action:

``` css
.btn-primary {
    background-color: var(--brand-primary);
    border-color: var(--brand-primary);
    color: var(--brand-on-primary);
}

.btn-primary:hover,
.btn-primary:focus {
    background-color: var(--brand-primary-hover);
    border-color: var(--brand-primary-hover);
    color: var(--brand-on-primary);
}
```

Secondary brand action:

``` css
.btn-brand-accent {
    background-color: var(--brand-accent);
    border-color: var(--brand-accent);
    color: var(--brand-on-accent);
}

.btn-brand-accent:hover,
.btn-brand-accent:focus {
    background-color: var(--brand-accent-hover);
    border-color: var(--brand-accent-hover);
    color: var(--brand-on-accent);
}
```

------------------------------------------------------------------------

## 5. Links and Interactive Elements

Use the primary blue for standard interactive elements.

``` css
a {
    color: var(--brand-primary);
}

a:hover {
    color: var(--brand-primary-hover);
}
```

Active navigation items should use the primary blue.

Selected or confirmation-oriented controls may use the teal accent.

Avoid using both colors for the same text or icon unless there is a
clear functional reason.

------------------------------------------------------------------------

## 6. Cards, Badges, Alerts, and Components

Use the brand colors carefully rather than filling large surfaces with
saturated color.

Recommended pattern:

``` css
.brand-primary-soft {
    background-color: var(--brand-primary-soft);
    color: var(--brand-primary);
}

.brand-accent-soft {
    background-color: var(--brand-accent-soft);
    color: var(--brand-accent);
}
```

Use the soft variants for:

-   Small badges
-   Informational cards
-   Document status indicators
-   Icon backgrounds
-   Table highlights
-   Subtle navigation indicators

Do not create additional brand shades unless they are required for
hover, active, disabled, or accessibility purposes.

------------------------------------------------------------------------

## 7. Dark Mode Requirements

Dark mode must not simply invert the light theme.

The dark theme should use:

-   Very dark neutral backgrounds
-   Light neutral text
-   Lightened blue for primary interactive elements
-   Lightened teal for secondary brand accents
-   No bright white page backgrounds
-   No excessive saturated color blocks

Recommended neutral foundation:

``` css
[data-theme="dark"] {
    --surface-page: #0B1220;
    --surface-card: #111827;
    --surface-elevated: #172033;

    --text-primary: #F3F6FA;
    --text-secondary: #B7C2CF;
    --border-default: #263447;
}
```

The brand colors should remain visually distinct from these neutral
surfaces.

------------------------------------------------------------------------

## 8. Bootstrap Integration

If the project uses Bootstrap 5, map the brand palette to Bootstrap's
primary variables.

``` scss
$primary: #0F6DB7;
$success: #147D6A;
```

For runtime theme switching with CSS variables, prefer overriding
component styles with the CSS custom properties defined in this
specification.

Do not redefine the entire Bootstrap color system just to change the
brand identity.

The goal is to make the project visually blue/teal while keeping
Bootstrap's existing semantic behavior intact.

------------------------------------------------------------------------

## 9. Laravel + Blade + Vite Integration

The color tokens should be placed in the project's central stylesheet,
for example:

``` text
resources/css/app.css
```

or the project's existing global stylesheet.

Import that stylesheet through Vite and ensure all Blade components use
the shared tokens.

Do not hard-code brand colors repeatedly throughout Blade templates.

### Preferred

``` html
<button class="btn btn-primary">
    Upload Document
</button>
```

or:

``` css
.document-action {
    background: var(--brand-primary);
}
```

### Avoid

``` html
<button style="background:#2088D8">
```

The project should have a single source of truth for the brand colors.

------------------------------------------------------------------------

## 10. Theme Switching

The existing Light/Dark theme switcher must continue to work.

When switching themes:

-   Light Mode → use the Light Mode brand palette.
-   Dark Mode → use the Dark Mode brand palette.
-   Do not change the brand identity when switching themes.
-   Only adjust brightness/value when required for readability.
-   Avoid flickering during initial page load.
-   Preserve the user's selected theme using the project's existing
    theme persistence mechanism.

The logo itself should remain visually consistent and should not be
recolored unless there is a separate monochrome logo asset.

------------------------------------------------------------------------

## 11. Accessibility Requirements

Color contrast must be considered for every interactive element.

For dark mode, use the darker text colors defined by
`--brand-on-primary` and `--brand-on-accent` when placing text directly
on the brighter brand colors.

Do not force white text onto a bright dark-mode blue or teal if it
produces insufficient contrast.

Focus states must remain clearly visible:

``` css
:focus-visible {
    outline: 2px solid var(--brand-primary);
    outline-offset: 2px;
}
```

Do not communicate important information through color alone. Combine
color with text, icons, labels, or other visual indicators when
appropriate.

------------------------------------------------------------------------

## 12. Usage Rules

### Do

-   Use **Blue as the main brand color**.
-   Use **Teal as the supporting brand color**.
-   Keep the interface visually restrained.
-   Use soft blue/teal backgrounds for subtle component states.
-   Use lighter brand variants in dark mode.
-   Keep neutral backgrounds and text independent from the brand
    palette.
-   Centralize all brand colors in CSS variables.

### Do Not

-   Do not introduce purple as another primary accent.
-   Do not introduce orange, pink, or unrelated gradients.
-   Do not use multiple random shades of blue.
-   Do not use multiple random shades of green.
-   Do not create gradient backgrounds from blue to teal.
-   Do not make every component saturated.
-   Do not use teal as the dominant color when a primary action should
    be blue.
-   Do not hard-code different blue/teal values across individual
    components.

------------------------------------------------------------------------

## 13. Recommended Visual Hierarchy

Use the following priority:

``` text
PRIMARY
Logo Blue
#0F6DB7
        ↓
Main navigation
Primary buttons
Links
Active states
Main actions

SECONDARY
Logo Teal
#147D6A
        ↓
Approval
Confirmation
Secondary actions
Supporting accents
Workflow states

NEUTRAL
        ↓
Page background
Cards
Borders
Text
Tables
Form controls
```

For Dark Mode:

``` text
PRIMARY
Light Logo Blue
#4AA9F0

SECONDARY
Light Logo Teal
#35C8A8

NEUTRAL
Dark surfaces
Light text
Subtle borders
```

------------------------------------------------------------------------

## 14. Final Implementation Goal

The finished web application should immediately feel visually connected
to the supplied logo.

The intended visual identity is:

> **Professional + Modern + Trustworthy + Document/Workflow Oriented**

The color system should remain intentionally minimal:

**Blue + Teal + Neutral UI**

Do not expand the brand palette unnecessarily.

The blue should visually dominate the interface, while teal should
provide controlled supporting emphasis. In Dark Mode, use the brighter
variants to maintain visibility while preserving the same brand
identity.

The final result should look like one coherent product rather than a
collection of components using unrelated colors.
