@props(['title'])

<x-card.general-info>
    <x-card.general-info.stat label="Type" :value="$title->type->label()" />
    <x-card.general-info.stat label="Status" :value="$title->status->label()" />
    <x-card.general-info.links label="Current Champion">
        @if ($title->currentChampionship)
            <x-route-link
                :route="$title->currentChampionship->champion instanceof \App\Models\Roster\Wrestlers\Wrestler
                    ? route('wrestlers.show', $title->currentChampionship->champion)
                    : route('tag-teams.show', $title->currentChampionship->champion)"
                :label="$title->currentChampionship->champion->name"
            />
        @else
            Vacant
        @endif
    </x-card.general-info.links>
    <x-card.general-info.stat
        label="Date Introduced"
        :value="$title->firstActivityPeriod?->started_at->toDateString() ?? 'No Start Date Set'"
    />
</x-card.general-info>
