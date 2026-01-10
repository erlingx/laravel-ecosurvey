<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" {{ $attributes }}>
    <!-- Globe/Earth outline -->
    <circle cx="24" cy="24" r="22" fill="none" stroke="currentColor" stroke-width="2"/>

    <!-- Leaf symbol (environmental focus) -->
    <path
        fill="currentColor"
        d="M24 8 C18 8, 14 12, 14 18 C14 24, 18 28, 24 32 C24 32, 24 20, 24 8 Z"
    />
    <path
        fill="currentColor"
        opacity="0.7"
        d="M24 8 C30 8, 34 12, 34 18 C34 24, 30 28, 24 32 C24 32, 24 20, 24 8 Z"
    />

    <!-- Data points (survey aspect) -->
    <circle cx="12" cy="24" r="2" fill="currentColor"/>
    <circle cx="36" cy="24" r="2" fill="currentColor"/>
    <circle cx="24" cy="36" r="2" fill="currentColor"/>

    <!-- Connection lines between data points -->
    <line x1="12" y1="24" x2="24" y2="36" stroke="currentColor" stroke-width="1.5" opacity="0.5"/>
    <line x1="36" y1="24" x2="24" y2="36" stroke="currentColor" stroke-width="1.5" opacity="0.5"/>
</svg>
