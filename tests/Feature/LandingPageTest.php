<?php

use App\Models\User;

test('guests see the landing page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Paceday')
        ->assertSee('Start tracking something');
});

test('authenticated users are redirected to the dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertRedirect(route('dashboard'));
});
