<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\FeeInstallment;
use App\Models\FeeTariff;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceService
{
    public function __construct(private AcademicCalendar $calendar) {}

    public function expected(FeeInstallment $installment): string
    {
        $expected = bcsub((string) $installment->amount_due, (string) $installment->discount, 2);

        return bccomp($expected, '0', 2) < 0 ? '0.00' : $expected;
    }

    public function paid(FeeInstallment $installment): string
    {
        if ($installment->payments_sum_amount !== null) {
            return Money::decimal($installment->payments_sum_amount);
        }

        return Money::decimal($installment->payments()->sum('amount'));
    }

    public function remaining(FeeInstallment $installment): string
    {
        return bcsub($this->expected($installment), $this->paid($installment), 2);
    }

    public function status(FeeInstallment $installment): string
    {
        $expected = $this->expected($installment);
        $paid = $this->paid($installment);

        if (bccomp($paid, '0', 2) <= 0) {
            return 'unpaid';
        }

        $comparison = bccomp($paid, $expected, 2);

        if ($comparison < 0) {
            return 'partial';
        }

        if ($comparison === 0) {
            return 'paid';
        }

        return 'overpaid';
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'paid' => 'Payé',
            'partial' => 'Partiellement payé',
            'overpaid' => 'Trop-perçu',
            default => 'Impayé',
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordPayment(array $data, AcademicYear $year, User $actor): Payment
    {
        $this->calendar->ensureOpen($year);

        $installment = FeeInstallment::query()
            ->whereKey($data['fee_installment_id'])
            ->where('academic_year_id', $year->id)
            ->first();

        if (! $installment) {
            throw ValidationException::withMessages([
                'fee_installment_id' => 'Cette échéance n\'appartient pas à l\'année sélectionnée.',
            ]);
        }

        if ((int) $installment->student_id !== (int) $data['student_id']) {
            throw ValidationException::withMessages([
                'student_id' => 'L\'échéance ne correspond pas à cet étudiant.',
            ]);
        }

        return DB::transaction(function () use ($data, $year, $actor, $installment): Payment {
            return Payment::query()->create([
                'academic_year_id' => $year->id,
                'student_id' => $installment->student_id,
                'fee_installment_id' => $installment->id,
                'amount' => $data['amount'],
                'paid_on' => $data['paid_on'],
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'receipt_number' => $this->nextReceipt($year),
                'notes' => $data['notes'] ?? null,
                'received_by' => $actor->id,
            ]);
        });
    }

    public function assignTariff(FeeTariff $tariff): int
    {
        $this->calendar->ensureOpen($tariff->academicYear);

        $enrollments = Enrollment::query()
            ->where('academic_year_id', $tariff->academic_year_id)
            ->where('status', 'active');

        if ($tariff->student_group_id) {
            $enrollments->where('student_group_id', $tariff->student_group_id);
        } elseif ($tariff->level_id) {
            $enrollments->where('level_id', $tariff->level_id);
        }

        $created = 0;

        foreach ($enrollments->get() as $enrollment) {
            $exists = FeeInstallment::query()
                ->where('student_id', $enrollment->student_id)
                ->where('fee_tariff_id', $tariff->id)
                ->exists();

            if ($exists) {
                continue;
            }

            FeeInstallment::query()->create([
                'academic_year_id' => $tariff->academic_year_id,
                'student_id' => $enrollment->student_id,
                'fee_tariff_id' => $tariff->id,
                'label' => $tariff->name,
                'amount_due' => $tariff->amount,
                'discount' => 0,
                'due_on' => $tariff->due_on ?? $tariff->academicYear->ends_on,
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * @return array{expected: string, paid: string, remaining: string}
     */
    public function studentBalance(int $studentId, AcademicYear $year): array
    {
        $installments = FeeInstallment::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $year->id)
            ->withSum('payments', 'amount')
            ->get();

        $expected = '0.00';
        $paid = '0.00';

        foreach ($installments as $installment) {
            $expected = bcadd($expected, $this->expected($installment), 2);
            $paid = bcadd($paid, $this->paid($installment), 2);
        }

        return [
            'expected' => $expected,
            'paid' => $paid,
            'remaining' => bcsub($expected, $paid, 2),
        ];
    }

    private function nextReceipt(AcademicYear $year): string
    {
        $prefix = 'REC-'.$year->starts_on->format('Y').'-';
        $last = Payment::withTrashed()
            ->where('receipt_number', 'like', $prefix.'%')
            ->orderByDesc('receipt_number')
            ->lockForUpdate()
            ->value('receipt_number');

        $sequence = $last ? ((int) substr((string) $last, -5)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
