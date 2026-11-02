<?php

namespace App\Support;

/**
 * Splits a service amount into the IVA (value added tax), the barber's
 * commission and the shop's share. The IVA is deducted first and the
 * remaining net amount is shared between the barber and the shop, so the
 * three parts always add up to the original amount.
 */
final readonly class RevenueSplit
{
    public function __construct(
        public int $iva,
        public int $barber,
        public int $shop,
    ) {}

    public static function calculate(int $amount, float|string $ivaRate, float|string $barberCommissionRate): self
    {
        $iva = (int) round($amount * (float) $ivaRate / 100);
        $net = $amount - $iva;
        $barber = (int) round($net * (float) $barberCommissionRate / 100);

        return new self($iva, $barber, $net - $barber);
    }
}
