<x-layouts.app>
    <x-slot:toolbar>
        <x-toolbar>
            <x-page-heading>View Manager Details</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('managers.index')" label="Managers" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :label="$manager->full_name" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-details-page>
        <x-details-card>
            <x-card>
                <x-card.body>
                    <x-card.detail-link
                        collapsibleLink="kt_manager_view_details"
                        resource="manager"
                        :href="route('managers.edit', $manager)"
                    />
                    <x-separator />
                    <x-card.detail-container id="kt_manager_view_details">
                        <x-card.detail-row>
                            <x-card.detail-property label="Name" />
                            <x-card.detail-value>{{ $manager->full_name }}</x-card.detail-value>
                        </x-card.detail-row>

                        @if ($manager->currentWrestlers->isNotEmpty())
                            <x-card.detail-row>
                                <x-card.detail-property label="Current Wrestler(s)" />
                                <x-card.detail-value>
                                    @foreach($manager->currentWrestlers as $wrestler)
                                        <x-route-link
                                            :route="route('wrestlers.show', $wrestler)"
                                            label="{{ $wrestler->name }}"
                                        />

                                        @if (! $loop->last)
                                            @php echo "<br>" @endphp
                                        @endif
                                    @endforeach
                                </x-card.detail-value>
                            </x-card.detail-row>
                        @endif

                        @if ($manager->currentTagTeams->isNotEmpty())
                            <x-card.detail-row>
                                <x-card.detail-property label="Current Tag Team(s)" />
                                <x-card.detail-value>
                                    @foreach($manager->currentTagTeams as $tagTeam)
                                        <x-route-link
                                            :route="route('tag-teams.show', $tagTeam)"
                                            label="{{ $tagTeam->name }}"
                                        />

                                        @if (! $loop->last)
                                            @php echo "<br>" @endphp
                                        @endif
                                    @endforeach
                                </x-card.detail-value>
                            </x-card.detail-row>
                        @endif

                        @if ($manager->currentStable)
                            <x-card.detail-row>
                                <x-card.detail-property label="Current Stable" />
                                <x-card.detail-value>
                                    <x-route-link
                                        :route="route('stables.show', $manager->currentStable)"
                                        label="{{ $manager->currentStable->name }}"
                                    />
                                </x-card.detail-value>
                            </x-card.detail-row>
                        @endif

                        <x-card.detail-row>
                            <x-card.detail-property label="Start Date" />
                            <x-card.detail-value>
                                {{ $manager->startedAt?->toDateString() ?? 'No Start Date Set' }}
                            </x-card.detail-value>
                        </x-card.detail-row>
                    </x-card.detail-container>

                    @if ($manager->isUnemployed())
                        <x-notice
                            class="mt-4"
                            title="This manager needs your attention!"
                            description="This manager does not have a start date and needs to be employed."
                        />
                    @endif
                </x-card.body>
            </x-card>
        </x-details-card>

        <x-details-data>
            @if ($manager->previousWrestlers->isNotEmpty())
                <livewire:managers.previous-wrestlers-list :manager="$manager" />
            @endif

            @if ($manager->previousTagTeams->isNotEmpty())
                <livewire:managers.previous-tag-teams-list :manager="$manager" />
            @endif
        </x-details-data>
    </x-details-page>
</x-layouts.app>
