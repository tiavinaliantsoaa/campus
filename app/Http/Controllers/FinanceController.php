<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Concerns\ResolvesAcademicYear;
use App\Http\Requests\PaymentRequest;
use App\Models\FeeInstallment;
use App\Models\FeeTariff;
use App\Models\Level;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentGroup;
use App\Services\AcademicCalendar;
use App\Services\FinanceService;
use App\Services\Visibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FinanceController extends Controller
{
    use ResolvesAcademicYear;

    public function index(Request $request, Visibility $visibility, FinanceService $finance): View
    {
        Gate::authorize('viewAny', Payment::class);
        $year = $this->academicYear();
        $manage = $request->user()->hasPermission('finance.manage');

        $installments = $visibility->installments($request->user(), $year)
            ->with(['student', 'tariff'])
            ->withSum('payments', 'amount')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim(str_replace(['%', '_'], '', $request->string('q')->toString()));
                $query->whereHas('student', fn ($student) => $student->search($term));
            })
            ->orderBy('due_on')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('finance.index', [
            'year' => $year,
            'manage' => $manage,
            'installments' => $installments,
            'finance' => $finance,
            'tariffs' => $manage ? FeeTariff::query()->with(['level', 'group'])->where('academic_year_id', $year->id)->orderBy('name')->get() : collect(),
            'payments' => $visibility->payments($request->user(), $year)->with('student')->latest('paid_on')->limit(8)->get(),
        ]);
    }

    public function storeTariff(Request $request, AcademicCalendar $calendar): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('finance.manage'), 403);
        $year = $this->academicYear();
        $calendar->ensureOpen($year);

        FeeTariff::query()->create([
            ...$request->validate([
                'name' => ['required', 'string', 'max:150'],
                'amount' => ['required', 'numeric', 'min:0'],
                'due_on' => ['nullable', 'date'],
                'level_id' => ['nullable', 'integer', 'exists:levels,id'],
                'student_group_id' => ['nullable', 'integer', 'exists:student_groups,id'],
            ]),
            'academic_year_id' => $year->id,
        ]);

        return back()->with('status', 'Tarif enregistré.');
    }

    public function assignTariff(FeeTariff $feeTariff, FinanceService $finance): RedirectResponse
    {
        abort_unless(request()->user()->hasPermission('finance.manage'), 403);
        $count = $finance->assignTariff($feeTariff);

        return back()->with('status', $count.' échéance(s) générée(s).');
    }

    public function createInstallment(): View
    {
        abort_unless(request()->user()->hasPermission('finance.manage'), 403);
        $year = $this->academicYear();

        return view('finance.installment', [
            'year' => $year,
            'students' => Student::query()->whereHas('enrollments', fn ($query) => $query->where('academic_year_id', $year->id))->orderBy('last_name')->get(),
            'tariffs' => FeeTariff::query()->where('academic_year_id', $year->id)->orderBy('name')->get(),
            'levels' => Level::query()->orderBy('sort_order')->get(),
            'groups' => StudentGroup::query()->where('academic_year_id', $year->id)->orderBy('name')->get(),
        ]);
    }

    public function storeInstallment(Request $request, AcademicCalendar $calendar): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('finance.manage'), 403);
        $year = $this->academicYear();
        $calendar->ensureOpen($year);
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'fee_tariff_id' => ['nullable', 'integer', 'exists:fee_tariffs,id'],
            'label' => ['required', 'string', 'max:150'],
            'amount_due' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'due_on' => ['required', 'date'],
        ]);

        if (bccomp((string) ($data['discount'] ?? 0), (string) $data['amount_due'], 2) > 0) {
            throw ValidationException::withMessages([
                'discount' => 'La remise ne peut pas dépasser le montant dû.',
            ]);
        }

        FeeInstallment::query()->create([
            ...$data,
            'discount' => $data['discount'] ?? 0,
            'academic_year_id' => $year->id,
        ]);

        return redirect()->route('finance.statement', $data['student_id'])->with('status', 'Échéance créée.');
    }

    public function statement(Student $student, Visibility $visibility, FinanceService $finance): View
    {
        abort_unless($visibility->seesStudent(request()->user(), $student) && request()->user()->hasPermission('finance.view'), 404);
        $year = $this->academicYear();
        $installments = $student->installments()->where('academic_year_id', $year->id)->with('payments')->withSum('payments', 'amount')->orderBy('due_on')->get();

        return view('finance.statement', [
            'student' => $student,
            'year' => $year,
            'installments' => $installments,
            'payments' => $student->payments()->where('academic_year_id', $year->id)->latest('paid_on')->get(),
            'balance' => $finance->studentBalance($student->id, $year),
            'finance' => $finance,
            'methods' => PaymentMethod::cases(),
            'canRecord' => request()->user()->hasPermission('finance.manage'),
        ]);
    }

    public function storePayment(PaymentRequest $request, FinanceService $finance): RedirectResponse
    {
        $payment = $finance->recordPayment($request->validated(), $this->academicYear(), $request->user());

        return redirect()->route('finance.receipt', $payment)->with('status', 'Paiement enregistré.');
    }

    public function receipt(Payment $payment): View
    {
        Gate::authorize('view', $payment);
        $payment->load(['student', 'installment', 'receiver', 'academicYear']);

        return view('finance.receipt', ['payment' => $payment]);
    }

    public function destroyPayment(Payment $payment, AcademicCalendar $calendar): RedirectResponse
    {
        Gate::authorize('delete', $payment);
        $calendar->ensureOpen($payment->academicYear);
        $studentId = $payment->student_id;
        $payment->delete();

        return redirect()->route('finance.statement', $studentId)->with('status', 'Paiement annulé.');
    }
}
