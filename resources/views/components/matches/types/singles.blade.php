<div class="mb-10 d-flex justify-content-between row">
    <div class="col-lg-5">
        <x-form.inputs.select
            name="competitors[0]"
            label="Competitor"
            :selected="old('wrestler', $match->competitors?->first()?->competitor->id)"
            :options="\App\Models\Wrestler::bookable()->pluck('name', 'id')"
        />
    </div>
    <div class="text-center align-self-center col-lg-2" style="vertical-align:middle">vs.</div>
    <div class="col-lg-5">
        <x-form.inputs.select
            name="competitors[1]"
            label="Competitor"
            :selected="old('wrestler', $match->competitors?->last()?->competitor->id)"
            :options="\App\Models\Wrestler::bookable()->pluck('name', 'id')"
        />
    </div>
</div>
