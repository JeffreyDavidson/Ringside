<table {{ $attributes->merge(['class' => 'table align-middle']) }}>
    <thead>
        <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
            {{ $head }}
        </tr>
    </thead>

    <tbody class="fw-semibold text-gray-600">
        {{ $body }}
    </tbody>
</table>
