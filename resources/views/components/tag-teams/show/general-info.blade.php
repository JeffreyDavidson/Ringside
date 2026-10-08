<x-card.general-info>
    <x-card.general-info.stat :label="__('core.general_info.status')">
        {{ $tagTeam->status->label() }}
        <x-availability-badges
            class="ms-2"
            :injured="$tagTeam->hasInjuredMember()"
            :suspended="$tagTeam->isSuspended() || $tagTeam->hasSuspendedMember()"
        />
    </x-card.general-info.stat>
    <x-card.general-info.links :label="__('core.general_info.current_tag_team_partners')">
        @forelse ($tagTeam->currentWrestlers as $wrestler)
            <x-route-link :route="route('wrestlers.show', $wrestler)" :label="$wrestler->name" />
            @if ($loop->count === 1)
                and TBD
            @endif
            @if (! $loop->last)
                and
            @endif
        @empty
            No Current Wrestlers Assigned
        @endforelse
    </x-card.general-info.links>

    @if ($tagTeam->currentManagers->isNotEmpty())
        <x-card.general-info.link-list :label="__('core.general_info.current_managers')">
            @foreach ($tagTeam->currentManagers as $manager)
                <x-card.general-info.link-item>
                    <x-route-link :route="route('managers.show', $manager)" :label="$manager->full_name" />
                </x-card.general-info.link-item>
            @endforeach
        </x-card.general-info.link-list>
    @endif

    @if ($tagTeam->currentStable)
        <x-card.general-info.links :label="__('core.general_info.current_stable')">
            <x-route-link
                :route="route('stables.show', $tagTeam->currentStable)"
                :label="$tagTeam->currentStable->name"
            />
        </x-card.general-info.links>
    @endif

    @if ($tagTeam->currentChampionships->isNotEmpty())
        <x-card.general-info.link-list :label="__('core.general_info.current_title_championships')">
            @foreach ($tagTeam->currentChampionships as $currentChampionship)
                <x-card.general-info.link-item>
                    <x-route-link
                        :route="route('titles.show', $currentChampionship->title)"
                        :label="$currentChampionship->title->name"
                    />
                </x-card.general-info.link-item>
            @endforeach
        </x-card.general-info.link-list>
    @endif

    @if ($tagTeam->signature_move)
        <x-card.general-info.stat :label="__('core.general_info.signature_move')" :value="$tagTeam->signature_move" />
    @endif
</x-card.general-info>
