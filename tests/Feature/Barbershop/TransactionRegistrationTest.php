<?php

namespace Tests\Feature\Barbershop;

use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Service $haircut;

    private PaymentMethod $debit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->haircut = Service::factory()->create(['name' => 'Corte de cabello', 'base_price' => 20000]);
        $this->debit = PaymentMethod::factory()->withAtmFee(3)->create(['name' => 'Débito']);
    }

    public function test_the_point_of_sale_renders_the_catalogs()
    {
        $admin = User::factory()->admin()->create();
        User::factory()->barber()->create();
        User::factory()->barber()->inactive()->create();
        Service::factory()->inactive()->create();

        $this->actingAs($admin)
            ->get(route('transactions.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('transactions/Register')
                ->has('services', 1)
                ->has('paymentMethods', 1)
                ->has('barbers', 1)
                ->where('currentBarber', null));
    }

    public function test_admin_registers_a_service_and_the_split_is_calculated()
    {
        $admin = User::factory()->admin()->create();
        $barber = User::factory()->barber()->create(['commission_rate' => 50]);

        $this->actingAs($admin)
            ->post(route('transactions.store'), [
                'barber_id' => $barber->id,
                'service_id' => $this->haircut->id,
                'payment_method_id' => $this->debit->id,
                'amount' => 20000,
                'tip' => 2000,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('transactions.create'));

        $this->assertDatabaseHas('transactions', [
            'barber_id' => $barber->id,
            'registered_by' => $admin->id,
            'amount' => 20000,
            'tip' => 2000,
            'atm_fee_amount' => 600,
            'barber_amount' => 9700,
            'shop_amount' => 9700,
            'service_name' => 'Corte de cabello',
            'payment_method_name' => 'Débito',
        ]);
    }

    public function test_admin_can_override_the_base_price()
    {
        $admin = User::factory()->admin()->create();
        $barber = User::factory()->barber()->create(['commission_rate' => 50]);

        $this->actingAs($admin)->post(route('transactions.store'), [
            'barber_id' => $barber->id,
            'service_id' => $this->haircut->id,
            'payment_method_id' => $this->debit->id,
            'amount' => 10000,
            'notes' => 'Descuento',
        ]);

        $transaction = Transaction::sole();
        $this->assertSame(10000, $transaction->amount);
        $this->assertSame(20000, $transaction->service_base_price);
        $this->assertSame(300, $transaction->atm_fee_amount);
    }

    public function test_barbers_register_for_themselves_at_the_base_price()
    {
        $barber = User::factory()->barber()->create(['commission_rate' => 50]);
        $colleague = User::factory()->barber()->create();

        $this->actingAs($barber)
            ->post(route('transactions.store'), [
                'barber_id' => $colleague->id,
                'service_id' => $this->haircut->id,
                'payment_method_id' => $this->debit->id,
                'amount' => 1,
                'performed_at' => now()->subMonth()->toDateTimeString(),
            ])
            ->assertSessionHasNoErrors();

        $transaction = Transaction::sole();
        $this->assertSame($barber->id, $transaction->barber_id);
        $this->assertSame(20000, $transaction->amount);
        $this->assertTrue($transaction->performed_at->isToday());
    }

    public function test_admin_must_choose_an_active_barber()
    {
        $admin = User::factory()->admin()->create();
        $inactive = User::factory()->barber()->inactive()->create();

        $this->actingAs($admin)
            ->post(route('transactions.store'), [
                'barber_id' => $inactive->id,
                'service_id' => $this->haircut->id,
                'payment_method_id' => $this->debit->id,
                'amount' => 20000,
            ])
            ->assertSessionHasErrors('barber_id');

        $this->actingAs($admin)
            ->post(route('transactions.store'), [
                'barber_id' => $admin->id,
                'service_id' => $this->haircut->id,
                'payment_method_id' => $this->debit->id,
                'amount' => 20000,
            ])
            ->assertSessionHasErrors('barber_id');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_inactive_services_and_payment_methods_are_rejected()
    {
        $barber = User::factory()->barber()->create();
        $inactiveService = Service::factory()->inactive()->create();
        $inactiveMethod = PaymentMethod::factory()->inactive()->create();

        $this->actingAs($barber)
            ->post(route('transactions.store'), [
                'service_id' => $inactiveService->id,
                'payment_method_id' => $inactiveMethod->id,
            ])
            ->assertSessionHasErrors(['service_id', 'payment_method_id']);
    }

    public function test_catalog_changes_do_not_alter_registered_transactions()
    {
        $barber = User::factory()->barber()->create(['commission_rate' => 50]);

        $this->actingAs($barber)->post(route('transactions.store'), [
            'service_id' => $this->haircut->id,
            'payment_method_id' => $this->debit->id,
        ]);

        $this->haircut->update(['name' => 'Corte premium', 'base_price' => 30000]);
        $this->debit->update(['atm_fee_rate' => 10]);
        $barber->update(['commission_rate' => 70]);

        $transaction = Transaction::sole();
        $this->assertSame('Corte de cabello', $transaction->service_name);
        $this->assertSame(20000, $transaction->amount);
        $this->assertSame('3.00', $transaction->atm_fee_rate);
        $this->assertSame('50.00', $transaction->barber_commission_rate);
        $this->assertSame(9700, $transaction->barber_amount);
    }

    public function test_admin_can_correct_a_transaction_keeping_the_original_rates()
    {
        $admin = User::factory()->admin()->create();
        $barber = User::factory()->barber()->create(['commission_rate' => 50]);
        $transaction = Transaction::factory()->create([
            'barber_id' => $barber->id,
            'service_id' => $this->haircut->id,
            'payment_method_id' => $this->debit->id,
        ]);

        $this->debit->update(['atm_fee_rate' => 10]);

        $this->actingAs($admin)
            ->put(route('transactions.update', $transaction), [
                'barber_id' => $barber->id,
                'service_id' => $this->haircut->id,
                'payment_method_id' => $this->debit->id,
                'amount' => 10000,
                'tip' => 500,
                'performed_at' => $transaction->performed_at->toDateTimeString(),
            ])
            ->assertSessionHasNoErrors();

        $transaction->refresh();
        $this->assertSame(10000, $transaction->amount);
        $this->assertSame(500, $transaction->tip);
        $this->assertSame(300, $transaction->atm_fee_amount);
        $this->assertSame(4850, $transaction->barber_amount);
    }

    public function test_changing_the_payment_method_takes_its_current_rate()
    {
        $admin = User::factory()->admin()->create();
        $cash = PaymentMethod::factory()->create(['name' => 'Efectivo']);
        $transaction = Transaction::factory()->create([
            'service_id' => $this->haircut->id,
            'payment_method_id' => $this->debit->id,
        ]);

        $this->actingAs($admin)->put(route('transactions.update', $transaction), [
            'barber_id' => $transaction->barber_id,
            'service_id' => $this->haircut->id,
            'payment_method_id' => $cash->id,
            'amount' => 20000,
            'performed_at' => $transaction->performed_at->toDateTimeString(),
        ])->assertSessionHasNoErrors();

        $transaction->refresh();
        $this->assertSame('Efectivo', $transaction->payment_method_name);
        $this->assertSame(0, $transaction->atm_fee_amount);
    }

    public function test_admin_can_void_a_transaction_with_a_reason()
    {
        $admin = User::factory()->admin()->create();
        $transaction = Transaction::factory()->create();

        $this->actingAs($admin)
            ->post(route('transactions.void', $transaction), ['void_reason' => 'Registrada dos veces'])
            ->assertSessionHasNoErrors();

        $transaction->refresh();
        $this->assertNotNull($transaction->voided_at);
        $this->assertSame($admin->id, $transaction->voided_by);
        $this->assertSame('Registrada dos veces', $transaction->void_reason);
    }

    public function test_a_voided_transaction_cannot_be_edited()
    {
        $admin = User::factory()->admin()->create();
        $transaction = Transaction::factory()->voided()->create();

        $this->actingAs($admin)->get(route('transactions.edit', $transaction))->assertForbidden();
    }
}
