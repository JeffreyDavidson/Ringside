<form wire:submit="deleteSelected" {{ $attributes }} >
    <div class="fw-bold me-5">
        <span class="me-2" x-text="$wire.selectedWrestlerIds.length"></span> Selected
    </div>

    <button type="submit" class="btn btn-danger">
        <x-icon.spinner wire:loading wire:target="deleteSelected" class="text-gray-700"/>

        Delete Selected
    </button>
</form>
