# Livewire Component Architecture

Ringside uses class-based Livewire components with external Blade views. Livewire
is the UI boundary; domain behavior remains in Actions and focused domain services.

## Layers

```text
Blade view
    ↓ binds input and invokes component methods
Livewire form + modal
    ↓ validates, authorizes, and builds typed data
Action
    ↓ enforces business rules and coordinates persistence
Eloquent models, Builders, and relationships
```

### Forms

Forms hold editable state, define validation, hydrate edit values, and construct
typed data objects. They do not provide generic Eloquent persistence.

### Modals

Modals own the user interaction:

- resolve or receive their create and update Actions;
- authorize protected operations;
- validate their form;
- choose the create or update path;
- call the Action with typed data; and
- dispatch UI refresh and close events after success.

### Actions

Actions are the application boundary for mutations. They own business rules,
transactions, model writes, and relationship changes. A Livewire component resolves
Actions from Laravel's container and calls `handle()`.

### Models and Builders

Models remain the Eloquent data layer. Typed custom Builders own reusable query
behavior. Livewire query components may query Eloquent directly and compose those
Builder methods.

## Directory convention

```text
app/Livewire/{Domain}/
├── Components/
├── Forms/
│   └── CreateEditForm.php
├── Modals/
│   └── FormModal.php
└── Tables/

resources/views/livewire/{domain}/
├── components/
├── modals/
└── tables/
```

Shared mechanics live under `app/Livewire/Base` or a focused concern. Domain rules
do not move into a base component merely because several screens invoke them.

## Shared base classes

### BaseForm

`BaseForm` centralizes model hydration, locked model identity, create/edit state,
modal-title display values, and validation hooks. It does not know a concrete model
class and does not persist records.

### BaseModal

`BaseModal` loads the model selected by a locked identifier, connects the model to
the form, builds modal titles, and resets or restores form state.

### BaseFormModal

`BaseFormModal` coordinates the shared submission lifecycle:

1. initialize the concrete model type while Livewire initializes the typed form;
2. mount create or edit state;
3. delegate domain submission to `storeForm()`;
4. dispatch table refresh and form-submitted events; and
5. close the modal after a successful submission.

Each domain modal implements `storeForm()` because only the domain boundary knows
which Actions and typed data belong to the operation.

## Mutation flow

```text
User submits modal
    ↓
Modal validates form
    ↓
Modal authorizes operation
    ↓
Form constructs typed Data object
    ↓
Modal calls create/update Action
    ↓
Action performs transaction and persistence
    ↓
Modal dispatches UI events
```

Validation failures remain attached to Livewire form fields. Business exceptions
are caught only at a user-interaction boundary and translated into an appropriate
message. Programmer and infrastructure failures continue through Laravel's normal
exception reporting.

## Authorization and input security

- Treat every public property as untrusted request input.
- Lock identifiers that must not change client-side.
- Call `Gate::authorize()` immediately before protected Livewire operations.
- Validate before constructing Action input.
- Never rely on a hidden input or disabled field as authorization.

## Read behavior

Use computed properties for data that should be derived on each request instead of
serializing constrained Eloquent collections into component state. Put reusable
query constraints on typed Eloquent Builders.

## Views

Keep markup in external Blade files paired with class-based components. Use
`wire:key` in rendered loops, loading states for visible async work, and named
Livewire events that express the completed UI operation.

## Testing boundaries

- Form tests cover validation, hydration, and typed-data conversion.
- Modal tests cover authorization, Action delegation, and emitted events.
- Action tests cover mutations, transactions, and business invariants.
- Browser tests cover behavior that depends on client-side Livewire interaction.

Prefer behavioral assertions over reflection tests that require incidental private
or protected methods.
