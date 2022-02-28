<div class="mb-10 row">
    <div class="col-lg-6">
        <x-form.inputs.select
            name="competitors[0]"
            label="Competitor"
            :selected="old('tagTeamA', $match->competitors?->first()?->competitor->id)"
            :options="\App\Models\TagTeam::bookable()->pluck('name', 'id')"
        />
    </div>

    <div class="col-lg-6">
        <x-form.inputs.select
            name="competitors[1]"
            label="Competitor"
            :selected="old('tagTeamB', $match->competitors?->last()?->competitor->id)"
            :options="\App\Models\TagTeam::bookable()->pluck('name', 'id')"
        />
    </div>
</div>
