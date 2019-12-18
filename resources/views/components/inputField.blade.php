<field-label :for="$name" :label="$label ?? ''" />

<input 
    type="text" 
    class="form-control @error($name) is-invalid @enderror" 
    name="{{ $name }}"
    placeholder="Enter {{ $label }}" 
    value="{{ old($name, $model->$name) }}"
>

@error($name)
    <form-error name="{{ $name }} />
@enderror
