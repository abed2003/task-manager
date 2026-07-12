@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-bold text-sm text-emerald-600']) }}>
        {{ $status }}
    </div>
@endif
