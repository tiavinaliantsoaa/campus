<?php

use App\Enums\AcademicYearStatus;
use App\Enums\PaymentMethod;
use App\Enums\StudentStatus;
use App\Models\AcademicYear;
use App\Models\FeeInstallment;
use App\Models\Payment;
use App\Models\Student;
use App\Services\FinanceService;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->year = AcademicYear::query()->create([
        'name' => '2026-2027',
        'starts_on' => '2026-09-01',
        'ends_on' => '2027-07-31',
        'status' => AcademicYearStatus::Active,
    ]);
    $this->student = Student::query()->create([
        'matricule' => 'ESCM-2026-0001',
        'first_name' => 'Aina',
        'last_name' => 'Rabe',
        'status' => StudentStatus::Active,
    ]);
    $this->installment = FeeInstallment::query()->create([
        'academic_year_id' => $this->year->id,
        'student_id' => $this->student->id,
        'label' => 'Tranche 1',
        'amount_due' => 1000,
        'discount' => 100,
        'due_on' => '2026-10-15',
    ]);
});

test('partial payments keep the remaining balance on the server', function () {
    $admin = userWithRole('administration');

    $this->actingAs($admin)->post(route('finance.payments.store'), [
        'student_id' => $this->student->id,
        'fee_installment_id' => $this->installment->id,
        'amount' => 400,
        'paid_on' => '2026-09-29',
        'method' => PaymentMethod::Cash->value,
    ])->assertRedirect();

    $finance = app(FinanceService::class);
    $installment = $this->installment->fresh();

    expect(Payment::query()->count())->toBe(1)
        ->and($finance->expected($installment))->toBe('900.00')
        ->and($finance->paid($installment))->toBe('400.00')
        ->and($finance->remaining($installment))->toBe('500.00')
        ->and($finance->status($installment))->toBe('partial');

    $this->actingAs($admin)->post(route('finance.payments.store'), [
        'student_id' => $this->student->id,
        'fee_installment_id' => $this->installment->id,
        'amount' => 600,
        'paid_on' => '2026-09-30',
        'method' => PaymentMethod::Transfer->value,
    ])->assertRedirect();

    $installment = $this->installment->fresh();

    expect($finance->remaining($installment))->toBe('-100.00')
        ->and($finance->status($installment))->toBe('overpaid');
});

test('a student cannot read another student payment receipt', function () {
    $payer = userWithRole('student');
    $other = userWithRole('student');
    $this->student->update(['user_id' => $payer->id]);
    $payment = Payment::query()->create([
        'academic_year_id' => $this->year->id,
        'student_id' => $this->student->id,
        'fee_installment_id' => $this->installment->id,
        'amount' => 100,
        'paid_on' => '2026-09-29',
        'method' => PaymentMethod::Cash,
        'receipt_number' => 'REC-2026-00001',
    ]);

    $this->actingAs($other)->get(route('finance.receipt', $payment))->assertNotFound();
    $this->actingAs($payer)->get(route('finance.receipt', $payment))->assertOk()->assertSee('REC-2026-00001');
});
