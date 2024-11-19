<x-card.general-info>
    <x-card.general-info.stat label="Height" :value="$wrestler->height" />
    <x-card.general-info.stat label="Weight" :value="$wrestler->weight" />
    <x-card.general-info.stat label="Hometown" :value="$wrestler->hometown" />
    @if ($wrestler->signature_move)
        <x-card.general-info.stat label="Signature Move" :value="$wrestler->signature_move" />
    @endif
    @if ($wrestler->currentTagTeam)
        <x-card.general-info.links label="Current Tag Team">
            <x-route-link :route="route('tag-teams.show', $wrestler->currentTagTeam)" label="{{ $wrestler->currentTagTeam->name }}" />
        </x-card.general-info.links>
    @endif
    @if ($wrestler->currentManagers->isNotEmpty())
        <x-card.general-info.links label="Current Manager(s)">
            @foreach ($wrestler->currentManagers as $manager)
                <x-route-link :route="route('managers.show', $manager)" label="{{ $manager->full_name }}" />

                @if (!$loop->last)
                    @php echo "<br>" @endphp
                @endif
            @endforeach
        </x-card.general-info.links>
    @endif
    @if ($wrestler->currentStable)
        <x-card.general-info.links label="Current Stable">
            <x-route-link :route="route('stables.show', $wrestler->currentStable)" label="{{ $wrestler->currentStable->name }}" />
        </x-card.general-info.links>
    @endif
    {{-- @if ($wrestler->currentChampionships->isNotEmpty())
        <x-card.general-info.links label="Current Title Championship(s)">
            @foreach ($wrestler->currentChampionships as $currentChampionship)
                <x-route-link :route="route('titles.show', $currentChampionship->title)" label="{{ $currentChampionship->title->name }}" />

                @if (!$loop->last)
                    @php echo "<br>" @endphp
                @endif
            @endforeach
        </x-card.general-info.links>
    @endif --}}
    <x-card.general-info.stat label="Start Date" :value="$wrestler->firstEmployment?->started_at->toDateString() ?? 'No Start Date Set'" />
</x-card.general-info>
