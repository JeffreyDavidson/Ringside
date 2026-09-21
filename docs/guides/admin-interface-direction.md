# Admin Interface Direction

Status: preserved design brief from April 2026. This captures product intent,
not a claim that the component structure or page rebuild is complete.

## Visual identity

The intended interface has a dark sidebar and header around a light content
area. The historical brief called for a custom Ringside identity without a
third-party admin template.

| Element | Direction |
| --- | --- |
| Primary accent | Ringside red, `#e62222` |
| Secondary accent | Gold, `#d4a843` |
| Shell | Near-black, `#0a0a0a`, with off-white text |
| Content | White surfaces with zinc text, borders, and muted backgrounds |
| Typography | Oswald for the sidebar brand; Inter for other text |
| Icons | Heroicons |
| Status colors | Green for success, amber for caution, red for danger, blue for information |

The brief favored semantic color tokens introduced as components need them,
utility classes within Blade components, and existing Livewire/Alpine behavior.
Full dark mode was deferred.

## Component and page goals

The proposed reusable inventory includes buttons, badges, cards, modals,
dropdowns, tabs, tables, statistics, tooltips, page headings, and form controls
with labels, validation messages, and errors. Components should support
attributes and composition through props and slots.

The historical `ui/` namespace and directory-based `index.blade.php` layout are
proposals to reconcile with the current component library before implementation.
Reuse current components and follow current project rules when choosing names.

The proposed page sequence was:

1. Responsive shell and navigation, including a mobile sidebar drawer.
2. Login, registration, and password recovery.
3. Dashboard with meaningful roster, event, and championship statistics.
4. Complete wrestler list, detail, form, and action flows as a reference.
5. Remaining roster, stable, title, venue, event, match, and user pages.

The intended scope is presentation, preserving existing backend behavior.
Inspect the live interface and its component APIs before defining any rebuild.
The old blanket instruction to delete all views is retired; this brief does not
authorize that action.

Mobile apps/PWAs and an extensive animation system were outside the initial
scope. See the [product roadmap](../product-roadmap.md) for the original source
revision and planning process.
