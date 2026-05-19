@props(['selected' => []])

<x-button variant="danger" size="sm" wire:click="deleteSelected" {{ $attributes }}>
    Delete Selected ({{ count($selected) }})
</x-button>
