<details class="border-ringside-line border-y py-4">
    <summary class="min-h-11 cursor-pointer py-3 font-semibold">{{ __('storylines.new_story') }}</summary>
    <form wire:submit="save" class="mt-4 grid max-w-2xl gap-5">
        <x-form.inputs.select
            wire:model="promotionId"
            :label="__('storylines.promotion')"
            size="lg"
            :options="$promotions->pluck('name', 'id')->all()"
            :placeholder="__('storylines.promotion')"
            class="min-h-11"
        />
        <x-form.inputs.text
            wire:model="title"
            :label="__('storylines.name')"
            size="lg"
            class="min-h-11"
            required
            maxlength="160"
        />
        <x-form.inputs.textarea
            wire:model="premise"
            :label="__('storylines.premise')"
            size="lg"
            class="min-h-11"
            required
            maxlength="4000"
        />
        <x-form.inputs.textarea
            wire:model="payoff"
            :label="__('storylines.payoff')"
            size="lg"
            class="min-h-11"
            maxlength="4000"
        />
        <x-button
            type="submit"
            wire:loading.attr="disabled"
            wire:offline.attr="disabled"
            class="justify-self-start"
        >{{ __('storylines.create') }}</x-button>
    </form>
</details>
