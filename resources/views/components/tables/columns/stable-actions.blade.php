<x-tables.entity-actions
    :model="$stable"
    :name="$stable->name"
    :menu-label="__('stables.actions.menu_label')"
    :show-url="route('stables.show', $stable)"
    form-modal="stables.modals.form-modal"
    gate-view
/>
