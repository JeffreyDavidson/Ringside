<x-tables.entity-actions
    :model="$wrestler"
    :name="$wrestler->name"
    :menu-label="__('wrestlers.actions.menu_label')"
    :show-url="route('wrestlers.show', $wrestler)"
    form-modal="wrestlers.modals.form-modal"
/>
