<?php

namespace Tests\Feature\Barbershop;

use App\Models\CashClosing;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function adminRoutes(): array
    {
        return [
            'barbers' => ['get', '/admin/barbers'],
            'create barber' => ['post', '/admin/barbers'],
            'services' => ['get', '/admin/services'],
            'payment methods' => ['get', '/admin/payment-methods'],
            'barber report' => ['get', '/admin/reports/barber'],
            'general report' => ['get', '/admin/reports/general'],
            'closings' => ['get', '/admin/closings'],
            'close period' => ['post', '/admin/closings'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_barbers_cannot_reach_admin_routes(string $method, string $uri)
    {
        $barber = User::factory()->barber()->create();

        $this->actingAs($barber)->{$method}($uri)->assertForbidden();
    }

    #[DataProvider('adminRoutes')]
    public function test_guests_are_redirected_to_login(string $method, string $uri)
    {
        $this->{$method}($uri)->assertRedirect(route('login'));
    }

    public function test_barbers_cannot_open_a_closing()
    {
        $barber = User::factory()->barber()->create();
        $closing = CashClosing::factory()->create(['barber_id' => $barber->id]);

        $this->actingAs($barber)->get(route('admin.closings.show', $closing))->assertForbidden();
    }

    public function test_barbers_cannot_edit_or_void_transactions_even_their_own()
    {
        $barber = User::factory()->barber()->create();
        $transaction = Transaction::factory()->create(['barber_id' => $barber->id]);

        $this->actingAs($barber)->get(route('transactions.edit', $transaction))->assertForbidden();
        $this->actingAs($barber)->post(route('transactions.void', $transaction), ['void_reason' => 'x'])->assertForbidden();

        $this->assertNull($transaction->fresh()->voided_at);
    }

    public function test_barbers_only_see_their_own_transactions()
    {
        $barber = User::factory()->barber()->create();
        $colleague = User::factory()->barber()->create();

        $own = Transaction::factory()->create(['barber_id' => $barber->id]);
        Transaction::factory()->create(['barber_id' => $colleague->id]);

        $this->actingAs($barber)
            ->get(route('transactions.index', ['barber_id' => $colleague->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('transactions/Index')
                ->has('transactions.data', 1)
                ->where('transactions.data.0.id', $own->id)
                ->where('barbers', []));
    }

    public function test_barber_production_ignores_a_barber_id_in_the_request()
    {
        $barber = User::factory()->barber()->create();
        $colleague = User::factory()->barber()->create();

        Transaction::factory()->create(['barber_id' => $barber->id]);
        Transaction::factory()->count(3)->create(['barber_id' => $colleague->id]);

        $this->actingAs($barber)
            ->get(route('my.production', ['barber_id' => $colleague->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('my/Production')
                ->where('barber.id', $barber->id)
                ->where('summary.totals.services_count', 1));
    }

    public function test_barber_dashboard_only_shows_own_figures()
    {
        $barber = User::factory()->barber()->create();
        Transaction::factory()->create(['barber_id' => $barber->id]);
        Transaction::factory()->count(2)->create();

        $this->actingAs($barber)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->component('barber/Dashboard')
                ->where('today.totals.services_count', 1)
                ->has('recent', 1));
    }

    public function test_admin_sees_the_admin_dashboard()
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->component('admin/Dashboard'));
    }

    public function test_admin_cannot_manage_an_admin_through_the_barber_catalog()
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.barbers.edit', $otherAdmin->id))->assertNotFound();
    }

    public function test_inactive_users_cannot_log_in()
    {
        $user = User::factory()->barber()->inactive()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_deactivated_users_are_logged_out_on_their_next_request()
    {
        $user = User::factory()->barber()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->update(['is_active' => false]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_public_registration_is_disabled()
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Intruso',
            'email' => 'intruso@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'intruso@example.com']);
    }
}
