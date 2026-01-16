# Component Inventory

> Design System
> Reference: @.agent-os/specs/2025-08-28-design-system/spec.md

> Created: 2025-08-28
> Version: 1.0.0

## Layout Components (`layout/`)

### Header Component (`header.blade.php`)
**Purpose**: Top navigation bar with logo, search, and user actions
**Props**: `title`, `showSearch`, `userMenu`
**Slots**: `logo`, `search`, `actions`, `userMenu`
**Metronic Elements**: App header, navbar, search toggle, user dropdown
**Usage Examples**: Application branding, entity search functionality, user role and profile display

### Sidebar Component (`sidebar.blade.php`)
**Purpose**: Main navigation sidebar with menu items and toggle functionality
**Props**: `collapsed`, `variant` (light/dark), `sticky`
**Slots**: `header`, `menu`, `footer`
**Metronic Elements**: Sidebar navigation, menu accordion, theme toggle
**Usage Examples**: Application navigation menus, collapsible sections, theme switching, navigation state management

### Main Content Wrapper (`main.blade.php`)
**Purpose**: Content area wrapper with responsive margins and padding
**Props**: `breadcrumbs`, `title`, `subtitle`
**Slots**: `breadcrumbs`, `header`, `toolbar`, `content`
**Metronic Elements**: Main content container, page header, breadcrumb navigation
**Usage Examples**: Application page headers, dynamic breadcrumbs, content area management

### Grid System (`grid.blade.php`)
**Purpose**: Responsive grid layouts following Metronic's design patterns
**Props**: `columns`, `gap`, `responsive`
**Slots**: Multiple numbered slots for grid items
**Metronic Elements**: CSS Grid and Flexbox layouts, responsive breakpoints
**Usage Examples**: Entity profile grids, card layouts, responsive content organization

## Form Components (`form/`)

### Input Component (`input.blade.php`)
**Purpose**: Text input fields with validation and styling
**Props**: `type`, `label`, `placeholder`, `required`, `error`, `helpText`
**Slots**: `prepend`, `append`, `help`
**Metronic Elements**: Styled input fields, floating labels, validation states
**Usage Examples**: Entity name inputs, numeric fields, text entry with validation

### Select Component (`select.blade.php`)
**Purpose**: Dropdown select fields with custom styling
**Props**: `options`, `label`, `placeholder`, `multiple`, `searchable`
**Slots**: `label`, `option`, `help`
**Metronic Elements**: Custom select styling, dropdown animations
**Usage Examples**: Status selection, category selection, entity selection from lists

### Textarea Component (`textarea.blade.php`)
**Purpose**: Multi-line text input with auto-resize capability
**Props**: `label`, `rows`, `autoResize`, `maxLength`, `placeholder`
**Slots**: `label`, `help`
**Metronic Elements**: Styled textarea with character counter
**Usage Examples**: Event descriptions, long-form content, notes and commentary fields

### Checkbox Component (`checkbox.blade.php`)
**Purpose**: Checkbox inputs with custom styling and labels
**Props**: `label`, `checked`, `disabled`, `variant`
**Slots**: `label`, `description`
**Metronic Elements**: Custom checkbox styling, indeterminate state
**Usage Examples**: Feature toggles, availability indicators, boolean status display

### Radio Component (`radio.blade.php`)
**Purpose**: Radio button groups with custom styling
**Props**: `name`, `options`, `value`, `inline`
**Slots**: `label`, `option`
**Metronic Elements**: Custom radio styling, group layouts
**Usage Examples**: Decision types, category selection, classification groups

### Validation Error (`validation-error.blade.php`)
**Purpose**: Display validation errors with consistent styling
**Props**: `field`, `errors`, `showIcon`
**Slots**: `message`
**Metronic Elements**: Error message styling, warning icons
**Usage Examples**: Business rule validation errors, conflict messages, field-specific error display

## Data Display Components (`display/`)

### Card Component (`card.blade.php`)
**Purpose**: Content containers with headers, bodies, and actions
**Props**: `title`, `subtitle`, `shadow`, `border`, `padding`
**Slots**: `header`, `media`, `body`, `footer`, `actions`
**Metronic Elements**: Card shadows, borders, responsive layouts
**Usage Examples**: Entity profile cards, event summary cards, result displays, content containers

### Table Component (`table.blade.php`)
**Purpose**: Data tables with sorting, pagination, and responsive design
**Props**: `headers`, `rows`, `sortable`, `pagination`, `striped`
**Slots**: `header`, `row`, `actions`, `pagination`
**Metronic Elements**: Table styling, hover effects, responsive behavior
**Usage Examples**: Entity tables, historical data, record displays, sortable data presentation

### List Component (`list.blade.php`)
**Purpose**: Styled lists for navigation and data display
**Props**: `variant` (simple/detailed), `dividers`, `hover`
**Slots**: `item`, `actions`
**Metronic Elements**: List item styling, separators, interactive states
**Usage Examples**: Activity listings, entity hierarchies, membership displays, categorized content

### Badge Component (`badge.blade.php`)
**Purpose**: Status and category indicators
**Props**: `variant`, `size`, `closable`, `icon`
**Slots**: `icon`, `content`
**Metronic Elements**: Badge colors, sizes, rounded corners
**Usage Examples**: Status badges, achievement indicators, category labels, classification tags

### Statistics Display (`stats.blade.php`)
**Purpose**: Numerical data presentation with icons and trends
**Props**: `value`, `label`, `icon`, `trend`, `change`
**Slots**: `icon`, `value`, `label`, `trend`
**Metronic Elements**: Statistics card layouts, trend indicators
**Usage Examples**: Performance metrics, duration tracking, statistical displays, trend indicators

## Interactive Components (`interactive/`)

### Button Component (`button.blade.php`)
**Purpose**: Action buttons with multiple variants and states
**Props**: `variant`, `size`, `disabled`, `loading`, `icon`, `href`
**Slots**: `icon`, `content`, `loading`
**Metronic Elements**: Button variants (primary, secondary, outline), loading states
**Usage Examples**: Entity actions, status changes, record creation, workflow triggers

### Dropdown Component (`dropdown.blade.php`)
**Purpose**: Dropdown menus and select interfaces
**Props**: `trigger`, `position`, `width`, `offset`
**Slots**: `trigger`, `content`, `item`
**Metronic Elements**: Dropdown animations, positioning, responsive behavior
**Usage Examples**: Context menus, entity selection, quick actions, workflow shortcuts

### Modal Component (`modal.blade.php`)
**Purpose**: Dialog windows for forms and confirmations
**Props**: `title`, `size`, `closable`, `backdrop`, `keyboard`
**Slots**: `header`, `body`, `footer`, `trigger`
**Metronic Elements**: Modal overlays, animations, responsive sizing
**Usage Examples**: Entity creation forms, booking dialogs, confirmation modals, data entry

### Tabs Component (`tabs.blade.php`)
**Purpose**: Tabbed interface for content organization
**Props**: `variant`, `orientation`, `defaultTab`
**Slots**: `nav`, `panel`
**Metronic Elements**: Tab styling, active states, responsive behavior
**Usage Examples**: Entity profile sections, management tabs, data organization, content switching

### Tooltip Component (`tooltip.blade.php`)
**Purpose**: Contextual help and information displays
**Props**: `content`, `position`, `trigger`, `delay`
**Slots**: `trigger`, `content`
**Metronic Elements**: Tooltip positioning, animations, responsive behavior
**Usage Examples**: Status explanations, help text, feature descriptions, contextual assistance

## Generic Components (`generic/`)

### Profile Card (`profile-card.blade.php`)
**Purpose**: Comprehensive entity profile display for any type of record
**Props**: `title`, `subtitle`, `showStats`, `showActions`, `compact`, `avatarUrl`
**Slots**: `avatar`, `header`, `stats`, `status`, `actions`, `footer`
**Metronic Elements**: Card layouts, avatar displays, status indicators, flexible content areas
**Usage Examples**: User profiles, entity records, personnel displays, contact cards

### Activity Display (`activity-display.blade.php`)
**Purpose**: Event and activity information presentation with participants and results
**Props**: `title`, `date`, `showResult`, `showParticipants`, `variant`, `status`
**Slots**: `header`, `participants`, `details`, `result`, `actions`
**Metronic Elements**: Activity card styling, participant layouts, result displays, timeline elements
**Usage Examples**: Events, meetings, activities, competitions, scheduled items

### Achievement Badge (`achievement-badge.blade.php`)
**Purpose**: Status, achievement, and hierarchical indicators
**Props**: `title`, `holder`, `duration`, `showHistory`, `variant`, `level`
**Slots**: `icon`, `title`, `holder`, `duration`, `actions`
**Metronic Elements**: Badge styling, holder displays, timeline indicators, hierarchy levels
**Usage Examples**: Awards, certifications, titles, achievements, status indicators

### Status Indicator (`status-indicator.blade.php`)
**Purpose**: Generic status visualization with contextual information
**Props**: `status`, `label`, `tooltip`, `showDates`, `variant`, `interactive`
**Slots**: `icon`, `label`, `details`, `timeline`
**Metronic Elements**: Status colors, icon indicators, tooltip integration, date displays
**Usage Examples**: Employment status, health status, availability, workflow states, progress indicators

## Implementation Priority

### Phase 1 (Core Foundation)
- Layout components (header, sidebar, main)
- Basic form components (input, select, textarea)
- Card and button components

### Phase 2 (Data Display)
- Table and list components
- Badge and stats components
- Validation error component

### Phase 3 (Interactivity)
- Modal and dropdown components
- Tabs and tooltip components
- Enhanced form components

### Phase 4 (Generic Components)
- Profile and activity display components  
- Achievement and status components
- Advanced generic component features

## Testing Coverage Requirements

Each component must include:
- **Unit Tests**: Props, slots, and rendering scenarios
- **Integration Tests**: Livewire interaction and Alpine.js behavior
- **Accessibility Tests**: ARIA attributes and keyboard navigation
- **Visual Tests**: Responsive behavior and dark mode support