<?php

namespace App\Services;

class CurrencyService
{
    protected float $prbPerMdl; // 1 MDL = x PRB
    protected float $prbPerUah; // 1 UAH = x PRB

    public function __construct()
    {
        $this->prbPerMdl = (float) env('PRB_PER_MDL', 0.9419);
        $this->prbPerUah = (float) env('PRB_PER_UAH', 0.48);
    }

    public function convert(float $amount, string $from, string $to): float
    {
        $from = strtoupper($from);
        $to   = strtoupper($to);
        if ($from === $to) return round($amount, 2);

        $mdlPerPrb = $this->prbPerMdl > 0 ? 1 / $this->prbPerMdl : 0.0; // 1 PRB в MDL
        $uahPerPrb = $this->prbPerUah > 0 ? 1 / $this->prbPerUah : 0.0; // 1 PRB в UAH

        $rates = [
            'PRB' => ['MDL' => $mdlPerPrb,          'UAH' => $uahPerPrb         ],
            'MDL' => ['PRB' => $this->prbPerMdl,    'UAH' => $mdlPerPrb ? $uahPerPrb / $mdlPerPrb : 0],
            'UAH' => ['PRB' => $this->prbPerUah,    'MDL' => $uahPerPrb ? $mdlPerPrb / $uahPerPrb : 0],
        ];

        if (!isset($rates[$from][$to]) || $rates[$from][$to] <= 0) {
            return round($amount, 2);
        }

        return round($amount * $rates[$from][$to], 2);
    }
}
