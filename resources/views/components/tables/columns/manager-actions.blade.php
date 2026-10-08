<x-tables.entity-actions
    :model="$manager"
    :name="$manager->full_name"
    :menu-label="__('managers.actions.menu_label')"
    :show-url="route('managers.show', $manager)"
    form-modal="managers.modals.form-modal"
/>
