<?php

namespace App\Domain;

class CashCalculator
{
    /**
     * Calculate expected cash balance at closing.
     */
    public function calculateExpectedCash(int $openingCash, int $cashSales, int $expenses, int $cashPurchases): int
    {
        return $openingCash + $cashSales - $expenses - $cashPurchases;
    }

    /**
     * Calculate difference between actual counted cash and expected cash.
     */
    public function calculateCashDiff(int $actualCash, int $expectedCash): array
    {
        $diff = $actualCash - $expectedCash;

        if ($diff < 0) {
            $formatted = 'kurang Rp ' . number_format(abs($diff), 0, ',', '.');
        } elseif ($diff > 0) {
            $formatted = 'lebih Rp ' . number_format($diff, 0, ',', '.');
        } else {
            $formatted = 'cocok (Rp 0)';
        }

        return [
            'diff' => $diff,
            'status' => $diff === 0 ? 'balanced' : ($diff < 0 ? 'shortage' : 'surplus'),
            'formatted_diff' => $formatted,
        ];
    }
}
