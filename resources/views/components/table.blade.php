<table {{ $attributes->merge(['class' => 'table align-middle']) }}>
    <thead>
        <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0">
            {{ $head }}
        </tr>
    </thead>

    <tbody class="text-gray-600 fw-bold">
        {{ $body }}
    </tbody>
</table>
