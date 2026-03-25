@props([
    'sidebar' => false,
])

<a {{ $attributes->merge(['class' => 'flex items-center gap-2.5']) }}>
    <x-app-logo-icon class="size-7 text-rust" />
    <span class="font-heading text-xl font-bold tracking-tight text-bark">Paceday</span>
</a>
