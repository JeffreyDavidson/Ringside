<div class="mb-10 row">
    <div class="col-lg-3">
        <x-form.inputs.select
            name="competitors[0]"
            label="Competitor"
            :selected="old('wrestler', $match->competitors?->first()?->competitor->id)"
            :options="\App\Models\Wrestler::bookable()->pluck('name', 'id')"
        />
    </div>

    <span>vs.</span>

    <div class="col-lg-3">
        <x-form.inputs.select
            name="competitors[1]"
            label="Competitor"
            :selected="old('wrestler', $match->competitors?->last()?->competitor->id)"
            :options="\App\Models\Wrestler::bookable()->pluck('name', 'id')"
        />
    </div>

    <span>vs.</span>

    <div class="col-lg-3">
        <x-form.inputs.select
            name="competitors[2]"
            label="Competitor"
            :selected="old('wrestler', $match->competitors?->last()?->competitor->id)"
            :options="\App\Models\Wrestler::bookable()->pluck('name', 'id')"
        />
    </div>

    <span>vs.</span>

    <div class="col-lg-3">
        <x-form.inputs.select
            name="competitors[3]"
            label="Competitor"
            :selected="old('wrestler', $match->competitors?->last()?->competitor->id)"
            :options="\App\Models\Wrestler::bookable()->pluck('name', 'id')"
        />
    </div>
</div>
