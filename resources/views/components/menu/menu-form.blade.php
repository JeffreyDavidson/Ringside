@props([
    'path' => '',
    'rowId' => '',
])

<form action="{{ route($path . '.destroy', $rowId) }}" class="inline" method="POST" x-data
    @submit.prevent="if (confirm('Are you sure you want to delete this user?')) $el.submit()">
    @method('DELETE')
    @csrf

    <x-buttons.link type="submit">
        <i class="ki-trash text-lg"></i>
        Remove
    </x-buttons.link>
</form>
