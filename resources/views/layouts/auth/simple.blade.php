<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen paceday-gradient antialiased">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-6">
                <a href="{{ route('home') }}" class="flex items-center justify-center gap-2.5" wire:navigate>
                    <x-app-logo-icon class="size-7 text-rust" />
                    <span class="font-heading text-xl font-bold tracking-tight text-bark">Paceday</span>
                </a>
                <div class="paceday-card">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
