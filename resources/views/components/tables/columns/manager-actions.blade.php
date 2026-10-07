<x-tables.entity-actions
    :model="$manager"
    :name="$manager->full_name"
    menu-label="Manager actions"
    :show-url="route('managers.show', $manager)"
    form-modal="managers.modals.form-modal"
/>
