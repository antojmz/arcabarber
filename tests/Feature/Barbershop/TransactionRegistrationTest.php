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
        $this->debit = PaymentMethod::factory()->withIva(3)->create(['name' => 'Débito']);
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
                'services' => [['service_id' => $this->haircut->id, 'amount' => 20000]],
                'payment_method_id' => $this->debit->id,
                'tip' => 2000,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('transactions.create'));

        $this->assertDatabaseHas('transactions', [
            'barber_id' => $barber->id,
            'registered_by' => $admin->id,
            'amount' => 20000,
            'tip' => 2000,
            'iva_amount' => 600,
            'barber_amount' => 9700,
            'shop_amount' => 9700,
            'payment_method_name' => 'Débito',
        ]);
        $this->assertDatabaseHas('transaction_items', [
            'transaction_id' => Transaction::sole()->id,
            'service_name' => 'Corte de cabello',
            'amount' => 20000,
        ]);
    }

    public function test_admin_can_override_the_base_price()
    {
        $admin = User::factory()->admin()->create();
        $barber = User::factory()->barber()->create(['commission_rate' => 50]);

        $this->actingAs($admin)->post(route('transactions.store'), [
            'barber_id' => $barber->id,
            'services' => [['service_id' => $this->haircut->id, 'amount' => 10000]],
            'payment_method_id' => $this->debit->id,
            'notes' => 'Descuento',
        ]);

        $transaction = Transaction::sole();
        $this->assertSame(10000, $transaction->amount);
        $this->assertSame(20000, $transaction->items->sole()->service_base_price);
        $this->assertSame(300, $transaction->iva_amount);
    }

    public function test_barbers_register_for_themselves_at_the_base_price()
    {
        $barber = User::factory()->barber()->create(['commission_rate' => 50]);
        $colleague = User::factory()->barber()->create();

        $this->actingAs($barber)
            ->post(route('transactions.store'), [
                'barber_id' => $colleague->id,
                'services' => [['service_id' => $this->haircut->id, 'amount' => 1]],
                'payment_method_id' => $this->debit->id,
                'performed_at' => now()->subMonth()->toDateTimeString(),
            ])
            ->assertSessionHasNoErrors();

        $transaction = Transaction::sole();
        $this->assertSame($barber->id, $transaction->barber_id);
        $this->assertSame(20000, $transaction->amount);
        $this->assertTrue($transaction->performed_at->isToday());
    }

    public function test_several_services_are_registered_as_one_transaction()
    {
        $barber = User::factory()->barber()->create(['commission_rate' => 50]);
        $beard = Service::factory()->create(['name' => 'Barba', 'base_price' => 8000]);

        $this->actingAs($barber)
            ->post(route('transactions.store'), [
                'services' => [['service_id' => $this->haircut->id], ['service_id' => $beard->id]],
                'payment_method_id' => $this->debit->id,
                'tip' => 2000,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('transactions.create'));

        $transaction = Transaction::sole();
        $this->assertSame(['Corte de cabello', 'Barba'], $transaction->items->pluck('service_name')->all());
        $this->assertSame([20000, 8000], $transaction->items->pluck('amount')->all());
        $this->assertSame(28000, $transaction->amount);
        $this->assertSame(2000, $transaction->tip);
        $this->assertSame(840, $transaction->iva_amount);
        $this->assertSame([600, 240], $transaction->items->pluck('iva_amount')->all());
        $this->assertSame($transaction->amount - $transaction->iva_amount, $transaction->barber_amount + $transaction->shop_amount);
    }

    public function test_admin_sets_the_amount_of_each_service()
    {
        $admin = User::factory()->admin()->create();
        $barber = User::factory()->barber()->create();
        $beard = Service::factory()->create(['base_price' => 8000]);

        $this->actingAs($admin)
            ->post(route('transactions.store'), [
                'barber_id' => $barber->id,
                'services' => [
                    ['service_id' => $this->haircut->id, 'amount' => 18000],
                    ['service_id' => $beard->id],
                ],
                'payment_method_id' => $this->debit->id,
            ])
            ->assertSessionHasErrors('services.1.amount');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_the_same_service_cannot_be_selected_twice()
    {
        $barber = User::factory()->barber()->create();

        $this->actingAs($barber)
            ->post(route('transactions.store'), [
                'services' => [['service_id' => $this->haircut->id], ['service_id' => $this->haircut->id]],
                'payment_method_id' => $this->debit->id,
            ])
            ->assertSessionHasErrors('services.0.service_id');

        $this->actingAs($barber)
            ->post(route('transactions.store'), ['services' => [], 'payment_method_id' => $this->debit->id])
            ->assertSessionHasErrors('services');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_admin_must_choose_an_active_barber()
    {
        $admin = User::factory()->admin()->create();
        $inactive = User::factory()->barber()->inactive()->create();

        $this->actingAs($admin)
            ->post(route('transactions.store'), [
                'barber_id' => $inactive->id,
                'services' => [['service_id' => $this->haircut->id, 'amount' => 20000]],
                'payment_method_id' => $this->debit->id,
            ])
            ->assertSessionHasErrors('barber_id');

        $this->actingAs($admin)
            ->post(route('transactions.store'), [
                'barber_id' => $admin->id,
                'services' => [['service_id' => $this->haircut->id, 'amount' => 20000]],
                'payment_method_id' => $this->debit->id,
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
                'services' => [['service_id' => $inactiveService->id]],
                'payment_method_id' => $inactiveMethod->id,
            ])
            ->assertSessionHasErrors(['services.0.service_id', 'payment_method_id']);
    }

    public function test_catalog_changes_do_not_alter_registered_transactions()
    {
        $barber = User::factory()->barber()->create(['commission_rate' => 50]);

        $this->actingAs($barber)->post(route('transactions.store'), [
            'services' => [['service_id' => $this->haircut->id]],
            'payment_method_id' => $this->debit->id,
        ]);

        $this->haircut->update(['name' => 'Corte premium', 'base_price' => 30000]);
        $this->debit->update(['iva_rate' => 10]);
        $barber->update(['commission_rate' => 70]);

        $transaction = Transaction::sole();
        $this->assertSame('Corte de cabello', $transaction->items->sole()->service_name);
        $this->assertSame(20000, $transaction->amount);
        $this->assertSame('3.00', $transaction->iva_rate);
        $this->assertSame('50.00', $transaction->barber_commission_rate);
        $this->assertSame(9700, $transaction->barber_amount);
    }

    public function test_admin_can_correct_a_transaction_keeping_the_original_rates()
    {
        $admin = User::factory()->admin()->create();
        $barber = User::factory()->barber()->create(['commission_rate' => 50]);
        $transaction = Transaction::factory()->withServices($this->haircut)->create([
            'barber_id' => $barber->id,
            'payment_method_id' => $this->debit->id,
        ]);

        $this->debit->update(['iva_rate' => 10]);

        $this->actingAs($admin)
            ->put(route('transactions.update', $transaction), [
                'barber_id' => $barber->id,
                'services' => [['service_id' => $this->haircut->id, 'amount' => 10000]],
                'payment_method_id' => $this->debit->id,
                'tip' => 500,
                'performed_at' => $transaction->performed_at->toDateTimeString(),
            ])
            ->assertSessionHasNoErrors();

        $transaction->refresh();
        $this->assertSame(10000, $transaction->amount);
        $this->assertSame(500, $transaction->tip);
        $this->assertSame(300, $transaction->iva_amount);
        $this->assertSame(4850, $transaction->barber_amount);
    }

    public function test_changing_the_payment_method_takes_its_current_rate()
    {
        $admin = User::factory()->admin()->create();
        $cash = PaymentMethod::factory()->create(['name' => 'Efectivo']);
        $transaction = Transaction::factory()->withServices($this->haircut)->create([
            'payment_method_id' => $this->debit->id,
        ]);

        $this->actingAs($admin)->put(route('transactions.update', $transaction), [
            'barber_id' => $transaction->barber_id,
            'services' => [['service_id' => $this->haircut->id, 'amount' => 20000]],
            'payment_method_id' => $cash->id,
            'performed_at' => $transaction->performed_at->toDateTimeString(),
        ])->assertSessionHasNoErrors();

        $transaction->refresh();
        $this->assertSame('Efectivo', $transaction->payment_method_name);
        $this->assertSame(0, $transaction->iva_amount);
    }

    public function test_admin_adds_and_removes_services_of_a_transaction()
    {
        $admin = User::factory()->admin()->create();
        $barber = User::factory()->barber()->create(['commission_rate' => 50]);
        $beard = Service::factory()->create(['name' => 'Barba', 'base_price' => 8000]);
        $eyebrows = Service::factory()->create(['name' => 'Cejas', 'base_price' => 3000]);
        $transaction = Transaction::factory()->withServices($this->haircut, $beard)->create([
            'barber_id' => $barber->id,
            'payment_method_id' => $this->debit->id,
        ]);

        $this->haircut->update(['name' => 'Corte premium', 'base_price' => 30000]);

        $this->actingAs($admin)->put(route('transactions.update', $transaction), [
            'barber_id' => $barber->id,
            'services' => [
                ['service_id' => $this->haircut->id, 'amount' => 20000],
                ['service_id' => $eyebrows->id, 'amount' => 3000],
            ],
            'payment_method_id' => $this->debit->id,
            'performed_at' => $transaction->performed_at->toDateTimeString(),
        ])->assertSessionHasNoErrors();

        $transaction->refresh();
        $this->assertSame(['Corte de cabello', 'Cejas'], $transaction->items->pluck('service_name')->all());
        $this->assertSame([20000, 3000], $transaction->items->pluck('service_base_price')->all());
        $this->assertSame(23000, $transaction->amount);
        $this->assertSame(690, $transaction->iva_amount);
        $this->assertSame($transaction->items->sum('barber_amount'), $transaction->barber_amount);
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
