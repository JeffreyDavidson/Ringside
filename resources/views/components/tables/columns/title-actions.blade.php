<x-tables.entity-actions
    :model="$title"
    :name="$title->name"
    menu-label="Title actions"
    :show-url="route('titles.show', $title)"
    form-modal="titles.modals.form-modal"
    gate-view
/>
