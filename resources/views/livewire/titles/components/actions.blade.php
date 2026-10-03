@use('App\Enums\Titles\TitleLifecycleTransition')

<div class="flex flex-wrap gap-2">
    @if ($this->canPerform(TitleLifecycleTransition::Debut))
        <x-buttons.success wire:click="debut" wire:loading.attr="disabled" wire:target="debut">
            {{ __('core.lifecycle_actions.debut') }}
        </x-buttons.success>
    @endif

    @if ($this->canPerform(TitleLifecycleTransition::Retire))
        <x-buttons.danger
            wire:click="retire"
            :wire:confirm="__('core.lifecycle_confirmations.retire', ['name' => $title->name])"
            wire:loading.attr="disabled"
            wire:target="retire"
        >
            {{ __('core.lifecycle_actions.retire') }}
        </x-buttons.danger>
    @endif

    @if ($this->canPerform(TitleLifecycleTransition::Unretire))
        <x-buttons.success wire:click="unretire" wire:loading.attr="disabled" wire:target="unretire">
            {{ __('core.lifecycle_actions.unretire') }}
        </x-buttons.success>
    @endif

    @if ($this->canPerform(TitleLifecycleTransition::Pull))
        <x-buttons.warning
            wire:click="deactivate"
            :wire:confirm="__('core.lifecycle_confirmations.deactivate', ['name' => $title->name])"
            wire:loading.attr="disabled"
            wire:target="deactivate"
        >
            {{ __('core.lifecycle_actions.deactivate') }}
        </x-buttons.warning>
    @endif

    @if ($this->canPerform(TitleLifecycleTransition::Reinstate))
        <x-buttons.success wire:click="reinstate" wire:loading.attr="disabled" wire:target="reinstate">
            {{ __('core.lifecycle_actions.reinstate') }}
        </x-buttons.success>
    @endif

    @if ($this->canRestore())
        <x-buttons.success wire:click="restore" wire:loading.attr="disabled" wire:target="restore">
            {{ __('core.lifecycle_actions.restore') }}
        </x-buttons.success>
    @endif
</div>
