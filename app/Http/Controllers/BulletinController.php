<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesAcademicYear;
use App\Models\Enrollment;
use App\Models\Semester;
use App\Models\StudentGroup;
use App\Services\AcademicCalendar;
use App\Services\Visibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BulletinController extends Controller
{
    use ResolvesAcademicYear;

    public function index(Request $request, Visibility $visibility): View
    {
        $year = $this->academicYear();
        $user = $request->user();
        $semesters = $year->semesters;
        $groups = StudentGroup::query()->with('level')->where('academic_year_id', $year->id)
            ->when(! $user->hasAnyRole(['administration', 'pedagogical']),
                fn ($query) => $query->whereIn('id', $visibility->groupIds($user, $year)))
            ->orderBy('name')->get();

        $selectedSemester = $request->filled('semester')
            ? $semesters->firstWhere('id', $request->integer('semester')) : null;
        $selectedGroup = $request->filled('group')
            ? $groups->firstWhere('id', $request->integer('group')) : null;

        if ($request->filled('semester') && ! $selectedSemester) {
            abort(404);
        }

        if ($request->filled('group') && ! $selectedGroup) {
            abort(404);
        }

        $enrollments = collect();
        $assessments = collect();
        $modules = collect();
        $selectedStudent = null;

        if ($selectedSemester && $selectedGroup) {
            $modules = collect(config('bulletins.modules', []))
                ->filter(fn (array $module): bool => $module['level'] === $selectedGroup->level->code
                    && ($module['term'] === null || $module['term'] === $selectedSemester->sort_order))
                ->values();

            $enrollments = Enrollment::query()->with('student')
                ->where('academic_year_id', $year->id)
                ->where('student_group_id', $selectedGroup->id)
                ->whereIn('student_id', $visibility->students($user)->select('students.id'))
                ->get()->sortBy(fn (Enrollment $enrollment): string => $enrollment->student->last_name)->values();

            $assessments = $visibility->assessments($user, $year)
                ->where('semester_id', $selectedSemester->id)
                ->where('student_group_id', $selectedGroup->id)
                ->with(['subject', 'grades' => fn ($query) => $query->whereIn('student_id', $enrollments->pluck('student_id'))])
                ->orderBy('assessed_on')->get();

            if ($request->filled('student')) {
                $selectedStudent = $enrollments->firstWhere('student_id', $request->integer('student'))?->student;
            }
        }

        if ($request->filled('student') && ! $selectedStudent) {
            abort(404);
        }

        return view('bulletins.index', compact(
            'year', 'semesters', 'groups', 'selectedSemester', 'selectedGroup', 'enrollments', 'assessments', 'modules', 'selectedStudent'
        ));
    }

    public function storeSemester(Request $request, AcademicCalendar $calendar): RedirectResponse
    {
        $year = $this->academicYear();
        $calendar->ensureOpen($year);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('semesters')->where('academic_year_id', $year->id)],
            'starts_on' => ['required', 'date', 'after_or_equal:'.$year->starts_on->toDateString()],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on', 'before_or_equal:'.$year->ends_on->toDateString()],
        ]);

        $overlap = Semester::query()->where('academic_year_id', $year->id)
            ->whereDate('starts_on', '<=', $data['ends_on'])
            ->whereDate('ends_on', '>=', $data['starts_on'])
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages(['starts_on' => 'Cette période chevauche un semestre existant.']);
        }

        $semester = DB::transaction(fn (): Semester => $year->semesters()->create([
            ...$data,
            'sort_order' => (int) $year->semesters()->max('sort_order') + 1,
        ]));

        return redirect()->route('bulletins.index', ['semester' => $semester->id])
            ->with('status', 'Semestre créé.');
    }
}
