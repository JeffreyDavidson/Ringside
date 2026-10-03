@use('App\Enums\Stables\StableLifecycleAction')

<div class="flex flex-wrap gap-2">
    @if ($this->canPerform(StableLifecycleAction::Establish))
        <x-buttons.success wire:click="establish" wire:loading.attr="disabled" wire:target="establish">
            {{ __('core.lifecycle_actions.establish') }}
        </x-buttons.success>
    @endif

    @if ($this->canPerform(StableLifecycleAction::Disband))
        <x-buttons.danger
            wire:click="disband"
            :wire:confirm="__('core.lifecycle_confirmations.disband', ['name' => $stable->name])"
            wire:loading.attr="disabled"
            wire:target="disband"
        >
            {{ __('core.lifecycle_actions.disband') }}
        </x-buttons.danger>
    @endif

    @if ($this->canPerform(StableLifecycleAction::Retire))
        <x-buttons.warning
            wire:click="retire"
            :wire:confirm="__('core.lifecycle_confirmations.retire', ['name' => $stable->name])"
            wire:loading.attr="disabled"
            wire:target="retire"
        >
            {{ __('core.lifecycle_actions.retire') }}
        </x-buttons.warning>
    @endif

    @if ($this->canPerform(StableLifecycleAction::Unretire))
        <x-buttons.success wire:click="unretire" wire:loading.attr="disabled" wire:target="unretire">
            {{ __('core.lifecycle_actions.unretire') }}
        </x-buttons.success>
    @endif
</div>
