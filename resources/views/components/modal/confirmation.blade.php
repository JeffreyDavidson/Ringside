<div class="swal2-container swal2-center swal2-backdrop-show" style="overflow-y: auto;">
    <div aria-labelledby="swal2-title" aria-describedby="swal2-html-container" class="swal2-popup swal2-modal swal2-icon-warning swal2-show" tabindex="-1" role="dialog" aria-live="assertive" aria-modal="true" style="display: grid;">
        <div class="swal2-icon swal2-warning swal2-icon-show" style="display: flex;">
            <div class="swal2-icon-content">!</div>
        </div>
        <img class="swal2-image">
        <div class="swal2-html-container" id="swal2-html-container" style="display: block;">Are you sure you want to delete {{ $itemName }}? This action is irreversible.</div>
        <div class="swal2-actions" style="display: flex;">
            <button type="button" wire:click="deleteSelected" class="swal2-confirm btn fw-bold btn-danger" style="display: inline-block;" aria-label="">Yes, delete!</button>
            <button type="button" wire:click="cancelDeletion($model)" class="swal2-cancel btn fw-bold btn-active-light-primary" style="display: inline-block;" aria-label="">No, cancel</button>
        </div>
    </div>
</div>
