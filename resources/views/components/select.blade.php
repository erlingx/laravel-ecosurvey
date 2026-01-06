@props([
    'label' => null,
    'error' => null,
])

<div>
    <div class="relative">
        <select {{ $attributes->except(['label', 'error'])->merge([
            'class' => 'block w-full rounded-lg border-0 py-2.5 pl-3 pr-10 shadow-sm ring-1 ring-inset transition-all duration-200
                        text-zinc-900 dark:text-zinc-100
                        bg-white dark:bg-zinc-800
                        ring-zinc-300 dark:ring-zinc-600
                        focus:ring-2 focus:ring-inset focus:ring-blue-500 dark:focus:ring-blue-400
                        hover:ring-zinc-400 dark:hover:ring-zinc-500
                        disabled:cursor-not-allowed disabled:bg-zinc-50 dark:disabled:bg-zinc-900 disabled:text-zinc-500 dark:disabled:text-zinc-600
                        sm:text-sm sm:leading-6' . ($error ? ' ring-red-500 dark:ring-red-400 focus:ring-red-500' : '')
        ]) }}>
            {{ $slot }}
        </select>
    </div>

    @if($label && !$error)
        <p class="mt-1.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
    @endif

    @if($error)
        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @endif
</div>

