<?php

namespace App\Domain;

class InventoryCalculator
{
    /**
     * Calculate daily sale rate over D days.
     */
    public function calculateDailyRate(float $totalQtySold, int $days): float
    {
        if ($days <= 0) {
            return 0.0;
        }

        return round($totalQtySold / $days, 3);
    }

    /**
     * Calculate threshold. If min_stock is set, it overrides calculation.
     */
    public function calculateThreshold(?float $minStock, float $dailyRate, int $leadTimeDays, float $safetyStock): ?float
    {
        if ($minStock !== null) {
            return round($minStock, 3);
        }

        return round(($dailyRate * $leadTimeDays) + $safetyStock, 3);
    }

    /**
     * Calculate days of stock left based on sellable stock and daily rate.
     */
    public function calculateDaysLeft(float $sellableStock, float $dailyRate): ?float
    {
        if ($dailyRate <= 0.0001) {
            return null;
        }

        return round($sellableStock / $dailyRate, 1);
    }

    /**
     * Evaluate whether historical data is sufficient for automatic threshold.
     */
    public function evaluateProductDataSufficiency(int $daysSinceFirstMovement, ?float $minStock): string
    {
        if ($minStock !== null) {
            return 'ready';
        }

        if ($daysSinceFirstMovement < 7) {
            return 'data_belum_cukup';
        }

        return 'ready';
    }
}
