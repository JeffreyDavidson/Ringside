<x-card.general-info>
    @if ($stable->currentWrestlers->isNotEmpty())
        <x-card.general-info.links label="Current Wrestler(s)">
            @foreach ($stable->currentWrestlers as $wrestler)
                <x-route-link :route="route('wrestlers.show', $wrestler)" label="{{ $wrestler->name }}" />

                @if (!$loop->last)
                    @php echo "<br>" @endphp
                @endif
            @endforeach
        </x-card.general-info.links>
    @endif
    @if ($stable->currentTagTeams->isNotEmpty())
        <x-card.general-info.links label="Current Tag Team(s)">
            @foreach ($stable->currentTagTeams as $tagTeam)
                <x-route-link :route="route('tag-teams.show', $tagTeam)" label="{{ $tagTeam->name }}" />

                @if (!$loop->last)
                    @php echo "<br>" @endphp
                @endif
            @endforeach
        </x-card.general-info.links>
    @endif
    <x-card.general-info.stat label="Start Date" :value="$stable->firstActivation?->started_at->toDateString() ?? 'No Start Date Set'" />
</x-card.general-info>
