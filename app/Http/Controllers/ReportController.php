<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Concerns\ResolvesAcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\FeeInstallment;
use App\Models\StudentGroup;
use App\Services\FinanceService;
use App\Services\Gradebook;
use Illuminate\View\View;

class ReportController extends Controller
{
    use ResolvesAcademicYear;

    public function index(): View
    {
        abort_unless(request()->user()->hasPermission('reports.view'), 403);

        return view('reports.index', [
            'year' => $this->academicYear(),
            'canFinance' => request()->user()->hasPermission('finance.view'),
        ]);
    }

    public function attendance(): View
    {
        abort_unless(request()->user()->hasPermission('reports.view'), 403);
        $year = $this->academicYear();
        $groups = StudentGroup::query()->where('academic_year_id', $year->id)->orderBy('name')->get();

        $rows = $groups->map(function (StudentGroup $group): array {
            $records = AttendanceRecord::query()->whereHas('course', fn ($query) => $query->where('student_group_id', $group->id));
            $total = (clone $records)->count();
            $present = (clone $records)->whereIn('status', [AttendanceStatus::Present->value, AttendanceStatus::Late->value])->count();

            return [
                'group' => $group,
                'total' => $total,
                'rate' => $total > 0 ? (int) round(($present / $total) * 100) : null,
            ];
        });

        return view('reports.attendance', compact('year', 'rows'));
    }

    public function grades(Gradebook $gradebook): View
    {
        abort_unless(request()->user()->hasPermission('reports.view'), 403);
        $year = $this->academicYear();
        $groups = StudentGroup::query()->where('academic_year_id', $year->id)->orderBy('name')->get();
        $rows = $groups->map(function (StudentGroup $group) use ($gradebook, $year): array {
            $ranking = $gradebook->ranking($group->id, $year);
            $averages = $ranking->pluck('average')->filter();

            return [
                'group' => $group,
                'count' => $averages->count(),
                'average' => $averages->isEmpty() ? null : number_format($averages->avg(), 2, '.', ''),
            ];
        });

        return view('reports.grades', compact('year', 'rows'));
    }

    public function finance(FinanceService $finance): View
    {
        abort_unless(request()->user()->hasPermission('finance.view'), 403);
        $year = $this->academicYear();
        $installments = FeeInstallment::query()
            ->with('student')
            ->where('academic_year_id', $year->id)
            ->withSum('payments', 'amount')
            ->orderBy('due_on')
            ->get()
            ->filter(fn (FeeInstallment $installment): bool => bccomp($finance->remaining($installment), '0', 2) > 0)
            ->values();

        return view('reports.finance', [
            'year' => $year,
            'installments' => $installments,
            'finance' => $finance,
            'enrolled' => Enrollment::query()->where('academic_year_id', $year->id)->where('status', 'active')->count(),
        ]);
    }
}
