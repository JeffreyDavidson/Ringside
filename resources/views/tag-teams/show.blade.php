<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>View Tag Team Details</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('tag-teams.index')" label="Tag Teams" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :label="$tagTeam->name" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-details-card>
        <x-card>
            <x-card.body>
                <x-card.detail-link collapsibleLink="kt_tag_team_view_details" resource="tag team" :href="route('tag-teams.edit', $tagTeam)" />
                <x-separator />
                <x-card.detail-container id="kt_tag_team_view_details">
                    <x-card.detail-row property="Name" value="{{ $tagTeam->name }}" />
                    @php
                    if ($tagTeam->currentWrestlers->isNotEmpty()) {
                        if ($tagTeam->currentWrestlers->count() === 2) {
                            $names = $tagTeam->currentWrestlers->pluck('name')->join(', ', ' and ');
                        } else {
                            $names = $tagTeam->currentWrestlers->first()->name. " and TBD";
                        }
                    } else {
                        $names = "No Wrestlers Assigned";
                    }
                    @endphp
                    <x-card.detail-row property="Current Tag Team Partners" value="{{ $names }}" />

                    @if ($tagTeam->currentWrestlers->isNotEmpty())
                        <x-card.detail-row property="Combined Weight" value="{{ $tagTeam->combined_weight }} lbs." />
                    @endif

                    @if ($tagTeam->signature_move)
                        <x-card.detail-row property="Signature Move" value="{{ $tagTeam->signature_move }}" />
                    @endif

                    <x-card.detail-row property="Start Date" value="{{ $tagTeam->startedAt?->toDateString() ?? 'No Start Date Set' }}" />
                </x-card.detail-container>

                @if ($tagTeam->isUnemployed())
                    <x-notice class="mt-4" title="This tag team needs your attention!" description="This tag team does not have a start date and needs to be employed." />
                @endif
            </x-card.body>
        </x-card>
    </x-details-card>
</x-layouts.app>
