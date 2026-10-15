@props(['label', 'value' => null])

<tr>
    <td class="text-muted-foreground pe-4 pb-3 text-sm lg:pe-8">{{ $label }}:</td>
    <td class="text-foreground pb-3 text-sm">{{ $value }}{{ $slot }}</td>
</tr>
