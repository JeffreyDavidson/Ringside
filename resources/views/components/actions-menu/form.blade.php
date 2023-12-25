<form action="{{ $action }}" method="post">
    @method('DELETE')
    @csrf
    {{ $slot }}
</form>
