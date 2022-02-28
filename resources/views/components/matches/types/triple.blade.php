<div class="mb-10 row">
    <div class="col-lg-4">
        <x-form.inputs.select
            name="competitors[0]"
            label="Competitor"
            :selected="old('wrestlerA', $match->competitors?->first()?->competitor->id)"
            :options="\App\Models\Wrestler::bookable()->pluck('name', 'id')"
        />
    </div>

    <div class="col-lg-4">
        <x-form.inputs.select
            name="competitors[1]"
            label="Competitor"
            :selected="old('wrestlerB', $match->competitors?->first()?->competitor->id)"
            :options="\App\Models\Wrestler::bookable()->pluck('name', 'id')"
        />
    </div>

    <div class="col-lg-4">
        <x-form.inputs.select
            name="competitors[2]"
            label="Competitor"
            :selected="old('wrestlerC', $match->competitors?->first()?->competitor->id)"
            :options="\App\Models\Wrestler::bookable()->pluck('name', 'id')"
        />
    </div>
</div>
