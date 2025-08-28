@props([
    'for' => null,
    'required' => false,
    'badge' => null,
])

<label 
    {{ $attributes->merge([
        'for' => $for,
        'class' => 'text-2sm font-normal text-gray-900'
    ]) }}>
    {{ $slot }}
    
    @if($badge)
        <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
            {{ $badge }}
        </span>
    @endif
    
    @if($required)
        <span class="text-red-500 ml-1">*</span>
    @endif
</label>