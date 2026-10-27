<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('panel'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_panel()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/panel');
        $response->assertOk();
    }
}
