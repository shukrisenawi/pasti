@props(['value'])

<label {{ $attributes->merge(['class' => 'label-base']) }}>
    {{ $value ?? $slot }}
</label>
