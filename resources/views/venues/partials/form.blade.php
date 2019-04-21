<div class="form-group">
    <label>Venue Name:</label>
    <input type="text" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" name="name" placeholder="Enter venue name" value="{{ $venue->name ?? old('name') }}">
    @if ($errors->has('name'))
        <div id="name-error" class="error invalid-feedback">{{ $errors->first('name') }}</div>
    @endif
</div>
<div class="row">
    <div class="col-lg-8">
        <div class="form-group">
            <label>Street Address:</label>
            <input type="text" class="form-control {{ $errors->has('address1') ? 'is-invalid' : '' }}" name="address1" placeholder="Enter street address" value="{{ $venue->address1 ?? old('address1') }}">
            @if ($errors->has('address1'))
                <div id="address1-error" class="error invalid-feedback">{{ $errors->first('address1') }}</div>
            @endif
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-group">
            <label>Suite Number:</label>
            <input type="text" class="form-control {{ $errors->has('address2') ? 'is-invalid' : '' }}" name="address2" placeholder="Enter suite number" value="{{ $venue->address2 ?? old('address2') }}">
            @if ($errors->has('address2'))
                <div id="address2-error" class="error invalid-feedback">{{ $errors->first('address2') }}</div>
            @endif
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-4">
        <div class="form-group">
            <label>City:</label>
            <input type="text" class="form-control {{ $errors->has('city') ? 'is-invalid' : '' }}" name="city" placeholder="Enter city" value="{{ $venue->city ?? old('city') }}">
            @if ($errors->has('city'))
                <div id="city-error" class="error invalid-feedback">{{ $errors->first('city') }}</div>
            @endif
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-group">
            <label>State:</label>
            <input type="text" class="form-control {{ $errors->has('state') ? 'is-invalid' : '' }}" name="state" placeholder="Enter state" value="{{ $venue->state ?? old('state') }}">
            @if ($errors->has('state'))
                <div id="email-error" class="error invalid-feedback">{{ $errors->first('state') }}</div>
            @endif
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-group">
            <label>Zip Code:</label>
            <input type="text" class="form-control {{ $errors->has('zip') ? 'is-invalid' : '' }}" name="zip" placeholder="Enter zip" value="{{ $venue->zip ?? old('zip') }}">
            @if ($errors->has('zip'))
                <div id="zip-error" class="error invalid-feedback">{{ $errors->first('zip') }}</div>
            @endif
        </div>
    </div>
</div>
