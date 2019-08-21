<select class="form-control" name="status" id="status-dropdown">
    <option value="">Select</option>
    @foreach ($statuses as $value => $label)
        <option value="{{ $value }}"> {{ $label }}</option>
    @endforeach
</select>
