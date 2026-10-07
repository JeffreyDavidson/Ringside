<x-tables.entity-actions
    :model="$referee"
    :name="$referee->full_name"
    menu-label="Referee actions"
    :show-url="route('referees.show', $referee)"
    form-modal="referees.modals.form-modal"
/>
