<?php

namespace Tests\Feature\Barbershop;

use App\Enums\UserRole;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_creates_a_barber_who_can_log_in()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.barbers.store'), [
                'name' => 'Carlos',
                'email' => 'carlos@example.com',
                'commission_rate' => 45,
                'is_active' => true,
                'password' => 'secreto-123',
                'password_confirmation' => 'secreto-123',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.barbers.index'));

        $barber = User::where('email', 'carlos@example.com')->sole();
        $this->assertSame(UserRole::Barber, $barber->role);
        $this->assertSame('45.00', $barber->commission_rate);
        $this->assertNotNull($barber->email_verified_at);
        $this->assertTrue(Hash::check('secreto-123', $barber->password));
    }

    public function test_admin_updates_a_barber_without_changing_the_password()
    {
        $barber = User::factory()->barber()->create();
        $password = $barber->password;

        $this->actingAs($this->admin)
            ->put(route('admin.barbers.update', $barber), [
                'name' => 'Nuevo nombre',
                'email' => $barber->email,
                'commission_rate' => 60,
                'is_active' => false,
            ])
            ->assertSessionHasNoErrors();

        $barber->refresh();
        $this->assertSame('Nuevo nombre', $barber->name);
        $this->assertFalse($barber->is_active);
        $this->assertSame($password, $barber->password);
    }

    public function test_commission_rate_must_be_a_percentage()
    {
        $barber = User::factory()->barber()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.barbers.update', $barber), [
                'name' => $barber->name,
                'email' => $barber->email,
                'commission_rate' => 120,
                'is_active' => true,
            ])
            ->assertSessionHasErrors('commission_rate');
    }

    public function test_admin_manages_services()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.services.store'), ['name' => 'Corte + Barba', 'base_price' => 19000, 'is_active' => true])
            ->assertSessionHasNoErrors();

        $service = Service::sole();
        $this->assertSame(19000, $service->base_price);

        $this->actingAs($this->admin)->delete(route('admin.services.destroy', $service));
        $this->assertSoftDeleted($service);
    }

    public function test_admin_manages_payment_methods()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.payment-methods.store'), ['name' => 'Crédito', 'iva_rate' => 2.95, 'is_active' => true])
            ->assertSessionHasNoErrors();

        $this->assertSame('2.95', PaymentMethod::sole()->iva_rate);

        $this->actingAs($this->admin)
            ->post(route('admin.payment-methods.store'), ['name' => 'Otro', 'iva_rate' => -1, 'is_active' => true])
            ->assertSessionHasErrors('iva_rate');
    }

    public function test_admin_restores_a_deleted_service()
    {
        $service = Service::factory()->create(['base_price' => 19000]);
        $service->delete();

        $this->actingAs($this->admin)
            ->get(route('admin.services.index'))
            ->assertInertia(fn ($page) => $page
                ->has('services', 0)
                ->where('trashedServices.0.id', $service->id));

        $this->actingAs($this->admin)
            ->post(route('admin.services.restore', $service))
            ->assertRedirect(route('admin.services.index'));

        $this->assertNotSoftDeleted($service);
        $this->assertSame(19000, $service->fresh()->base_price);
    }

    public function test_admin_restores_a_deleted_payment_method()
    {
        $paymentMethod = PaymentMethod::factory()->withIva(3)->create();
        $paymentMethod->delete();

        $this->actingAs($this->admin)
            ->get(route('admin.payment-methods.index'))
            ->assertInertia(fn ($page) => $page
                ->has('paymentMethods', 0)
                ->where('trashedPaymentMethods.0.id', $paymentMethod->id));

        $this->actingAs($this->admin)
            ->post(route('admin.payment-methods.restore', $paymentMethod))
            ->assertRedirect(route('admin.payment-methods.index'));

        $this->assertNotSoftDeleted($paymentMethod);
    }

    public function test_admin_reorders_services()
    {
        [$cut, $beard, $eyebrows] = Service::factory()->count(3)->sequence(
            ['name' => 'Corte', 'sort_order' => 0],
            ['name' => 'Barba', 'sort_order' => 0],
            ['name' => 'Cejas', 'sort_order' => 1],
        )->create();

        $this->actingAs($this->admin)
            ->from(route('admin.services.index'))
            ->put(route('admin.services.reorder'), ['ids' => [$eyebrows->id, $cut->id, $beard->id]])
            ->assertRedirect(route('admin.services.index'));

        $this->assertSame([$eyebrows->id, $cut->id, $beard->id], Service::ordered()->pluck('id')->all());
    }

    public function test_admin_reorders_payment_methods()
    {
        [$cash, $debit, $credit] = PaymentMethod::factory()->count(3)->sequence(
            ['name' => 'Efectivo', 'sort_order' => 0],
            ['name' => 'Débito', 'sort_order' => 1],
            ['name' => 'Crédito', 'sort_order' => 2],
        )->create();

        $this->actingAs($this->admin)
            ->from(route('admin.payment-methods.index'))
            ->put(route('admin.payment-methods.reorder'), ['ids' => [$credit->id, $cash->id, $debit->id]])
            ->assertRedirect(route('admin.payment-methods.index'));

        $this->assertSame([$credit->id, $cash->id, $debit->id], PaymentMethod::ordered()->pluck('id')->all());
    }

    public function test_reordering_rejects_unknown_ids()
    {
        $service = Service::factory()->create(['sort_order' => 3]);

        $this->actingAs($this->admin)
            ->put(route('admin.services.reorder'), ['ids' => [999999, $service->id]])
            ->assertSessionHasErrors('ids.0');

        $this->assertSame(3, $service->fresh()->sort_order);
    }

    public function test_new_catalog_items_are_added_last()
    {
        Service::factory()->create(['name' => 'Barba', 'sort_order' => 4]);
        PaymentMethod::factory()->create(['name' => 'Débito', 'sort_order' => 2]);

        $this->actingAs($this->admin)
            ->post(route('admin.services.store'), ['name' => 'Afeitado', 'base_price' => 8000, 'is_active' => true])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->admin)
            ->post(route('admin.payment-methods.store'), ['name' => 'Crédito', 'iva_rate' => 0, 'is_active' => true])
            ->assertSessionHasNoErrors();

        $this->assertSame(['Barba', 'Afeitado'], Service::ordered()->pluck('name')->all());
        $this->assertSame(['Débito', 'Crédito'], PaymentMethod::ordered()->pluck('name')->all());
    }

    public function test_barbers_cannot_reorder_catalogs()
    {
        $barber = User::factory()->barber()->create();
        [$first, $second] = Service::factory()->count(2)->sequence(['sort_order' => 0], ['sort_order' => 1])->create();
        $paymentMethod = PaymentMethod::factory()->create(['sort_order' => 5]);

        $this->actingAs($barber)->put(route('admin.services.reorder'), ['ids' => [$second->id, $first->id]])->assertForbidden();
        $this->actingAs($barber)->put(route('admin.payment-methods.reorder'), ['ids' => [$paymentMethod->id]])->assertForbidden();

        $this->assertSame(0, $first->fresh()->sort_order);
        $this->assertSame(5, $paymentMethod->fresh()->sort_order);
    }

    public function test_barbers_cannot_restore_catalog_items()
    {
        $barber = User::factory()->barber()->create();
        $service = Service::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        $service->delete();
        $paymentMethod->delete();

        $this->actingAs($barber)->post(route('admin.services.restore', $service))->assertForbidden();
        $this->actingAs($barber)->post(route('admin.payment-methods.restore', $paymentMethod))->assertForbidden();

        $this->assertSoftDeleted($service);
        $this->assertSoftDeleted($paymentMethod);
    }

    public function test_catalog_pages_render()
    {
        foreach (['admin.barbers.index', 'admin.barbers.create', 'admin.services.index', 'admin.services.create', 'admin.payment-methods.index', 'admin.payment-methods.create', 'admin.closings.index', 'transactions.index'] as $route) {
            $this->actingAs($this->admin)->get(route($route))->assertOk();
        }
    }

    public function test_seeder_creates_the_initial_data()
    {
        $this->seed();

        $this->assertTrue(User::where('email', config('barbershop.admin.email'))->sole()->isAdmin());
        $this->assertSame(4, PaymentMethod::count());
        $this->assertSame(2, Service::count());

        $this->seed();
        $this->assertSame(4, PaymentMethod::count());
    }
}
