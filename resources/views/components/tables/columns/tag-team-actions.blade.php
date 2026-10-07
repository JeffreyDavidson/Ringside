<x-tables.entity-actions
    :model="$tagTeam"
    :name="$tagTeam->name"
    menu-label="Tag team actions"
    :show-url="route('tag-teams.show', $tagTeam)"
    form-modal="tag-teams.modals.form-modal"
/>
