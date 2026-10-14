@if ($this->opponent !== '')
    <div class="border-outline flex flex-wrap items-center justify-between gap-3 border-b pb-3">
        <p class="text-foreground text-sm">{{ __('matches.history.against', ['name' => $this->opponentLabel]) }}</p>
        <x-buttons.light wire:click="clearOpponent">{{ __('matches.history.clear_opponent') }}</x-buttons.light>
    </div>
@endif
