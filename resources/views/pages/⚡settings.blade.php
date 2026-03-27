<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Settings')] class extends Component {
    //
}; ?>

<section class="w-full space-y-8">
    <div>
        <flux:heading size="xl" level="1">{{ __('Settings') }}</flux:heading>
        <flux:subheading size="lg">{{ __('Manage your profile and account settings') }}</flux:subheading>
    </div>

    <div class="paceday-card">
        <livewire:pages::settings.profile />
    </div>

    <div class="paceday-card">
        <livewire:pages::settings.security />
    </div>
</section>
