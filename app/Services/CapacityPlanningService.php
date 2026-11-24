<?php

namespace App\Services;

class CapacityPlanningService
{
    /**
     * Hitung jumlah mesin yang dibutuhkan untuk memenuhi target order.
     *
     * Formula:
     * Available Seconds Per Machine = Working Days * Daily Hours * 3600 * Efficiency
     * Total Required Seconds = Target Pairs * SMV Seconds
     * Required Machines = ceil(Total Required Seconds / Available Seconds Per Machine)
     *
     * @param  int  $targetPcs  Jumlah pcs yang harus diproduksi
     * @param  float  $smvSeconds  SMV per pcs dalam detik
     * @param  int  $workingDays  Jumlah hari kerja efektif
     * @param  float  $dailyHours  Jam operasional per hari (default 24 jam untuk pabrik 3 shift)
     * @param  float  $efficiency  Target efisiensi lini rajut (contoh: 0.85 = 85%)
     */
    public function calculateRequiredMachines(
        int $targetPcs,
        float $smvSeconds,
        int $workingDays,
        float $dailyHours = 24.0,
        float $efficiency = 0.85
    ): int {
        if ($targetPcs <= 0 || $workingDays <= 0 || $smvSeconds <= 0) {
            return 0;
        }

        $availableSecondsPerMachine = $workingDays * $dailyHours * 3600 * $efficiency;
        $totalRequiredSeconds = $targetPcs * $smvSeconds;

        return (int) ceil($totalRequiredSeconds / $availableSecondsPerMachine);
    }

    /**
     * Hitung persentase beban kapasitas (load percentage) dari mesin aktif.
     *
     * @param  int  $activeMachines  Jumlah mesin yang aktif
     * @param  int  $targetPcs  Target kuantitas pcs
     * @param  float  $smvSeconds  SMV detik
     * @param  int  $workingDays  Hari kerja
     * @param  float  $dailyHours  Jam operasional
     * @param  float  $efficiency  Efisiensi
     */
    public function calculateLoadPercentage(
        int $activeMachines,
        int $targetPcs,
        float $smvSeconds,
        int $workingDays,
        float $dailyHours = 24.0,
        float $efficiency = 0.85
    ): float {
        if ($activeMachines <= 0 || $workingDays <= 0) {
            return 0.0;
        }

        $totalCapacitySeconds = $activeMachines * $workingDays * $dailyHours * 3600 * $efficiency;
        $requiredSeconds = $targetPcs * $smvSeconds;

        return round(($requiredSeconds / $totalCapacitySeconds) * 100, 2);
    }
}
