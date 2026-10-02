<?php

namespace App\Http\Controllers;

use App\Enums\TeacherStatus;
use App\Http\Controllers\Concerns\ResolvesAcademicYear;
use App\Http\Requests\TeacherRequest;
use App\Models\StudentGroup;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\TeacherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherController extends Controller
{
    use ResolvesAcademicYear;

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Teacher::class);

        $teachers = Teacher::query()
            ->with('subjects')
            ->when($request->filled('q'), fn ($query) => $query->search($request->string('q')->toString()))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        return view('teachers.index', [
            'teachers' => $teachers,
            'statuses' => TeacherStatus::cases(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Teacher::class);

        return view('teachers.form', $this->formData(new Teacher));
    }

    public function store(TeacherRequest $request, TeacherService $teachers): RedirectResponse
    {
        $result = $teachers->create($request->validated());

        return redirect()
            ->route('teachers.show', $result['teacher'])
            ->with('status', 'Enseignant enregistré.')
            ->with('temporary_passwords', array_filter([$result['password']]));
    }

    public function show(Teacher $teacher): View
    {
        Gate::authorize('view', $teacher);
        $year = $this->academicYear();
        $teacher->load(['subjects', 'user', 'documents']);
        $assignments = $teacher->assignments()->with(['group.level', 'subject'])->whereHas('group', fn ($query) => $query->where('academic_year_id', $year->id))->get();
        $courses = $teacher->courses()->with(['subject', 'group', 'room'])->where('academic_year_id', $year->id)->orderBy('starts_at')->limit(20)->get();

        return view('teachers.show', compact('teacher', 'year', 'assignments', 'courses'));
    }

    public function edit(Teacher $teacher): View
    {
        Gate::authorize('update', $teacher);
        $teacher->load(['subjects', 'assignments']);

        return view('teachers.form', $this->formData($teacher));
    }

    public function update(TeacherRequest $request, Teacher $teacher, TeacherService $teachers): RedirectResponse
    {
        $password = $teachers->update($teacher, $request->validated());

        return redirect()
            ->route('teachers.show', $teacher)
            ->with('status', 'Fiche enseignant mise à jour.')
            ->with('temporary_passwords', array_filter([$password]));
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        Gate::authorize('delete', $teacher);
        $teacher->delete();

        return redirect()->route('teachers.index')->with('status', 'Enseignant archivé.');
    }

    public function photo(Teacher $teacher): StreamedResponse
    {
        Gate::authorize('view', $teacher);
        abort_unless($teacher->photo_path && Storage::disk('local')->exists($teacher->photo_path), 404);

        return Storage::disk('local')->response($teacher->photo_path);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Teacher $teacher): array
    {
        $year = $this->academicYear();

        return [
            'teacher' => $teacher,
            'year' => $year,
            'subjects' => Subject::query()->orderBy('name')->get(),
            'groups' => StudentGroup::query()->with('level')->where('academic_year_id', $year->id)->orderBy('name')->get(),
            'statuses' => TeacherStatus::cases(),
        ];
    }
}
