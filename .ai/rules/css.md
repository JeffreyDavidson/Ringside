---
paths:
  - 'resources/css/**'
---

# Css

## Use shared semantic Tailwind theme tokens
Keep brand colors, typography, and shared shape values in resources/css/tokens.css using Tailwind v4 @theme tokens. Import that file from each stylesheet entry point and reference semantic Ringside tokens instead of adding one-off brand hex values.

Use purpose-based names without an application prefix: `bg-surface`, `text-foreground`, `text-muted-foreground`, `border-border`, and `bg-primary`. Keep primary hover/pressed, error, warning and success roles distinct. Table status dots use `status-positive`/`status-caution`; footer text uses `footer-muted`/`footer-foreground`. These preserve their distinct existing colors. `ink` serves print and white-background text; leave Tailwind default white and black unchanged. See DESIGN.md for the complete token reference.

Prefer native numeric utilities such as `h-8.5` and `px-3`, custom-property shorthand such as `duration-(--sidebar-transition-duration)`, and opacity modifiers such as `border-signal/50`. Use `--alpha()` when direct CSS needs transparent theme colors; retain the marketing neutral alpha literals to preserve their sRGB rendering. Add theme overrides only for values the app actually uses; keep ordinary layout variables outside `@theme`. Retain custom CSS where it expresses the sidebar, checkbox or print behavior clearly.
