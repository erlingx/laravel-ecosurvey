@props([
    'variant' => 'default',
])

@php
$classes = match($variant) {
    'success' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
    'error' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
    'warning' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800',
    'info' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
    default => 'bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700',
};
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg shadow-sm border p-6 {$classes}"]) }}>
    {{ $slot }}
</div>

