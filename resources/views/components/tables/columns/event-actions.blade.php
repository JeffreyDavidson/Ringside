<x-tables.entity-actions
    :model="$event"
    :name="$event->name"
    :menu-label="__('events.actions.menu_label')"
    :show-url="route('events.show', $event)"
    form-modal="events.modals.form-modal"
    gate-view
/>
