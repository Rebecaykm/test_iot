---
name: bootstrap-ui
description: Modern anti-generic UI/UX guardrails for this project's admin views (Bootstrap 4.6 via AdminLTE 3, NOT Bootstrap 5). Use when creating or auditing any AdminLTE/admin Blade view — cards, tables, buttons, badges, forms.
---

# Bootstrap UI/UX Design System (adapted to this project's real stack)

## Stack reality check — read this first

This project's admin panel (`@extends('adminlte::page')`, all `resources/views/{resource}/*.blade.php`
outside `guest/`) runs on **AdminLTE 3, which vendors Bootstrap 4.6** — confirmed by
`pagination::bootstrap-4` usage and `data-dismiss`/`.close`/`font-weight-bold`/`mr-*` syntax
throughout the codebase. It is **not** Bootstrap 5. `package.json` has no Bootstrap entry at all —
BS4 arrives precompiled from the `jeroennoten/laravel-adminlte` composer package, so there is no
Sass build step here to override Bootstrap's variables at compile time.

Practical consequences:
- BS5-only utilities (`rounded-3`/`rounded-4`, `bg-*-subtle`, `text-body-secondary`, `form-floating`,
  `gap-*`) **do not exist** in the loaded CSS. Using them silently does nothing.
- BS5's `:root { --bs-primary: ... }` variable override **does not work** — BS4 components read
  Sass variables baked in at compile time, not CSS custom properties, and this project doesn't
  recompile Bootstrap's Sass.
- BS5.3's `data-bs-theme="dark"` **does not exist**. This project's real dark-mode toggle
  (`resources/views/components/theme-toggle.blade.php`) toggles a `dark` class on `<html>` for the
  Tailwind/Alpine layouts (`layouts/app.blade.php`, `layouts/guest.blade.php`) — and as of this
  writing it is **not wired into AdminLTE views** (`config/adminlte.php` has `layout_dark_mode` set
  to `null`). Don't add `data-bs-theme` blocks; if dark mode for an AdminLTE view is actually needed,
  that's a separate wiring task, not a CSS-only one — flag it instead of guessing at a selector.

So: apply every principle below, but through **BS4-safe classes plus small custom "bridge" CSS**
that mimics the BS5 utility names/effect. This project already has exactly that, as a **single
project-wide partial** —
[`resources/views/partials/theme-styles.blade.php`](../../../resources/views/partials/theme-styles.blade.php)
(include via `@include('partials.theme-styles')` inside `@section('css')`), paired with
[`partials/theme-alerts.blade.php`](../../../resources/views/partials/theme-alerts.blade.php) (session
success/error alerts, include at the top of `@section('content')`) and
[`partials/theme-scripts.blade.php`](../../../resources/views/partials/theme-scripts.blade.php)
(auto-dismiss alerts + `.delete-form` SweetAlert2 confirmation, include inside `@section('js')`,
optionally passing `deleteMessage`).

Two more opt-in partials, only for views that need them (don't include unconditionally):
- [`partials/theme-buttons-outline.blade.php`](../../../resources/views/partials/theme-buttons-outline.blade.php)
  — reskins `.btn-action-primary/-success/-danger/-solid/-warning` to outline instead of soft/solid.
  Include after `theme-styles` in `@section('css')`. Currently used by `part-numbers`, `work-centers`,
  `material-validations`, `production-records`, `areas`, `lines`, `projects` — NOT `users`/`tag-types`,
  which keep the soft/solid default. This is a deliberate per-view choice, not a global default — don't
  add it to `theme-styles.blade.php` itself. Given how many resources have opted in, if a new resource
  is being built or audited, ask whether it should get outline too rather than assuming soft/solid.
- [`partials/theme-datepicker-styles.blade.php`](../../../resources/views/partials/theme-datepicker-styles.blade.php)
  + [`theme-datepicker-scripts.blade.php`](../../../resources/views/partials/theme-datepicker-scripts.blade.php)
  — Flatpickr (themed to match the palette), for any `<input type="text" class="filter-input flatpickr-date">`
  (native `<input type="date">` can't be restyled by CSS at all — this replaces it). Include the styles
  partial in `@section('css')` and the scripts partial in `@section('js')`. Add `data-auto-submit="1"`
  on an input that should submit its form on date change.

Two reusable Blade components (`resources/views/components/`), for anywhere a styled dropdown is
needed instead of a native `<select>`/checkbox grid — no `@include` needed, just use the tag:
- `<x-multi-select name="..." label="..." :options="$collection->map(fn ($x) => ['value' => ..., 'label' => ..., 'selected' => bool])" />`
  — searchable multi-pick dropdown with removable chips, backed by real `name="..[]"` checkboxes (so
  it submits/validates exactly like a native checkbox group — no controller changes needed to adopt
  it). Used by `production-records/summary` (Estaciones), `users/edit`+`create` (Líneas Asociadas),
  `lines/edit`+`create` (Estaciones de Trabajo).
- `<x-select name="..." label="..." :options="[['value' => ..., 'label' => ..., 'selected' => bool], ...]" />`
  — single-pick version of the same visual widget (dropdown panel, optional search box past 6 options),
  backed by a real hidden `<select name="...">` that fires a native `change` event, so it's a drop-in
  replacement for a plain `<select class="filter-input">` filter field. Used by `material-validations/index`
  (Estado), `production-records/summary` (Turno). Prefer this over Select2 for **filter-bar** selects so
  the whole filter row looks visually consistent; Select2 (already integrated, themed separately in
  `theme-styles.blade.php`) stays as-is for the **create/edit form** selects that already use it
  (`users` Rol, `lines` Área/Color) — don't migrate those without being asked, they're a different,
  already-established pattern for a different context (form field vs. filter).

Both components share the `.ms-*` CSS classes in `theme-styles.blade.php` (`.ms-dropdown`, `.ms-trigger`,
`.ms-panel`, `.ms-option`, etc.) — don't duplicate this CSS into a view-specific `<style>` block if a
third use case comes up; extend the component instead.

**Gotcha that already caused a real outage**: never write a literal `<x-component-name>` (angle
brackets included) inside a CSS `/* comment */` or prose text in a `.blade.php` file, even just to
document "this CSS is used by `<x-multi-select>`" — Blade's component-tag compiler matches `<x-...>`
as real markup regardless of CSS/text context (it has no concept of "inside a comment"), and an opening
tag with no matching closing tag compiles to a `<?php if (...): ?>` with no `endif`, which is syntactically
valid Blade (no compile-time error — `view:cache` and `Blade::compileString` both report success) but
produces genuinely broken PHP that only fails at request time with "unexpected end of file, expecting
elseif or else or endif". If you need to reference a component by name in a comment, spell it without
angle brackets ("the multi-select component"). To catch this class of bug before it ships, don't just
run `php artisan view:cache` — also lint the actual compiled output:
`for f in storage/framework/views/*.php; do php -l "$f" || echo "FAIL: $f"; done`.

As of this writing `theme-styles.blade.php` is shared by `part-numbers`, `work-centers`,
`users`, `tag-types`, `lines`, `areas`, `material-validations`, `production-records`, and `infor-sync`
— **always reuse/extend this partial, never create a
per-resource copy.** (`part-numbers` originally had its own `_styles.blade.php` duplicating ~90% of
this file — that was a mistake made before this partial's existence was discovered; it has since been
deleted and `part-numbers` migrated onto the shared partial. Don't repeat that mistake for a new
resource.)

Known gap, not yet cleaned up: `production-sequences` still references legacy `.btn-filter-submit`/
`.btn-filter-clear` classes (kept in the partial for backward compatibility — don't remove them without
first migrating that view's markup to `.btn-action.btn-action-solid`/`.btn-action.btn-action-secondary`,
same as was already done for `part-numbers`, `work-centers`, `material-validations`, `production-records`,
and `infor-sync`). Treat that as a separate, explicitly-scoped task, not something to fold into an
unrelated change.

## Core Design Principles (BS5 spec → BS4 reality)

### 1. Anti-Generic Aesthetic
- Never ship stock `.btn-primary` (`#007bff`) or `.badge-primary` as-is — same "generic Bootstrap"
  problem exists in BS4. Use the soft/pastel component classes below instead.
- High breathability spacing: `p-4`, `p-lg-5` work unchanged in BS4. `gap-*` **does not exist** in
  BS4 flexbox utilities — don't repeat `style="gap: 0.5rem"` inline across views (current
  anti-pattern in `index.blade.php`/`edit.blade.php`); instead define bridge classes once:
  ```css
  .gap-1 { gap: .25rem; } .gap-2 { gap: .5rem; } .gap-3 { gap: .75rem; } .gap-4 { gap: 1rem; }
  ```

### 2. Elevation & Borders
- Cards: `card border-0 shadow-sm` — both exist in BS4 unchanged. `rounded-4` doesn't exist in BS4
  (its `.rounded`/`.rounded-lg` top out around 4–5px) — since `.rounded-3`/`.rounded-4` are free
  class names in BS4 (no collision), define them as bridge utilities instead of inline
  `style="border-radius: 12px"` scattered per card:
  ```css
  .rounded-3 { border-radius: 8px !important; }
  .rounded-4 { border-radius: 12px !important; }
  ```
  (12px, not BS5's literal 16px — matches this project's existing card radius, already implemented
  this way in `partials/theme-styles.blade.php`. Don't "correct" it to 16px, that would be a real
  visual change to every card, not a neutral refactor.)
  Then: `<div class="card border-0 shadow-sm rounded-4 overflow-hidden">`.
- `.overflow-hidden` **does not exist** in BS4 either (added in BS5) — add it as a bridge utility
  too instead of `style="overflow: hidden"`:
  ```css
  .overflow-hidden { overflow: hidden !important; }
  ```
- Borders: prefer the project's existing subtle border color (`#e2e8f0`) over BS4's default
  `.border` (`#dee2e6`, harsher). Bridge class: `.border-subtle { border-color: #e2e8f0 !important; }`.

### 3. Typography & Hierarchy
- `fw-medium`/`fw-semibold` don't exist in BS4 (only `font-weight-light/normal/bold/bolder`).
  `partials/theme-styles.blade.php` already defines `.fw-500`/`.fw-600` for this — reuse those names
  project-wide, don't introduce a second naming scheme.
- Muted text: use `.text-muted` (BS4's real class). `text-body-secondary` is just BS5's rename of
  the same concept — don't use it, it resolves to nothing here.
- `.lead` works unchanged in BS4 — fine to use as-is for section intros, paired with `.text-muted`.

### 4. Component Patterns
- **Buttons:** don't reach for stock `.btn-primary`/`.btn-secondary`. Use the project's existing
  soft-button family (`.btn-action`, `.btn-action-primary`, `.btn-action-secondary`,
  `.btn-action-success`, `.btn-action-warning`, `.btn-action-danger`, `.btn-action-solid` for the one
  saturated/primary CTA per view) — this **is** the BS4 equivalent of BS5's
  `bg-primary-subtle text-primary border-0` pattern, defined once in `partials/theme-styles.blade.php`
  and already in production use across `part-numbers` and `work-centers`. Exactly one `.btn-action-solid`
  per view (the primary submit/save action) — everything else is soft or `.btn-action-secondary`
  (ghost/outline, for dismissive actions like "Cancelar"/"Volver"/"Limpiar"). Don't flatten this
  3-tier hierarchy to a single weight; it's what tells the user which action matters most.
- **Tables:** wrap in `card border-0 shadow-sm rounded-4 overflow-hidden` (using the bridge classes
  above), table itself as `table table-hover mb-0` — `align-middle` works unchanged in BS4, or keep
  using the project's `.td-cell`/`.th-cell`/`.table-head-row` classes which already bundle
  vertical-align + padding + the muted uppercase header treatment. Always give every `<th>` a
  `scope="col"`.
- **Forms:** BS5's `form-floating` has no BS4 equivalent (the BS4 floating-label recipe needs
  different markup and isn't a drop-in). Don't use it here. Keep the project's existing pattern —
  `.field-label` above a `.field-input`/`form-control` — it's simpler and already accessible
  (label associated via `for`/`id`). `form-control-lg` does work unchanged in BS4 if a taller input
  is wanted.
- **Badges:** stock `.badge-pill` (BS4's real class, not `rounded-pill` alone) plus the project's
  `.badge-soft.badge-{primary,success,danger,warning,secondary}` gives the BS5
  `bg-primary-subtle text-primary rounded-pill` look: `<span class="badge-soft badge-primary rounded-pill">`
  (`.rounded-pill` *does* exist in BS4, `border-radius: 50rem`, safe to combine).

### 5. Color & Theme System
- Can't override `--bs-primary` etc. (see stack reality check). Instead, formalize the palette this
  project already uses ad hoc across `.btn-action*`/`.badge-soft*` as CSS custom properties once in
  the shared partial, and have new bridge classes reference `var(...)` instead of repeating hex:
  ```css
  :root {
      --surface: #f8fafc;
      --surface-hover: #f1f5f9;
      --border-subtle: #e2e8f0;
      --text-muted: #64748b;
      --text-faint: #94a3b8;
      --text-body: #334155;
      --accent: #1d4ed8;
      --accent-soft-bg: #eff6ff;
      --accent-soft-hover: #dbeafe;
  }
  ```
- Dark mode: out of scope by default here — see stack reality check. If a task explicitly asks for
  dark mode on an AdminLTE view, that requires wiring `layout_dark_mode` / a class toggle first; treat
  it as a separate task rather than adding `data-bs-theme` blocks that do nothing.

### 6. Copy & Microcopy

Adapted from Anthropic's `frontend-design` skill — words are interface material, not decoration,
even in an internal CRUD panel. This app's UI is in Spanish; apply these in Spanish, matching the
tone already used in `part-numbers`.

- **Same action name end-to-end.** The verb on the button must match the verb in the confirmation
  and in the result. Existing good example: `edit.blade.php`'s delete flow — button says "Eliminar",
  SweetAlert2 confirms "Sí, eliminar" — keep this pairing when adding new destructive actions rather
  than mixing "Eliminar" / "Borrar" / "Quitar" across a flow.
- **Active voice, plain verbs, sentence case.** "Guardar Cambios", "Volver", "Agregar Imagen" are the
  right register — not "Enviar Formulario" or ALL-CAPS labels.
- **Errors state what happened and how to fix it, without apologizing.** Don't write "Lo sentimos,
  ocurrió un error" — say what's wrong (`session('error')` messages should name the actual problem,
  e.g. validation feedback already does this via `@error('production_order')`).
- **Empty states are an invitation to act, not an apology.** Existing good example:
  `index.blade.php`'s "No hay números de parte registrados" + "Intenta ajustar la búsqueda" — keep
  this pattern (state + next step) instead of a bare "Sin datos."

### 7. Quality floor: keyboard focus & motion

- **Never ship `outline: none` without a visible replacement.** `.field-input`/`.filter-input`
  already do this correctly — `outline: none` paired with a `:focus` box-shadow ring
  (`box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.2)`). Copy this pattern for any new focusable custom
  element; never remove the outline and leave nothing in its place.
- **Respect `prefers-reduced-motion` for anything beyond a trivial hover transition.** The existing
  `transition: background 0.1s ease` / `transition: all 0.15s ease` on hovers are trivial and fine
  as-is. If a future view adds anything more elaborate (transforms, entrance animations,
  auto-playing effects), wrap it:
  ```css
  @media (prefers-reduced-motion: no-preference) {
      /* elaborate animation here */
  }
  ```

### 8. CSS specificity gotcha

`partials/theme-styles.blade.php` leans on `!important` for `.th-cell`/`.td-cell`/etc. specifically to
win over AdminLTE/BS4's own table styles — that's intentional here, not sloppiness. But it means
**two `!important` rules targeting the same property on the same element no longer resolve by
specificity — the one that loads later in the compiled `<head>` wins, regardless of which class
"looks" more specific.** Since `@include('partials.theme-styles')` always loads before any
view-specific `<style>` block, a page-specific block would win ties — don't add a second `!important`
on `padding`/`vertical-align`/etc. to a partial class from within a page-specific block expecting it
to lose "because the partial is more general"; it won't. If a single view genuinely needs a different
value for a shared class, use a distinct class name instead of re-declaring the shared one with
another `!important`. In practice this should rarely come up — after the `part-numbers` migration,
neither `part-numbers` nor `work-centers` have any page-specific `<style>` block left at all; that's
the target state for every resource on this partial.

## Checklist when creating/auditing an AdminLTE view under this skill
- [ ] No stock `.btn-primary`/`.badge-primary`/etc. — using `.btn-action*` / `.badge-soft*` family.
- [ ] No inline `style="border-radius:...` / `overflow: hidden` / `gap: ...` — using bridge utility
      classes (`.rounded-3`/`.rounded-4`/`.overflow-hidden`/`.gap-*`), added once to a shared partial.
- [ ] Every `<th>` has `scope="col"`.
- [ ] Icon-only buttons/links (icon visible, text hidden below a breakpoint) have `aria-label`.
- [ ] No `data-bs-*`, `rounded-4`/`rounded-3` (raw, undefined), `bg-*-subtle`, `form-floating`, or
      `text-body-secondary` — none of these exist in this project's loaded CSS.
- [ ] Uses `@include('partials.theme-styles'/'theme-alerts'/'theme-scripts')` — no per-resource copy,
      no page-specific `<style>` block unless something is genuinely unique to that one view.
- [ ] Same action verb used on the trigger, its confirmation, and its result message; errors state
      the problem plainly (no apology-only text); empty states pair the state with a next step.
- [ ] No `outline: none` without a visible `:focus` replacement; any animation beyond a trivial hover
      transition is wrapped in `@media (prefers-reduced-motion: no-preference)`.
- [ ] No new `!important` re-declaration of a shared partial class's property from a page-specific
      `<style>` block — use a distinct class name instead.
