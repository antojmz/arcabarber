<?php

namespace Tests\Feature;

use App\Models\CashClosing;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $barber;

    private Service $haircut;

    private PaymentMethod $cash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->barber = User::factory()->barber()->create(['name' => 'Carlos', 'password' => '1234']);
        $this->haircut = Service::factory()->create(['base_price' => 14000]);
        $this->cash = PaymentMethod::factory()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'barber_id' => $this->barber->id,
            'service_ids' => [$this->haircut->id],
            'payment_method_id' => $this->cash->id,
            'password' => '1234',
            ...$overrides,
        ];
    }

    public function test_guests_see_todays_activity_without_amounts()
    {
        User::factory()->barber()->inactive()->create();
        Transaction::factory()->count(2)->create(['barber_id' => $this->barber->id]);
        Transaction::factory()->voided()->create(['barber_id' => $this->barber->id]);
        Transaction::factory()->create(['barber_id' => $this->barber->id, 'performed_at' => now()->subDay()]);

        $this->get(route('public.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/Dashboard')
                ->has('barbers', 1)
                ->where('today.services_count', 2)
                ->where('today.by_barber.0.services_count', 2)
                ->has('today.transactions', 2)
                ->has('today.transactions.0', fn ($row) => $row
                    ->hasAll(['id', 'performed_at', 'barber_name', 'service_name', 'payment_method_name'])
                    ->missingAll(['amount', 'tip', 'barber_amount', 'shop_amount', 'iva_amount'])));
    }

    public function test_todays_services_are_sorted_by_creation_date_descending()
    {
        $first = Transaction::factory()->create([
            'barber_id' => $this->barber->id,
            'performed_at' => now()->setTime(12, 0),
            'created_at' => now()->subMinutes(30),
        ]);
        $second = Transaction::factory()->create([
            'barber_id' => $this->barber->id,
            'performed_at' => now()->setTime(9, 0),
            'created_at' => now()->subMinutes(20),
        ]);
        $third = Transaction::factory()->create([
            'barber_id' => $this->barber->id,
            'performed_at' => now()->setTime(10, 0),
            'created_at' => now()->subMinutes(10),
        ]);

        $this->get(route('public.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('today.transactions.0.id', $third->id)
                ->where('today.transactions.1.id', $second->id)
                ->where('today.transactions.2.id', $first->id));
    }

    public function test_a_service_is_registered_with_the_barbers_password()
    {
        $this->post(route('public.services.store'), $this->payload(['amount' => 1, 'tip' => 2000]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('public.dashboard'));

        $transaction = Transaction::sole();
        $this->assertSame($this->barber->id, $transaction->barber_id);
        $this->assertSame($this->barber->id, $transaction->registered_by);
        $this->assertSame(14000, $transaction->amount);
        $this->assertSame(2000, $transaction->tip);
        $this->assertTrue($transaction->performed_at->isToday());
    }

    public function test_several_services_are_registered_at_once()
    {
        $beard = Service::factory()->create(['name' => 'Barba', 'base_price' => 8000]);

        $this->post(route('public.services.store'), $this->payload([
            'service_ids' => [$this->haircut->id, $beard->id],
            'tip' => 1000,
        ]))->assertSessionHasNoErrors();

        $transactions = Transaction::orderBy('id')->get();
        $this->assertSame([14000, 8000], $transactions->pluck('amount')->all());
        $this->assertSame(1000, $transactions->sum('tip'));
        $this->assertSame([$this->barber->id], $transactions->pluck('barber_id')->unique()->values()->all());
    }

    public function test_a_wrong_password_is_rejected()
    {
        $this->post(route('public.services.store'), $this->payload(['password' => '9999']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_another_barbers_password_is_not_accepted()
    {
        User::factory()->barber()->create(['password' => '5555']);

        $this->post(route('public.services.store'), $this->payload(['password' => '5555']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_inactive_barbers_cannot_receive_services()
    {
        $this->barber->update(['is_active' => false]);

        $this->post(route('public.services.store'), $this->payload())
            ->assertSessionHasErrors('barber_id');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_password_attempts_are_throttled()
    {
        foreach (range(1, 5) as $attempt) {
            $this->post(route('public.services.store'), $this->payload(['password' => '0000']));
        }

        $this->post(route('public.services.store'), $this->payload())
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_services_cannot_be_registered_in_a_closed_period()
    {
        CashClosing::factory()->create(['barber_id' => null]);

        $this->post(route('public.services.store'), $this->payload())
            ->assertSessionHasErrors('performed_at');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_the_public_dashboard_cannot_close_periods()
    {
        $this->post(route('admin.closings.store'), ['period' => 'daily', 'date' => today()->toDateString()])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('cash_closings', 0);
    }
}
