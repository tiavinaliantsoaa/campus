<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesAcademicYear;
use App\Http\Requests\CourseRequest;
use App\Models\Course;
use App\Models\Room;
use App\Models\StudentGroup;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\CourseScheduler;
use App\Services\Visibility;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CourseController extends Controller
{
    use ResolvesAcademicYear;

    public function index(Request $request, Visibility $visibility): View
    {
        Gate::authorize('viewAny', Course::class);
        $year = $this->academicYear();
        $anchor = Carbon::parse($request->input('date', now()->toDateString()));
        $view = $request->string('view')->toString() === 'day' ? 'day' : 'week';
        $start = $view === 'day' ? $anchor->copy()->startOfDay() : $anchor->copy()->startOfWeek();
        $end = $view === 'day' ? $anchor->copy()->endOfDay() : $anchor->copy()->endOfWeek();

        $courses = $visibility->courses($request->user(), $year)
            ->with(['subject', 'teacher', 'group', 'room'])
            ->whereBetween('starts_at', [$start, $end])
            ->when($request->filled('group'), fn ($query) => $query->where('student_group_id', $request->integer('group')))
            ->when($request->filled('teacher'), fn ($query) => $query->where('teacher_id', $request->integer('teacher')))
            ->when($request->filled('room'), fn ($query) => $query->where('room_id', $request->integer('room')))
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        return view('courses.index', [
            'courses' => $courses,
            'year' => $year,
            'view' => $view,
            'anchor' => $anchor,
            'start' => $start,
            'end' => $end,
            'groups' => StudentGroup::query()->where('academic_year_id', $year->id)->orderBy('name')->get(),
            'teachers' => Teacher::query()->orderBy('last_name')->get(),
            'rooms' => Room::query()->orderBy('code')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Course::class);

        return view('courses.form', $this->formData(new Course));
    }

    public function store(CourseRequest $request, CourseScheduler $scheduler): RedirectResponse
    {
        $course = $scheduler->create($request->validated(), $this->academicYear());

        return redirect()->route('courses.index', ['date' => $course->starts_at->toDateString()])->with('status', 'Cours planifié.');
    }

    public function edit(Course $course): View
    {
        Gate::authorize('update', $course);

        return view('courses.form', $this->formData($course));
    }

    public function update(CourseRequest $request, Course $course, CourseScheduler $scheduler): RedirectResponse
    {
        $scheduler->update($course, $request->validated());

        return redirect()->route('courses.index', ['date' => $course->starts_at->toDateString()])->with('status', 'Cours mis à jour.');
    }

    public function destroy(Course $course, CourseScheduler $scheduler): RedirectResponse
    {
        Gate::authorize('delete', $course);
        $scheduler->delete($course);

        return redirect()->route('courses.index')->with('status', 'Cours supprimé.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Course $course): array
    {
        $year = $course->exists ? $course->academicYear : $this->academicYear();

        return [
            'course' => $course,
            'year' => $year,
            'subjects' => Subject::query()->orderBy('name')->get(),
            'teachers' => Teacher::query()->where('status', 'active')->orderBy('last_name')->get(),
            'groups' => StudentGroup::query()->where('academic_year_id', $year->id)->orderBy('name')->get(),
            'rooms' => Room::query()->orderBy('code')->get(),
            'semesters' => $year->semesters,
        ];
    }
}
