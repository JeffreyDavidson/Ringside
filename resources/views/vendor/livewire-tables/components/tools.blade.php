@aware(['component','isTailwind','isBootstrap'])

<div @class([
    'flex flex-wrap gap-2.5' => $isTailwind,
    'd-flex flex-column ' => ($isBootstrap),
])>
    {{ $slot }}
</div>
