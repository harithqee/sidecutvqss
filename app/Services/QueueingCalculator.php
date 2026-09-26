<?php

namespace App\Services;

/**
 * Implements the queueing-theory formulas from queueing_formulas.md.
 *
 * Legend (matches the source document):
 *   λ (lambda) — arrival rate
 *   μ (mu)     — service rate PER SERVER (i.e. per barber)
 *   S          — number of servers (active barbers)
 *   ρ (rho)    — utilization factor
 *
 * IMPORTANT: lambda and mu must be expressed in the SAME time unit
 * (this app uses "per hour" throughout) or the results are meaningless.
 */
class QueueingCalculator
{
    /**
     * M/M/1 — Single-server queue. Kept for completeness / a shop with one barber.
     *
     * L  = λ / (μ − λ)
     * Lq = λ² / [μ(μ − λ)]
     * W  = 1 / (μ − λ)
     */
    public function mm1(float $lambda, float $mu): array
    {
        if ($lambda < 0 || $mu <= $lambda) {
            return $this->unstableResult($mu > 0 ? ['rho' => $lambda / $mu] : []);
        }

        $rho = $lambda / $mu;
        $L   = $lambda / ($mu - $lambda);
        $Lq  = ($lambda ** 2) / ($mu * ($mu - $lambda));
        $W   = 1 / ($mu - $lambda);
        $Wq  = $lambda > 0 ? $Lq / $lambda : 0.0;

        return compact('rho', 'L', 'Lq', 'W', 'Wq') + ['stable' => true];
    }

    /**
     * M/M/S — Multi-server queue. This is the one sidecutvqss uses, since a shop
     * normally has several active barbers serving the same queue.
     *
     * ρ  = λ / (S·μ)
     * P0 = 1 / [ Σ(n=0..S-1) (λ/μ)^n/n!  +  (λ/μ)^S/S! · 1/(1−ρ) ]
     * Lq = [P0 · (λ/μ)^S · ρ] / [S! · (1−ρ)²]
     *
     * L and W follow from Little's Law once Lq/P0 are known.
     */
    public function mms(float $lambda, float $mu, int $servers): array
    {
        if ($servers < 1 || $mu <= 0 || $lambda < 0) {
            return $this->unstableResult();
        }

        $rho = $lambda / ($servers * $mu);

        // ρ >= 1 means arrivals outpace total service capacity — queue grows unbounded.
        if ($rho >= 1) {
            return $this->unstableResult(['rho' => $rho]);
        }

        $a = $lambda / $mu; // offered load (Erlangs)

        $sum = 0.0;
        for ($n = 0; $n < $servers; $n++) {
            $sum += ($a ** $n) / $this->factorial($n);
        }

        $lastTerm = ($a ** $servers) / $this->factorial($servers) * (1 / (1 - $rho));

        $P0 = 1 / ($sum + $lastTerm);

        $Lq = ($P0 * ($a ** $servers) * $rho) / ($this->factorial($servers) * (1 - $rho) ** 2);
        $Wq = $lambda > 0 ? $Lq / $lambda : 0.0; // avg wait time IN QUEUE
        $W  = $Wq + (1 / $mu);                   // avg time in the whole system
        $L  = $lambda * $W;                      // Little's Law

        return [
            'rho'    => $rho,
            'P0'     => $P0,
            'L'      => $L,
            'Lq'     => $Lq,
            'W'      => $W,
            'Wq'     => $Wq,
            'stable' => true,
        ];
    }

    private function factorial(int $n): float
    {
        return $n <= 1 ? 1.0 : $n * $this->factorial($n - 1);
    }

    private function unstableResult(array $overrides = []): array
    {
        return array_merge([
            'rho' => null, 'P0' => null, 'L' => null, 'Lq' => null,
            'W' => null, 'Wq' => null, 'stable' => false,
        ], $overrides);
    }
}