<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen paceday-gradient">
        {{-- Minimal top bar --}}
        <header class="sticky top-0 z-50 backdrop-blur-md bg-cream/80">
            <div class="mx-auto flex max-w-lg items-center justify-center px-5 py-4">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <x-app-logo-icon class="size-7 text-rust" />
                    <span class="font-heading text-xl font-bold tracking-tight text-bark">Paceday</span>
                </a>
            </div>
        </header>

        {{-- Content --}}
        <main class="mx-auto max-w-lg px-5 pb-12 pt-6">
            {{ $slot }}
        </main>

        @fluxScripts
    </body>
</html>
