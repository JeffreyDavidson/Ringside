<x-tables.entity-actions
    :model="$tagTeam"
    :name="$tagTeam->name"
    :menu-label="__('tag-teams.actions.menu_label')"
    :show-url="route('tag-teams.show', $tagTeam)"
    form-modal="tag-teams.modals.form-modal"
/>
