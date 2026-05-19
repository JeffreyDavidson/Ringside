# Technical Specification

> Design System
> Reference: @.agent-os/specs/2025-08-28-design-system/spec.md
> Updated: 2026-04-04

## Component Architecture

### Anonymous Blade Component Structure

**Directory Organization**:
```
resources/views/components/
├── ui/                          # Design system (domain-agnostic)
│   ├── button/
│   │   └── index.blade.php      # <x-ui.button>
│   ├── badge/
│   │   └── index.blade.php      # <x-ui.badge>
│   ├── card/
│   │   ├── index.blade.php      # <x-ui.card>
│   │   ├── header.blade.php
│   │   ├── body.blade.php
│   │   └── footer.blade.php
│   ├── modal/
│   │   ├── index.blade.php
│   │   ├── header.blade.php
│   │   ├── body.blade.php
│   │   └── footer.blade.php
│   ├── form/
│   │   ├── input.blade.php
│   │   ├── select.blade.php
│   │   ├── textarea.blade.php
│   │   ├── checkbox.blade.php
│   │   ├── label.blade.php
│   │   └── error.blade.php
│   ├── dropdown/
│   │   └── index.blade.php
│   ├── tabs/
│   │   └── index.blade.php
│   ├── table/
│   │   └── index.blade.php
│   ├── page/
│   │   ├── header.blade.php
│   │   ├── heading.blade.php
│   │   └── description.blade.php
│   ├── stats/
│   │   └── index.blade.php
│   ├── tooltip/
│   │   └── index.blade.php
│   └── route-link/
│       └── index.blade.php
├── layouts/
├── sidebar/
├── topbar/
└── {entity}/                    # Domain components
```

**Convention**: Every component is a directory with `index.blade.php`, even standalone components. This provides consistency and room for future sub-components.

### Component Patterns

**Attribute Forwarding**: All components forward additional attributes to the root element:
```blade
<div {{ $attributes->merge(['class' => 'base-classes']) }}>
    {{ $slot }}
</div>
```

**Slot-Based Composition**: Named slots for structured content:
```blade
<x-ui.card>
    <x-ui.card.header>
        <x-ui.card.title>Title</x-ui.card.title>
    </x-ui.card.header>
    <x-ui.card.body>
        Content here
    </x-ui.card.body>
</x-ui.card>
```

**Prop-Based Configuration**: Props with sensible defaults:
```blade
<x-ui.button variant="primary" size="sm">
    Save
</x-ui.button>
```

## Technical Requirements

### Stack Integration

**Tailwind CSS 4**: All styling via Tailwind utility classes directly in components. No CSS utility class layer (no `btn-primary-default` type classes). CSS file contains only:
- `@import "tailwindcss"`
- `@theme` block for custom tokens
- `:root` block for semantic CSS variables
- Layout variables (sidebar width, header height)

**Livewire 3**: Components work seamlessly with `wire:model`, `wire:click`, and other Livewire directives via attribute forwarding. Existing Livewire PHP classes are kept as-is — views rebuilt to match their API.

**Alpine.js**: Interactive behavior (sidebar toggle, dropdowns, modals) uses Alpine.js, included with Livewire. Follow existing patterns like `x-data`, `x-show`, `x-transition`.

**Heroicons**: All icons via `blade-ui-kit/blade-heroicons`:
```blade
<x-heroicon-o-user-group class="size-5" />
```

### CSS Architecture

```css
@import "tailwindcss";

@theme {
  /* Only truly custom values that Tailwind doesn't provide */
  --text-2sm: 0.8125rem;
  --text-2xs: 0.6875rem;
}

:root {
  /* Semantic tokens — shell */
  --shell-bg: #0a0a0a;
  --shell-text: #f5f5f5;
  --shell-border: rgba(230, 34, 34, 0.1);

  /* Semantic tokens — content */
  --background: var(--color-white);
  --foreground: var(--color-zinc-950);
  /* ... etc */

  /* Brand */
  --primary: #e62222;
  --accent-brand: #d4a843;

  /* Layout */
  --sidebar-width: 280px;
  --sidebar-collapsed-width: 80px;
  --header-height: 70px;
}

/* No @layer components {} — styling lives in Blade components */
```

### Component Behavior Standards

**Responsive Design**: Mobile-first. Sidebar hidden below `lg` breakpoint with mobile drawer. Content stacks to single column.

**Accessibility**: ARIA attributes, keyboard navigation, focus management. Interactive components need `tabindex`, `role`, `aria-expanded`, etc.

**Performance**: Minimal DOM. No unnecessary wrapper divs. Leverage Tailwind's utility-first approach.

### Rebuild Strategy

**Preserve Livewire API Surface**: The existing 95 Livewire PHP classes define what properties and methods the views can use. When rebuilding a view:

1. Read the Livewire PHP class
2. Identify its public properties, computed properties, and methods
3. Build the view to use those exact bindings

**No PHP refactoring during view rebuild.** If a Livewire class has an awkward API, note it for future refactoring but build the view to match the current API.

### Testing Requirements

**Component Testing**: Blade component rendering tests using Pest.

**Integration Testing**: Verify Livewire components render correctly with new views.

**Accessibility Testing**: Automated checks for ARIA attributes and keyboard navigation.

Tests are written as we build, not as a separate phase.
