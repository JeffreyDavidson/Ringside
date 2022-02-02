<div class="d-flex justify-content-end align-items-center ms-4">
    <div class="fw-bolder me-5">
    <span class="me-2">{{ count($selected) }}</span>Selected</div>
    <button wire:click="$set('showDeleteModal', true)" type="button" class="btn btn-danger">Delete Selected</button>
</div>
