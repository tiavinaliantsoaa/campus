<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesAcademicYear;
use App\Http\Requests\AttendanceRequest;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\StudentGroup;
use App\Services\AttendanceService;
use App\Services\Visibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    use ResolvesAcademicYear;

    public function index(Request $request, Visibility $visibility): View
    {
        abort_unless($request->user()->hasPermission('attendance.view'), 403);
        $year = $this->academicYear();

        $courses = $visibility->courses($request->user(), $year)
            ->with(['subject', 'teacher', 'group', 'room'])
            ->withCount('attendanceRecords')
            ->where('status', 'scheduled')
            ->when($request->filled('group'), fn ($query) => $query->where('student_group_id', $request->integer('group')))
            ->when($request->date('from'), fn ($query, $from) => $query->whereDate('starts_at', '>=', $from))
            ->when($request->date('to'), fn ($query, $to) => $query->whereDate('starts_at', '<=', $to))
            ->orderByDesc('starts_at')
            ->paginate(15)
            ->withQueryString();

        $history = AttendanceRecord::query()
            ->with(['student', 'course.subject', 'course.group'])
            ->whereHas('course', function ($query) use ($request, $year, $visibility) {
                $query->whereIn('id', $visibility->courses($request->user(), $year)->select('courses.id'));
            })
            ->when(
                ! $request->user()->hasAnyRole(['administration', 'pedagogical']),
                fn ($query) => $query->whereIn('student_id', $visibility->studentIds($request->user())),
            )
            ->when($request->filled('student'), fn ($query) => $query->where('student_id', $request->integer('student')))
            ->latest('id')
            ->limit(25)
            ->get();

        return view('attendance.index', [
            'courses' => $courses,
            'history' => $history,
            'year' => $year,
            'groups' => StudentGroup::query()->where('academic_year_id', $year->id)->orderBy('name')->get(),
        ]);
    }

    public function roll(Course $course): View
    {
        Gate::authorize('recordAttendance', $course);
        $course->load(['subject', 'teacher', 'group', 'room', 'attendanceRecords']);

        $enrollments = Enrollment::query()
            ->with('student')
            ->where('academic_year_id', $course->academic_year_id)
            ->where('student_group_id', $course->student_group_id)
            ->where('status', 'active')
            ->get()
            ->sortBy(fn (Enrollment $enrollment) => $enrollment->student->last_name);

        return view('attendance.roll', [
            'course' => $course,
            'enrollments' => $enrollments,
            'existing' => $course->attendanceRecords->keyBy('student_id'),
        ]);
    }

    public function store(AttendanceRequest $request, Course $course, AttendanceService $attendance): RedirectResponse
    {
        $count = $attendance->record(
            $course,
            $request->validated('statuses'),
            $request->validated('comments') ?? [],
            $request->user(),
        );

        return redirect()->route('attendance.roll', $course)->with('status', $count.' présence(s) enregistrée(s).');
    }
}
