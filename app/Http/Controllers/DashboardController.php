<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesAcademicYear;
use App\Models\Announcement;
use App\Models\FeeInstallment;
use App\Services\AcademicYearContext;
use App\Services\DashboardMetrics;
use App\Services\FinanceService;
use App\Services\Gradebook;
use App\Services\Visibility;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResolvesAcademicYear;

    public function __invoke(
        AcademicYearContext $years,
        DashboardMetrics $metrics,
        Visibility $visibility,
        Gradebook $gradebook,
        FinanceService $finance,
    ): View {
        $user = request()->user();
        $year = $years->current();

        if (! $year) {
            return view('dashboard.empty');
        }

        if ($user->hasAnyRole(['administration', 'pedagogical'])) {
            $summary = $metrics->campus($year);

            return view('dashboard.campus', [
                'year' => $year,
                'metrics' => $summary,
                'courses' => $metrics->coursesOn(now(), $year),
                'alerts' => $metrics->alerts($year, $summary),
                'activity' => $metrics->activity($year),
                'canPlan' => $user->hasPermission('timetable.manage'),
            ]);
        }

        if ($user->hasRole('teacher') && $user->teacher) {
            return view('dashboard.teacher', [
                'year' => $year,
                'teacher' => $user->teacher->load('subjects'),
                'courses' => $metrics->coursesOn(now(), $year, $user),
                'groups' => $user->teacher->assignments()->with(['group.level', 'subject'])->whereHas('group', fn ($query) => $query->where('academic_year_id', $year->id))->get(),
                'assessments' => $visibility->assessments($user, $year)->with(['subject', 'group'])->latest('assessed_on')->limit(5)->get(),
            ]);
        }

        $student = $user->student;

        if ($user->hasRole('parent')) {
            $student = $visibility->selectedChild($user);
        }

        if (! $student) {
            return view('dashboard.empty');
        }

        $enrollment = $student->enrollments()->with(['group', 'level'])->where('academic_year_id', $year->id)->first();
        $attendance = $metrics->attendanceRate($year, $year->starts_on, $year->ends_on, $student->id);
        $balance = $finance->studentBalance($student->id, $year);
        $nextDue = FeeInstallment::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $year->id)
            ->withSum('payments', 'amount')
            ->orderBy('due_on')
            ->get()
            ->first(fn (FeeInstallment $installment): bool => bccomp($finance->remaining($installment), '0', 2) > 0);

        $roleIds = $user->roles->pluck('id')->all();
        $groupIds = $visibility->groupIds($user, $year)->all();

        return view($user->hasRole('parent') ? 'dashboard.parent' : 'dashboard.student', [
            'year' => $year,
            'student' => $student,
            'children' => $user->guardian?->students()->orderBy('last_name')->get() ?? collect(),
            'enrollment' => $enrollment,
            'courses' => $metrics->coursesOn(now(), $year, $user),
            'attendance' => $attendance,
            'average' => $gradebook->average($student, $year),
            'balance' => $balance,
            'nextDue' => $nextDue,
            'announcements' => Announcement::query()->visibleTo($roleIds, $groupIds)->latest('published_at')->limit(4)->get(),
            'absences' => $student->attendanceRecords()->whereIn('status', ['absent', 'late'])->whereHas('course', fn ($query) => $query->where('academic_year_id', $year->id))->count(),
        ]);
    }
}
