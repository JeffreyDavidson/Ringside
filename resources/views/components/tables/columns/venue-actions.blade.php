<x-tables.entity-actions
    :model="$venue"
    :name="$venue->name"
    menu-label="Venue actions"
    :show-url="route('venues.show', $venue)"
    form-modal="venues.modals.form-modal"
    gate-view
/>
