<x-tables.entity-actions
    :model="$venue"
    :name="$venue->name"
    :menu-label="__('venues.actions.menu_label')"
    :show-url="route('venues.show', $venue)"
    form-modal="venues.modals.form-modal"
    gate-view
/>
