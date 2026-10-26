<?php

namespace App\Support;

/**
 * Splits a service amount into the payment processor (ATM) fee, the barber's
 * commission and the shop's share. The ATM fee is deducted first and the
 * remaining net amount is shared between the barber and the shop, so the
 * three parts always add up to the original amount.
 */
final readonly class RevenueSplit
{
    public function __construct(
        public int $atmFee,
        public int $barber,
        public int $shop,
    ) {}

    public static function calculate(int $amount, float|string $atmFeeRate, float|string $barberCommissionRate): self
    {
        $atmFee = (int) round($amount * (float) $atmFeeRate / 100);
        $net = $amount - $atmFee;
        $barber = (int) round($net * (float) $barberCommissionRate / 100);

        return new self($atmFee, $barber, $net - $barber);
    }
}
