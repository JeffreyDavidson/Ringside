<form action="{{ $action }}" method="post">
    @method('PATCH')
    @csrf
    {{ $slot }}
</form>
