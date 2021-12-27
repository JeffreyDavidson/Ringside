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
<x-kt-section>
    @if ($wrestlers->isNotEmpty())
        <div class="row">
            <div class="col-lg-6">
                <div class="form-group">
                    <x-form.inputs.select
                        name="wrestler1"
                        label="Tag Team Partner"
                        :options="$wrestlers"
                        :isSelected="$tagTeam->currentWrestlers[0] ?? ''"
                    />
                </div>
            </div>
            <div class="col-lg-6">
                <div class="form-group">
                    <x-form.inputs.select
                        name="wrestler2"
                        label="Tag Team Partner"
                        :options="$wrestlers"
                        :isSelected="$tagTeam->currentWrestlers[1] ?? ''"
                    />
                </div>
            </div>
        </div>
    @else
        <hr class="kt-separator">
        <h3 class="kt-section__title">Tag Team Partner 1</h3>
        @include('wrestlers.partials.form', ['wrestler' => new \App\Models\Wrestler])
        <hr class="kt-separator">
        <h3 class="kt-section__title">Tag Team Partner 2</h3>
        @include('wrestlers.partials.form', ['wrestler' => new \App\Models\Wrestler])
    @endif
</x-kt-section>
