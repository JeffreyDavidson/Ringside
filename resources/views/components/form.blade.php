<form method="post" {{ $attributes }}>
    @csrf

    {{ $slot }}
</form>
