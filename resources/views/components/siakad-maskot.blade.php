@props([
    'src' => 'images/siakad-sifeeder-maskot.png',
])

<img
    src="{{ asset($src) }}"
    alt="Maskot Siakad-Feeder"
    {{ $attributes->merge(['class' => 'object-contain select-none max-h-48 w-auto']) }}
>
