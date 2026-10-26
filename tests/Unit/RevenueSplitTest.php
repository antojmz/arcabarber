<?php

namespace Tests\Unit;

use App\Support\RevenueSplit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RevenueSplitTest extends TestCase
{
    public function test_atm_fee_is_deducted_before_sharing_the_net_amount()
    {
        $split = RevenueSplit::calculate(20000, 3, 50);

        $this->assertSame(600, $split->atmFee);
        $this->assertSame(9700, $split->barber);
        $this->assertSame(9700, $split->shop);
    }

    public function test_cash_payments_have_no_atm_fee()
    {
        $split = RevenueSplit::calculate(14000, 0, 50);

        $this->assertSame(0, $split->atmFee);
        $this->assertSame(7000, $split->barber);
        $this->assertSame(7000, $split->shop);
    }

    public function test_decimal_rates_given_as_strings_are_supported()
    {
        $split = RevenueSplit::calculate(19000, '1.50', '45.00');

        $this->assertSame(285, $split->atmFee);
        $this->assertSame(8422, $split->barber);
        $this->assertSame(10293, $split->shop);
    }

    #[DataProvider('amounts')]
    public function test_the_three_parts_always_add_up_to_the_amount(int $amount, float $atmFeeRate, float $commissionRate)
    {
        $split = RevenueSplit::calculate($amount, $atmFeeRate, $commissionRate);

        $this->assertSame($amount, $split->atmFee + $split->barber + $split->shop);
        $this->assertGreaterThanOrEqual(0, $split->shop);
    }

    /**
     * @return array<string, array{int, float, float}>
     */
    public static function amounts(): array
    {
        return [
            'odd amount' => [14999, 2.95, 47.5],
            'zero amount' => [0, 3, 50],
            'full commission' => [10000, 1.5, 100],
            'no commission' => [10000, 1.5, 0],
            'rounding edge' => [333, 33.33, 33.33],
        ];
    }
}
