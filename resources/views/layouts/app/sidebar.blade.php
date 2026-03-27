<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen paceday-gradient">
        {{-- Top bar --}}
        <header class="sticky top-0 z-50 backdrop-blur-md bg-cream/80">
            <div class="mx-auto flex max-w-lg items-center justify-between px-5 py-4">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5">
                    <x-app-logo-icon class="size-7 text-rust" />
                    <span class="font-heading text-xl font-bold tracking-tight text-bark">Paceday</span>
                </a>

                <flux:dropdown position="bottom" align="end">
                    <button type="button" class="flex items-center gap-2 rounded-full bg-white p-1.5 pr-3 shadow-sm transition hover:shadow-md">
                        <flux:avatar
                            :name="auth()->user()->name"
                            :initials="auth()->user()->initials()"
                            size="xs"
                        />
                        <span class="text-sm font-medium text-bark">{{ auth()->user()->first_name ?? Str::before(auth()->user()->name, ' ') }}</span>
                    </button>

                    <flux:menu>
                        <flux:menu.item :href="route('settings')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item
                                as="button"
                                type="submit"
                                icon="arrow-right-start-on-rectangle"
                                class="w-full cursor-pointer"
                            >
                                {{ __('Log out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </header>

        {{-- Content --}}
        <main class="mx-auto max-w-lg px-5 pb-12 pt-6">
            {{ $slot }}
        </main>

        @fluxScripts
    </body>
</html>
