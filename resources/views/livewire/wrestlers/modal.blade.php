<div class="p-4">
    <div class="mb-10">
        <x-form.inputs.text class="bg-red-500" label="Name:" name="name" placeholder="Wrestler Name Here" wire:model="form.name" />
    </div>

    <div class="mb-10">
        <x-form.inputs.text label="Hometown:" name="hometown" placeholder="Orlando, FL" wire:model="form.hometown"  />
    </div>

    <div class="mb-10">
        <x-form.inputs.text label="Signature Move:" name="signature_move" placeholder="This Amazing Finisher" wire:model="form.signature_move"  />
    </div>

    <div class="mb-10">
        <x-form.inputs.date label="Start Date:" name="start_date" wire:model="form.start_date" />
    </div>
    <div class="mb-10">
        <x-form.inputs.text label="Feet:" name="name" placeholder="Feet" wire:model="form.height_feet" />
    </div>

    <div class="mb-10">
        <x-form.inputs.text label="Inches:" name="name" placeholder="Inches" wire:model="form.height_inches" />
    </div>

    <div class="mb-10">
        <x-form.inputs.text label="Weight:" name="weight" placeholder="lbs" wire:model="form.weight" />
    </div>

    <div>
        <button wire:click="save">Save</button>
    </div>
</div>