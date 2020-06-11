<x-kt-section title="General Information">
    <div class="form-group">
        <x-form.inputs.text
            name="name"
            label="Name"
            :value="old('name', $tagTeam->name)"
        />
    </div>
    <div class="form-group row">
        <div class="col-lg-6">
            <x-form.inputs.date
                name="started_at"
                label="Started At"
                :value="old('started_at', $tagTeam->startedAt)"
            />
        </div>
        <div class="col-lg-6">
            <x-form.inputs.text
                name="signature_move"
                label="Signature Move"
                :value="old('signature_move', $tagTeam->signature_move)"
            />
        </div>
    </div>
</x-kt-section>
<x-kt-section title="Wrestlers">
    <div class="row">
        <div class="col-lg-6">
            <div class="form-group">
                <x-form.inputs.select
                    name="wrestler1"
                    label="Tag Team Partner"
                    :options="$wrestlers"
                    :isSelected="$isTagTeamPartner1"
                />
            </div>
        </div>
        <div class="col-lg-6">
            <div class="form-group">
                <x-form.inputs.select
                    name="wrestler2"
                    label="Tag Team Partner"
                    :options="$wrestlers"
                    :isSelected="$isTagTeamPartner2"
                />
            </div>
        </div>
    </div>
</x-kt-section>
