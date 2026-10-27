<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_public_dashboard()
    {
        $this->get(route('home'))->assertRedirect(route('public.dashboard'));
    }

    public function test_authenticated_users_are_redirected_to_their_panel()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertRedirect(route('panel'));
    }
}
