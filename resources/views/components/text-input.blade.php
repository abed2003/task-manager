@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-700 focus:ring-cyan-700']) }}>
