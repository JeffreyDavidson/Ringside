<x-tables.entity-actions
    :model="$wrestler"
    :name="$wrestler->name"
    menu-label="Wrestler actions"
    :show-url="route('wrestlers.show', $wrestler)"
    form-modal="wrestlers.modals.form-modal"
/>
