---
paths:
  - 'app/View/**'
---

# View Composers and Forms

## Composers share read-only context with named views
A composer in `app/View/Composers` supplies data that a layout or partial needs on every render (`PromotionSwitcherComposer`). Register it in `AppServiceProvider::boot()` with `View::composer()` against explicit view names, never a wildcard. It reads shared context through existing services and sets view data; it never writes, authorizes or loads a page's own data (that is a ViewModel, see view-models.md). Give it safe empty values for a guest, as the existing composer does.

## Form field context is a pure helper for Blade components
`app/View/Forms/FormFieldContext` is a `final readonly` value built with `from()` by the `input`, `select`, `textarea` and roster-combobox components. It works out one field's name, id, error state and `aria-describedby` from the component attributes and error bag. Keep it free of queries, models and validation, and let it reject a non-string name or id with `InvalidArgumentException` because that is a caller bug.

## Test them through the rendered view
Test a composer in `tests/Integration/View/Composers` with and without a signed-in user, and the form context through the components that render it (`tests/Integration/View/Components`). Assert the rendered attributes and data, not private structure.
