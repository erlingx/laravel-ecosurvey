@props([
>{{ $slot }}</textarea>
    ]) }}
        'class' => 'block w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm text-zinc-900 transition focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 disabled:cursor-not-allowed disabled:bg-zinc-100 disabled:opacity-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-emerald-400 dark:disabled:bg-zinc-900'
    {{ $attributes->merge([
    placeholder="{{ $placeholder }}"
    {{ $disabled ? 'disabled' : '' }}
    rows="{{ $rows }}"
<textarea

])
    'disabled' => false,
    'rows' => 4,
    'placeholder' => null,

