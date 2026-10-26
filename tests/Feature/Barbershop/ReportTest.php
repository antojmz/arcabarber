<?php

namespace Tests\Feature\Barbershop;

use App\Enums\ClosingPeriod;
use App\Models\PaymentMethod;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $carlos;

    private User $matias;

    private PaymentMethod $cash;

    private PaymentMethod $debit;

    private Service $haircut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-14 12:00')); // Wednesday

        $this->carlos = User::factory()->barber()->create(['name' => 'Carlos', 'commission_rate' => 50]);
        $this->matias = User::factory()->barber()->create(['name' => 'Matías', 'commission_rate' => 40]);
        $this->cash = PaymentMethod::factory()->create(['name' => 'Efectivo']);
        $this->debit = PaymentMethod::factory()->withAtmFee(3)->create(['name' => 'Débito']);
        $this->haircut = Service::factory()->create(['base_price' => 20000]);
    }

    private function transaction(User $barber, PaymentMethod $method, string $when, array $attributes = []): Transaction
    {
        return Transaction::factory()->create([
            'barber_id' => $barber->id,
            'payment_method_id' => $method->id,
            'service_id' => $this->haircut->id,
            'performed_at' => $when,
            ...$attributes,
        ]);
    }

    public function test_period_ranges()
    {
        $date = CarbonImmutable::parse('2026-10-14');

        $this->assertSame(['2026-10-14', '2026-10-14'], array_map(fn ($d) => $d->toDateString(), ClosingPeriod::Daily->range($date)));
        $this->assertSame(['2026-10-12', '2026-10-18'], array_map(fn ($d) => $d->toDateString(), ClosingPeriod::Weekly->range($date)));
        $this->assertSame(['2026-10-01', '2026-10-31'], array_map(fn ($d) => $d->toDateString(), ClosingPeriod::Monthly->range($date)));
    }

    public function test_barber_summary_breaks_down_by_payment_method_and_excludes_voided()
    {
        $this->transaction($this->carlos, $this->debit, '2026-10-14 10:00', ['tip' => 1000]);
        $this->transaction($this->carlos, $this->debit, '2026-10-14 11:00');
        $this->transaction($this->carlos, $this->cash, '2026-10-14 11:30');
        $this->transaction($this->carlos, $this->cash, '2026-10-14 11:45', ['voided_at' => now(), 'voided_by' => $this->carlos->id]);
        $this->transaction($this->matias, $this->cash, '2026-10-14 10:00');
        $this->transaction($this->carlos, $this->cash, '2026-10-13 10:00');

        [$start, $end] = ClosingPeriod::Daily->range(now());
        $summary = app(ReportService::class)->summary($start, $end, $this->carlos->id);

        $this->assertSame([
            'services_count' => 3,
            'gross_total' => 60000,
            'tips_total' => 1000,
            'atm_fee_total' => 1200,
            'barber_total' => 29400,
            'shop_total' => 29400,
            'payout_total' => 30400,
        ], $summary['totals']);

        $byMethod = collect($summary['by_payment_method'])->keyBy('payment_method_name');
        $this->assertSame(2, $byMethod['Débito']['services_count']);
        $this->assertSame(1, $byMethod['Efectivo']['services_count']);
        $this->assertSame([], $summary['by_barber']);
    }

    public function test_general_summary_includes_every_barber()
    {
        $this->transaction($this->carlos, $this->debit, '2026-10-12 10:00');
        $this->transaction($this->matias, $this->cash, '2026-10-18 20:00');
        $this->transaction($this->matias, $this->cash, '2026-10-19 09:00');

        [$start, $end] = ClosingPeriod::Weekly->range(now());
        $summary = app(ReportService::class)->summary($start, $end);

        $this->assertSame(2, $summary['totals']['services_count']);
        $this->assertSame(40000, $summary['totals']['gross_total']);

        $byBarber = collect($summary['by_barber'])->keyBy('barber_name');
        $this->assertSame(9700, $byBarber['Carlos']['barber_total']);
        $this->assertSame(8000, $byBarber['Matías']['barber_total']);
    }

    public function test_barber_report_page()
    {
        $admin = User::factory()->admin()->create();
        $this->transaction($this->carlos, $this->debit, '2026-10-14 10:00');

        $this->actingAs($admin)
            ->get(route('admin.reports.barber', ['barber_id' => $this->carlos->id, 'period' => 'monthly', 'date' => '2026-10-14']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/reports/Barber')
                ->where('barber.id', $this->carlos->id)
                ->where('range.start', '2026-10-01')
                ->where('summary.totals.payout_total', 9700)
                ->has('serviceCounts', 1)
                ->where('closing', null));
    }

    public function test_general_report_page()
    {
        $admin = User::factory()->admin()->create();
        $this->transaction($this->carlos, $this->debit, '2026-10-14 10:00');
        $this->transaction($this->matias, $this->cash, '2026-10-14 10:00');

        $this->actingAs($admin)
            ->get(route('admin.reports.general'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/reports/General')
                ->where('filters.period', 'daily')
                ->where('summary.totals.gross_total', 40000)
                ->has('summary.by_payment_method', 2)
                ->has('summary.by_barber', 2));
    }
}
