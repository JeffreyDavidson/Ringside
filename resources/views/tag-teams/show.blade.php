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

    <x-details-page>
        <x-details-card>
            <x-card>
                <x-card.body>
                    <x-card.detail-link
                        collapsibleLink="kt_tag_team_view_details"
                        resource="tag team" :href="route('tag-teams.edit', $tagTeam)"
                    />
                    <x-separator />
                    <x-card.detail-container id="kt_tag_team_view_details">
                        <x-card.detail-row property="Name" value="{{ $tagTeam->name }}">
                            <x-card.detail-property label="Name" />
                            <x-card.detail-value>{{ $tagTeam->name }}</x-card.detail-value>
                        </x-card.detail-row>
                        <x-card.detail-row>
                            <x-card.detail-property label="Current Tag Team Partners" />
                            <x-card.detail-value>
                            @forelse ($tagTeam->currentWrestlers as $wrestler)
                                <x-route-link
                                    :route="route('wrestlers.show', $wrestler)"
                                    label="{{ $wrestler->name }}"
                                />

                                @if ($loop->count === 1)
                                    and TBD
                                @endif

                                @if (! $loop->last)
                                    and
                                @endif
                            @empty
                                No Current Wrestlers Assigned
                            @endforelse
                            </x-card.detail-value>
                        </x-card.detail-row>

                        @if ($tagTeam->currentChampionship)
                            <x-card.detail-row>
                                <x-card.detail-property label="Current Title Championship" />
                                <x-card.detail-value>
                                    <x-route-link
                                        :route="route('titles.show', $tagTeam->currentChampionship->title)"
                                        label="{{ $tagTeam->currentChampionship->title->name }}"
                                    />
                                </x-card.detail-value>
                            </x-card.detail-row>
                        @endif

                        @if ($tagTeam->currentManagers->isNotEmpty())
                            <x-card.detail-row>
                                <x-card.detail-property label="Current Managers" />
                                <x-card.detail-value>
                                    @forelse ($tagTeam->currentManagers as $manager)
                                        <x-route-link
                                            :route="route('managers.show', $manager)"
                                            label="{{ $manager->full_name }}"
                                        />

                                        @if (! $loop->last)
                                            and
                                        @endif
                                    @empty
                                        No Current Wrestlers Assigned
                                    @endforelse
                                </x-card.detail-value>
                            </x-card.detail-row>
                        @endif

                        @if ($tagTeam->currentWrestlers->isNotEmpty())
                            <x-card.detail-row value="{{ $tagTeam->combined_weight }} lbs.">
                                <x-card.detail-property label="Combined Weight" />
                                <x-card.detail-value>{{ $tagTeam->combined_weight }} lbs.</x-card.detail-value>
                            </x-card.detail-row>
                        @endif

                        @if ($tagTeam->signature_move)
                            <x-card.detail-row>
                                <x-card.detail-property label="Signature Move" />
                                <x-card.detail-value>{{ $tagTeam->signature_move }}</x-card.detail-value>
                            </x-card.detail-row>
                        @endif

                        <x-card.detail-row>
                            <x-card.detail-property label="Start Date" />
                            <x-card.detail-value>
                                {{ $tagTeam->startedAt?->toDateString() ?? 'No Start Date Set' }}
                            </x-card.detail-value>
                        </x-card.detail-row>
                    </x-card.detail-container>

                    @if ($tagTeam->isUnemployed())
                        <x-notice
                            class="mt-4"
                            title="This tag team needs your attention!"
                            description="This tag team does not have a start date and needs to be employed."
                        />
                    @endif
                </x-card.body>
            </x-card>
        </x-details-card>

        <x-details-data>
            @if ($tagTeam->previousTitleChampionships->isNotEmpty())
                <livewire:tag-teams.previous-title-championships-list :tagTeam="$tagTeam" />
            @endif

            @if ($tagTeam->previousMatches->isNotEmpty())
                <livewire:tag-teams.previous-matches-list :tagTeam="$tagTeam" />
            @endif

            @if ($tagTeam->previousWrestlers->isNotEmpty())
                <livewire:tag-teams.previous-wrestlers-list :tagTeam="$tagTeam" />
            @endif

            @if ($tagTeam->previousManagers->isNotEmpty())
                <livewire:tag-teams.previous-managers-list :tagTeam="$tagTeam" />
            @endif
        </x-details-data>
    </x-details-page>
</x-layouts.app>
