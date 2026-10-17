<?php

namespace App\Services;

use App\Enums\CashTransactionType;
use App\Enums\PaymentMethod;
use App\Enums\ShiftStatus;
use App\Exceptions\PosException;
use App\Models\CashTransaction;
use App\Models\Payment;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ShiftService
{
    public function open(User $user, int $locationId, float $openingCash, ?string $operatorName = null): Shift
    {
        return DB::transaction(function () use ($user, $locationId, $openingCash, $operatorName) {
            $existing = Shift::query()
                ->where('user_id', $user->id)
                ->where('status', ShiftStatus::Open)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new PosException('Anda masih memiliki shift yang terbuka.');
            }

            return Shift::create([
                'user_id' => $user->id,
                'location_id' => $locationId,
                'operator_name' => $operatorName,
                'opened_at' => now(),
                'opening_cash' => $openingCash,
                'status' => ShiftStatus::Open,
            ]);
        });
    }

    /**
     * Cash that SHOULD be in the drawer right now (Tech Spec §5.6). Cash
     * payments are stored net (dibayar − kembalian), so a plain sum already
     * reflects what actually entered the drawer.
     */
    public function expectedCash(Shift $shift): float
    {
        $cashIn = (float) Payment::query()
            ->where('shift_id', $shift->id)
            ->where('method', PaymentMethod::Cash)
            ->where('direction', 'in')
            ->whereNull('voided_at')
            ->sum('amount');

        $cashTxIn = (float) CashTransaction::query()
            ->where('shift_id', $shift->id)
            ->where('type', CashTransactionType::In)
            ->where('method', 'cash')
            ->sum('amount');

        $cashTxOut = (float) CashTransaction::query()
            ->where('shift_id', $shift->id)
            ->where('type', CashTransactionType::Out)
            ->where('method', 'cash')
            ->sum('amount');

        return round((float) $shift->opening_cash + $cashIn + $cashTxIn - $cashTxOut, 2);
    }

    public function close(Shift $shift, float $countedCash, ?string $notes = null): Shift
    {
        return DB::transaction(function () use ($shift, $countedCash, $notes) {
            $shift = Shift::query()->lockForUpdate()->findOrFail($shift->id);

            if ($shift->status === ShiftStatus::Closed) {
                throw new PosException('Shift ini sudah ditutup.');
            }

            $expected = $this->expectedCash($shift);

            $shift->update([
                'closed_at' => now(),
                'counted_cash' => $countedCash,
                'expected_cash' => $expected,
                'difference' => round($countedCash - $expected, 2),
                'notes' => $notes,
                'status' => ShiftStatus::Closed,
            ]);

            return $shift;
        });
    }

    /** Per-payment-method breakdown for the shift report (RPT-06). */
    public function paymentBreakdown(Shift $shift): array
    {
        return Payment::query()
            ->where('shift_id', $shift->id)
            ->where('direction', 'in')
            ->whereNull('voided_at')
            ->selectRaw('method, sum(amount) as total')
            ->groupBy('method')
            ->pluck('total', 'method')
            ->all();
    }
}
