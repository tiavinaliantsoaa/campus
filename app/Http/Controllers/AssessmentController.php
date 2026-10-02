<?php

namespace App\Http\Controllers;

use App\Enums\AssessmentType;
use App\Http\Controllers\Concerns\ResolvesAcademicYear;
use App\Http\Requests\AssessmentRequest;
use App\Http\Requests\GradeEntryRequest;
use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\StudentGroup;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\Gradebook;
use App\Services\Visibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    use ResolvesAcademicYear;

    public function index(Request $request, Visibility $visibility): View
    {
        Gate::authorize('viewAny', Assessment::class);
        $year = $this->academicYear();

        $assessments = $visibility->assessments($request->user(), $year)
            ->with(['subject', 'group', 'teacher', 'semester'])
            ->when($request->filled('group'), fn ($query) => $query->where('student_group_id', $request->integer('group')))
            ->when($request->filled('subject'), fn ($query) => $query->where('subject_id', $request->integer('subject')))
            ->orderByDesc('assessed_on')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('grades.index', [
            'assessments' => $assessments,
            'year' => $year,
            'groups' => StudentGroup::query()->where('academic_year_id', $year->id)->orderBy('name')->get(),
            'subjects' => Subject::query()->orderBy('name')->get(),
            'canEnter' => $request->user()->hasPermission('grades.enter'),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Assessment::class);

        return view('grades.form', $this->formData());
    }

    public function store(AssessmentRequest $request, Gradebook $gradebook): RedirectResponse
    {
        $assessment = $gradebook->create($request->validated(), $this->academicYear(), $request->user());

        return redirect()->route('assessments.entry', $assessment)->with('status', 'Évaluation créée. Vous pouvez saisir les notes.');
    }

    public function entry(Assessment $assessment): View
    {
        Gate::authorize('view', $assessment);
        $assessment->load(['subject', 'group', 'teacher', 'grades']);
        $enrollments = Enrollment::query()
            ->with('student')
            ->where('academic_year_id', $assessment->academic_year_id)
            ->where('student_group_id', $assessment->student_group_id)
            ->get()
            ->sortBy(fn (Enrollment $enrollment) => $enrollment->student->last_name);

        $user = request()->user();

        if (! $user->hasAnyRole(['administration', 'pedagogical']) && ! ($user->teacher && $user->teacher->id === $assessment->teacher_id)) {
            $allowed = app(Visibility::class)->studentIds($user);
            $enrollments = $enrollments->whereIn('student_id', $allowed->all());
        }

        return view('grades.entry', [
            'assessment' => $assessment,
            'enrollments' => $enrollments,
            'grades' => $assessment->grades->keyBy('student_id'),
            'canEnter' => request()->user()->can('enter', $assessment),
            'canValidate' => request()->user()->can('validate', $assessment),
        ]);
    }

    public function save(GradeEntryRequest $request, Assessment $assessment, Gradebook $gradebook): RedirectResponse
    {
        $gradebook->saveScores($assessment, $request->validated('scores'), $request->validated('comments') ?? []);

        return back()->with('status', 'Notes enregistrées.');
    }

    public function submit(Assessment $assessment, Gradebook $gradebook): RedirectResponse
    {
        Gate::authorize('enter', $assessment);
        $gradebook->submit($assessment);

        return back()->with('status', 'Évaluation soumise pour validation.');
    }

    public function validateAssessment(Assessment $assessment, Gradebook $gradebook): RedirectResponse
    {
        Gate::authorize('validate', $assessment);
        $gradebook->validateAssessment($assessment, request()->user());

        return back()->with('status', 'Notes validées.');
    }

    public function reopen(Assessment $assessment, Gradebook $gradebook): RedirectResponse
    {
        Gate::authorize('validate', $assessment);
        $gradebook->reopen($assessment);

        return back()->with('status', 'Évaluation rouverte.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $year = $this->academicYear();
        $user = request()->user();

        return [
            'year' => $year,
            'types' => AssessmentType::cases(),
            'subjects' => Subject::query()->orderBy('name')->get(),
            'teachers' => Teacher::query()->where('status', 'active')->orderBy('last_name')->get(),
            'groups' => StudentGroup::query()->where('academic_year_id', $year->id)->orderBy('name')->get(),
            'semesters' => $year->semesters,
            'defaultTeacher' => $user->teacher?->id,
        ];
    }
}
