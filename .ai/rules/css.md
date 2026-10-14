---
paths:
  - 'resources/css/**'
---

# Css

## Use shared semantic Tailwind theme tokens
Keep brand colors, typography, and shared shape values in resources/css/tokens.css using Tailwind v4 @theme tokens. Import that file from each stylesheet entry point and reference semantic Ringside tokens instead of adding one-off brand hex values.

Use purpose-based names without an application prefix: `bg-surface`, `text-foreground`, `text-muted-foreground`, `border-border`, and `bg-primary`. Keep primary hover/pressed, error, warning and success roles distinct. See DESIGN.md for the complete token reference. Retained Metronic primary/success/warning colors use explicit `legacy-*` names; do not substitute the brand values in legacy consumers. `ink` serves print and white-background text; leave Tailwind default black unchanged.
