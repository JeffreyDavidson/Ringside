@props(['form', 'labels', 'kind'])
<div class="grid gap-4 md:grid-cols-2">
    <x-form.inputs.text wire:model="form.name" :label="__('tournaments.name')" class="min-h-11" />
    <x-form.inputs.select
        wire:model.live="form.kind"
        :label="__('tournaments.entrant_type')"
        :options="['wrestler' => __('tournaments.kind.wrestler'), 'tag_team' => __('tournaments.kind.tag_team')]"
        class="min-h-11"
    />
    <x-form.inputs.select
        wire:model="form.capacity"
        :label="__('tournaments.capacity')"
        :options="[4 => 4, 8 => 8, 16 => 16]"
        class="min-h-11"
    />
    <label class="flex min-h-11 items-center gap-3"
        ><input type="checkbox" wire:model="form.repeatAppearances" class="size-5" />
        <span>{{ __('tournaments.repeat') }}</span></label>
    <div class="md:col-span-2">
        <x-form.inputs.textarea wire:model="form.description" :label="__('tournaments.description')" />
    </div>
</div>
<p class="text-muted-foreground text-sm">{{ __('tournaments.repeat_hint') }}</p>
<x-form.inputs.roster-combobox
    wire:model="form.entrants"
    :label="__('tournaments.entrants')"
    :kind="$kind"
    :labels="$labels"
    :multiple="true"
/>
<p class="text-muted-foreground text-sm">{{ __('tournaments.seed_hint') }}</p>
<ol class="divide-border divide-y">
    @foreach ($form->entrants as $index => $id)
        @php($name = collect($labels)->firstWhere('id',$id)['name'] ?? __('tournaments.selected_entry'))
        <li wire:key="seed-{{ $id }}" class="flex min-h-14 items-center gap-3 py-2">
            <span class="text-muted-foreground w-8 shrink-0 tabular-nums">{{ $index+1 }}</span
            ><span class="min-w-0 grow break-words">{{ $name }}</span>
            <x-button
                variant="secondary"
                wire:click="move({{ $index }},-1)"
                :disabled="$index === 0"
                :aria-label="__('tournaments.move_up', ['name' => $name])"
                ><x-heroicon-o-arrow-up class="size-5"
            /></x-button>
            <x-button
                variant="secondary"
                wire:click="move({{ $index }},1)"
                :disabled="$index === count($form->entrants) - 1"
                :aria-label="__('tournaments.move_down', ['name' => $name])"
                ><x-heroicon-o-arrow-down class="size-5"
            /></x-button>
        </li>
    @endforeach
</ol>
