<?php

namespace App\Services;

class CurrencyService
{
    public const SUPPORTED = ['PRB', 'MDL', 'UAH'];

    protected float $prbPerMdl; // 1 MDL = x PRB
    protected float $prbPerUah; // 1 UAH = x PRB

    public function __construct()
    {
        $this->prbPerMdl = (float) config('currency.prb_per_mdl');
        $this->prbPerUah = (float) config('currency.prb_per_uah');
    }

    public function convert(float $amount, string $from, string $to): float
    {
        return $this->quote($amount, $from, $to)['amount'];
    }

    /**
     * @return array{amount: float, rate: float, from: string, to: string}
     */
    public function quote(float $amount, string $from, string $to): array
    {
        $from = $this->normalize($from);
        $to = $this->normalize($to);

        if (! in_array($from, self::SUPPORTED, true) || ! in_array($to, self::SUPPORTED, true)) {
            throw new \InvalidArgumentException("Unsupported currency conversion: {$from} to {$to}");
        }

        $rate = $this->rate($from, $to);

        return [
            'amount' => round($amount * $rate, 2, PHP_ROUND_HALF_UP),
            'rate' => round($rate, 8, PHP_ROUND_HALF_UP),
            'from' => $from,
            'to' => $to,
        ];
    }

    public function checkoutCurrency(?string $currency): string
    {
        $currency = $this->normalize($currency ?: (string) config('currency.checkout_currency', 'PRB'));

        return in_array($currency, self::SUPPORTED, true)
            ? $currency
            : (string) config('currency.checkout_currency', 'PRB');
    }

    private function rate(string $from, string $to): float
    {
        if ($from === $to) {
            return 1.0;
        }

        $mdlPerPrb = $this->prbPerMdl > 0 ? 1 / $this->prbPerMdl : 0.0; // 1 PRB в MDL
        $uahPerPrb = $this->prbPerUah > 0 ? 1 / $this->prbPerUah : 0.0; // 1 PRB в UAH

        $rates = [
            'PRB' => ['MDL' => $mdlPerPrb,          'UAH' => $uahPerPrb         ],
            'MDL' => ['PRB' => $this->prbPerMdl,    'UAH' => $mdlPerPrb ? $uahPerPrb / $mdlPerPrb : 0],
            'UAH' => ['PRB' => $this->prbPerUah,    'MDL' => $uahPerPrb ? $mdlPerPrb / $uahPerPrb : 0],
        ];

        if (! isset($rates[$from][$to]) || $rates[$from][$to] <= 0) {
            throw new \RuntimeException("Invalid currency rate: {$from} to {$to}");
        }

        return $rates[$from][$to];
    }

    private function normalize(string $currency): string
    {
        $currency = strtoupper($currency);

        return $currency === 'RUB' ? 'PRB' : $currency;
    }
}
