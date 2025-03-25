@props([
    'path' => '',
    'rowId' => '',
])

<form action="{{ route($path . '.destroy', $rowId) }}" class="d-inline" method="POST" x-data
    @submit.prevent="if (confirm('Are you sure you want to delete this user?')) $el.submit()">
    @method('DELETE')
    @csrf

    <button type="submit" class="btn btn-link">
        <i class="fa-solid fa-trash"></i>
        Remove
    </button>
</form>
