<?php

namespace App\Domain;

class FefoAllocator
{
    /**
     * Allocate quantity across batches for sales using FEFO rules.
     * Excludes batches that have expired before $referenceDate.
     *
     * @param array $batches Each element has ['id', 'expiry_date', 'received_on', 'remaining_qty', 'unit_cost']
     * @param float $targetQty
     * @param string $referenceDate 'YYYY-MM-DD'
     * @return array
     */
    public function allocateForSale(array $batches, float $targetQty, string $referenceDate): array
    {
        return $this->allocate($batches, $targetQty, $referenceDate, true);
    }

    /**
     * Allocate quantity across batches for negative stocktake adjustment.
     * Includes expired batches (OPN-04: batch with earliest expiry exhausted first).
     *
     * @param array $batches
     * @param float $targetQty
     * @return array
     */
    public function allocateForAdjustment(array $batches, float $targetQty): array
    {
        return $this->allocate($batches, $targetQty, null, false);
    }

    /**
     * Internal sorting and allocation logic.
     */
    protected function allocate(array $batches, float $targetQty, ?string $referenceDate, bool $excludeExpired): array
    {
        $filtered = [];

        foreach ($batches as $b) {
            $remaining = (float) ($b['remaining_qty'] ?? 0);
            if ($remaining <= 0.0001) {
                continue;
            }

            $expiry = $b['expiry_date'] ?? null;
            if ($excludeExpired && $expiry !== null && $referenceDate !== null) {
                if ($expiry < $referenceDate) {
                    // Expired batch, excluded from sale (TC-02)
                    continue;
                }
            }

            $filtered[] = $b;
        }

        // Sort: expiry_date ASC NULLS LAST, then received_on ASC, then id ASC
        usort($filtered, function ($a, $b) {
            $expA = $a['expiry_date'] ?? null;
            $expB = $b['expiry_date'] ?? null;

            if ($expA !== $expB) {
                if ($expA === null) return 1;
                if ($expB === null) return -1;
                return strcmp($expA, $expB);
            }

            $recA = $a['received_on'] ?? '';
            $recB = $b['received_on'] ?? '';
            if ($recA !== $recB) {
                return strcmp($recA, $recB);
            }

            return strcmp((string)$a['id'], (string)$b['id']);
        });

        $remainingToAllocate = $targetQty;
        $allocations = [];
        $totalCost = 0.0;
        $totalAllocatedQty = 0.0;

        foreach ($filtered as $batch) {
            if ($remainingToAllocate <= 0.00001) {
                break;
            }

            $batchRemaining = (float) $batch['remaining_qty'];
            $take = min($batchRemaining, $remainingToAllocate);
            $take = round($take, 3);

            if ($take > 0) {
                $unitCost = (int) ($batch['unit_cost'] ?? 0);
                $allocations[] = [
                    'batch_id' => $batch['id'],
                    'qty' => $take,
                    'unit_cost' => $unitCost,
                ];

                $totalCost += ($take * $unitCost);
                $totalAllocatedQty += $take;
                $remainingToAllocate = round($remainingToAllocate - $take, 3);
            }
        }

        $weightedUnitCost = $totalAllocatedQty > 0
            ? (int) round($totalCost / $totalAllocatedQty)
            : 0;

        return [
            'allocations' => $allocations,
            'unallocated_qty' => max(0.0, $remainingToAllocate),
            'weighted_unit_cost' => $weightedUnitCost,
            'allocated_qty' => round($totalAllocatedQty, 3),
        ];
    }
}
