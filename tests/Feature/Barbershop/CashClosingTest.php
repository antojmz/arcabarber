<?php

namespace Tests\Feature\Barbershop;

use App\Models\CashClosing;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashClosingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $barber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-14 20:00'));

        $this->admin = User::factory()->admin()->create();
        $this->barber = User::factory()->barber()->create(['commission_rate' => 50]);
    }

    public function test_closing_a_barber_period_stores_a_snapshot()
    {
        $debit = PaymentMethod::factory()->withIva(3)->create();
        $service = Service::factory()->create(['base_price' => 20000]);
        Transaction::factory()->count(2)->create([
            'barber_id' => $this->barber->id,
            'payment_method_id' => $debit->id,
            'service_id' => $service->id,
            'tip' => 1000,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.closings.store'), [
                'period' => 'daily',
                'date' => '2026-10-14',
                'barber_id' => $this->barber->id,
            ])
            ->assertSessionHasNoErrors();

        $closing = CashClosing::sole();
        $this->assertSame($this->barber->id, $closing->barber_id);
        $this->assertSame(2, $closing->services_count);
        $this->assertSame(40000, $closing->gross_total);
        $this->assertSame(19400, $closing->barber_total);
        $this->assertSame(21400, $closing->payout_total);
        $this->assertCount(1, $closing->lines);

        $this->actingAs($this->admin)
            ->get(route('admin.closings.show', $closing))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/closings/Show')
                ->where('closing.payout_total', 21400)
                ->has('closing.by_payment_method', 1));
    }

    public function test_a_period_cannot_be_closed_twice()
    {
        foreach ([$this->barber->id, null] as $barberId) {
            $payload = ['period' => 'weekly', 'date' => '2026-10-14', 'barber_id' => $barberId];

            $this->actingAs($this->admin)->post(route('admin.closings.store'), $payload)->assertSessionHasNoErrors();
            $this->actingAs($this->admin)->post(route('admin.closings.store'), $payload)->assertSessionHasErrors('date');
        }

        $this->assertDatabaseCount('cash_closings', 2);
    }

    public function test_future_periods_cannot_be_closed()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.closings.store'), ['period' => 'daily', 'date' => '2026-10-15'])
            ->assertSessionHasErrors('date');
    }

    public function test_transactions_in_a_closed_period_are_locked()
    {
        $transaction = Transaction::factory()->create(['barber_id' => $this->barber->id]);

        $this->actingAs($this->admin)->post(route('admin.closings.store'), [
            'period' => 'daily',
            'date' => '2026-10-14',
            'barber_id' => $this->barber->id,
        ]);

        $this->assertTrue($transaction->isLocked());

        $this->actingAs($this->admin)->get(route('transactions.edit', $transaction))->assertForbidden();
        $this->actingAs($this->admin)
            ->post(route('transactions.void', $transaction), ['void_reason' => 'x'])
            ->assertForbidden();

        $this->actingAs($this->barber)
            ->post(route('transactions.store'), [
                'services' => [['service_id' => $transaction->service_id]],
                'payment_method_id' => $transaction->payment_method_id,
            ])
            ->assertSessionHasErrors('performed_at');
    }

    public function test_a_barber_closing_does_not_lock_other_barbers()
    {
        $other = User::factory()->barber()->create();
        $transaction = Transaction::factory()->create(['barber_id' => $other->id]);

        $this->actingAs($this->admin)->post(route('admin.closings.store'), [
            'period' => 'daily',
            'date' => '2026-10-14',
            'barber_id' => $this->barber->id,
        ]);

        $this->assertFalse($transaction->isLocked());
    }

    public function test_a_general_closing_locks_every_barber()
    {
        $transaction = Transaction::factory()->create(['barber_id' => $this->barber->id]);

        $this->actingAs($this->admin)->post(route('admin.closings.store'), [
            'period' => 'monthly',
            'date' => '2026-10-01',
        ]);

        $this->assertTrue($transaction->isLocked());
    }

    public function test_admin_can_mark_a_payroll_closing_as_paid()
    {
        $closing = CashClosing::factory()->create(['barber_id' => $this->barber->id]);

        $this->actingAs($this->admin)->post(route('admin.closings.mark-paid', $closing));
        $this->assertNotNull($closing->fresh()->paid_at);

        $this->actingAs($this->admin)->post(route('admin.closings.mark-paid', $closing));
        $this->assertNull($closing->fresh()->paid_at);
    }

    public function test_barber_sees_own_closings_in_production()
    {
        CashClosing::factory()->create(['barber_id' => $this->barber->id]);
        CashClosing::factory()->create(['barber_id' => User::factory()->barber()]);

        $this->actingAs($this->barber)
            ->get(route('my.production'))
            ->assertInertia(fn ($page) => $page->has('closings', 1));
    }
}
