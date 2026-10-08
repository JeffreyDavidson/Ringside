<x-tables.entity-actions
    :model="$title"
    :name="$title->name"
    :menu-label="__('titles.actions.menu_label')"
    :show-url="route('titles.show', $title)"
    form-modal="titles.modals.form-modal"
    gate-view
/>
