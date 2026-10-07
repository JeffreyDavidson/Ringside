<x-tables.entity-actions
    :model="$event"
    :name="$event->name"
    menu-label="Event actions"
    :show-url="route('events.show', $event)"
    form-modal="events.modals.form-modal"
    gate-view
/>
