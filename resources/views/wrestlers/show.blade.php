<x-layouts.app>
    <x-slot name="toolbar">
        <x-toolbar>
            <x-page-heading>View Wrestler Details</x-page-heading>
            <x-breadcrumbs.list>
                <x-breadcrumbs.item :url="route('dashboard')" label="Home" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :url="route('wrestlers.index')" label="Wrestlers" />
                <x-breadcrumbs.separator />
                <x-breadcrumbs.item :label="$wrestler->name" />
            </x-breadcrumbs.list>
        </x-toolbar>
    </x-slot>

    <x-details-card>
        <x-card>
            <x-card.body>
                <x-card.detail-link
                    collapsibleLink="kt_wrestler_view_details"
                    resource="wrestler"
                    :href="route('wrestlers.edit', $wrestler)"
                />
                <x-separator />
                <x-card.detail-container id="kt_wrestler_view_details">
                    <x-card.detail-row>
                        <x-card.detail-property label="Name" />
                        <x-card.detail-value>{{ $wrestler->name }}</x-card.detail-value>
                    </x-card.detail-row>
                    <x-card.detail-row>
                        <x-card.detail-property label="Height" />
                        <x-card.detail-value>{{ $wrestler->height }}</x-card.detail-value>
                    </x-card.detail-row>
                    <x-card.detail-row>
                        <x-card.detail-property label="Weight" />
                        <x-card.detail-value>{{ $wrestler->weight }} lbs.</x-card.detail-value>
                    </x-card.detail-row>
                    <x-card.detail-row>
                        <x-card.detail-property label="Hometown" />
                        <x-card.detail-value>{{ $wrestler->hometown }}</x-card.detail-value>
                    </x-card.detail-row>

                    @if ($wrestler->currentTagTeam)
                        <x-card.detail-row>
                            <x-card.detail-property label="Current Tag Team" />
                            <x-card.detail-value>
                                <x-route-link
                                    :route="route('tag-teams.show', $wrestler->currentTagTeam)"
                                    label="{{ $wrestler->currentTagTeam->name }}"
                                />
                            </x-card.detail-value>
                        </x-card.detail-row>
                    @endif

                    @if ($wrestler->currentStable)
                        <x-card.detail-row>
                            <x-card.detail-property label="Current Stable" />
                            <x-card.detail-value>
                                <x-route-link
                                    :route="route('stables.show', $wrestler->currentStable)"
                                    label="{{ $wrestler->currentStable->name }}"
                                />
                            </x-card.detail-value>
                        </x-card.detail-row>
                    @endif

                    @if ($wrestler->currentChampionships->isNotEmpty())
                        <x-card.detail-row>
                            <x-card.detail-property label="Current Title Championship(s)" />
                            <x-card.detail-value>
                                @foreach ($wrestler->currentChampionships as $currentChampionship)
                                    <x-route-link
                                        :route="route('titles.show', $currentChampionship->title)"
                                        label="{{ $currentChampionship->title->name }}"
                                    />

                                    @if (! $loop->last)
                                        @php echo "<br>" @endphp
                                    @endif
                                @endforeach
                            </x-card.detail-value>
                        </x-card.detail-row>
                    @endif

                    @if ($wrestler->currentManagers->isNotEmpty())
                        <x-card.detail-row>
                            <x-card.detail-property label="Current Managers" />
                            <x-card.detail-value>
                                @foreach ($wrestler->currentManagers as $manager)
                                    <x-route-link
                                        :route="route('managers.show', $manager)"
                                        label="{{ $manager->full_name }}"
                                    />

                                    @if (! $loop->last)
                                        @php echo "<br>" @endphp
                                    @endif
                                @endforeach
                            </x-card.detail-value>
                        </x-card.detail-row>
                    @endif

                    @if ($wrestler->signature_move)
                        <x-card.detail-row>
                            <x-card.detail-property label="Signature Move" />
                            <x-card.detail-value>{{ $wrestler->signature_move }}</x-card.detail-value>
                        </x-card.detail-row>
                    @endif
                    <x-card.detail-row>
                        <x-card.detail-property label="Start Date" />
                        <x-card.detail-value>
                            {{ $wrestler->startedAt?->toDateString() ?? 'No Start Date Set' }}
                        </x-card.detail-value>
                    </x-card.detail-row>
                </x-card.detail-container>

                @if ($wrestler->isUnemployed())
                    <x-notice
                        class="mt-4"
                        title="This wrestler needs your attention!"
                        description="This wrestler does not have a start date and needs to be employed."
                    />
                @endif
            </x-card.body>
        </x-card>
    </x-details-card>
</x-layouts.app>
