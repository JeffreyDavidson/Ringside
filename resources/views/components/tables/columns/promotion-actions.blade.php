<div class="flex justify-end">
    @can('update', $promotion)
        <x-buttons.light
            size="sm"
            @click="$dispatch('openModal', { component: 'promotions.modals.form-modal', arguments: { modelId: {{ $promotion->id }} } })"
        >
            {{ __('promotions.edit') }}
        </x-buttons.light>
    @endcan
</div>
