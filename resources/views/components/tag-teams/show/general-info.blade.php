<x-card.general-info>
    <x-card.general-info.links label="Current Tag Team Partners">
        @forelse ($tagTeam->currentWrestlers as $wrestler)
            <x-route-link :route="route('wrestlers.show', $wrestler)" label="{{ $wrestler->name }}" />
            @if ($loop->count === 1)
                and TBD
            @endif
            @if (!$loop->last)
                and
            @endif
        @empty
            No Current Wrestlers Assigned
        @endforelse
    </x-card.general-info.links>

    @if ($tagTeam->currentManagers->isNotEmpty())
        <x-card.general-info.link-list label="Current Manager(s)">
            @foreach ($tagTeam->currentManagers as $manager)
                <x-card.general-info.link-item>
                    <x-route-link :route="route('managers.show', $manager)" label="{{ $manager->full_name }}" />
                </x-card.general-info.link-item>
            @endforeach
        </x-card.general-info.link-list>
    @endif

    @if ($tagTeam->currentStable)
        <x-card.general-info.links label="Current Stable">
            <x-route-link :route="route('stables.show', $tagTeam->currentStable)" label="{{ $tagTeam->currentStable->name }}" />
        </x-card.general-info.links>
    @endif

    {{-- @if ($tagTeam->currentChampionships->isNotEmpty())
        <x-card.general-info.link-list label="Current Title Championship(s)">
            @foreach ($tagTeam->currentChampionships as $currentChampionship)
                <x-card.general-info.link-item>
                    <x-route-link :route="route('titles.show', $currentChampionship->title)" label="{{ $currentChampionship->title->name }}" />
                </x-card.general-info.link-item>
            @endforeach
        </x-card.general-info.link-list>
    @endif --}}

    @if ($tagTeam->signature_move)
        <x-card.general-info.stat label="Signature Move" :value="$tagTeam->signature_move" />
    @endif
</x-card.general-info>
